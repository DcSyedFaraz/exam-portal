<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\StudentAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    protected function ensureExamAvailableForStudent(Exam $exam): void
    {
        $studentClassLevel = auth()->user()->studentProfile?->class_level;

        abort_unless(
            is_null($exam->class_level) || $exam->class_level === $studentClassLevel,
            403,
            'This exam is not available for your class level.'
        );
    }

    protected function latestSubmittedAttempt(int $studentId, int $examId): ?ExamAttempt
    {
        return ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();
    }

    protected function getOrCreateInProgressAttempt(int $studentId, int $examId): ExamAttempt
    {
        return DB::transaction(function () use ($studentId, $examId) {
            $attempt = ExamAttempt::where('student_id', $studentId)
                ->where('exam_id', $examId)
                ->whereNull('submitted_at')
                ->lockForUpdate()
                ->first();

            if ($attempt) {
                return $attempt;
            }

            return ExamAttempt::create([
                'student_id' => $studentId,
                'exam_id'    => $examId,
                'started_at' => now(),
            ]);
        });
    }

    public function index()
    {
        $studentId = auth()->id();
        $studentClassLevel = auth()->user()->studentProfile?->class_level;

        $classFilter = function ($q) use ($studentClassLevel) {
            $q->whereNull('class_level');
            if ($studentClassLevel) {
                $q->orWhere('class_level', $studentClassLevel);
            }
        };

        $exams = Exam::where('is_published', true)
            ->where($classFilter)
            ->withCount('questions')
            ->latest()
            ->get()
            ->map(function ($exam) use ($studentId) {
                $exam->latest_attempt = ExamAttempt::where('student_id', $studentId)
                    ->where('exam_id', $exam->id)
                    ->whereNotNull('submitted_at')
                    ->latest('submitted_at')
                    ->first();

                return $exam;
            });

        return view('student.exams.index', compact('exams'));
    }

    public function instructions(Exam $exam)
    {
        $studentId = auth()->id();

        $this->ensureExamAvailableForStudent($exam);

        $latestAttempt = $this->latestSubmittedAttempt($studentId, $exam->id);
        if ($latestAttempt && $latestAttempt->is_passed) {
            return redirect()->route('student.exams.result', $exam)
                ->with('info', 'You have already passed this exam.');
        }

        // If exam is already started (in-progress attempt), go straight back to the exam
        $inProgress = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->first();
        if ($inProgress) {
            return redirect()->route('student.exams.take', $exam);
        }

        $questionsCount = $exam->questions()->count();

        return view('student.exams.instructions', compact('exam', 'questionsCount'));
    }

    public function begin(Request $request, Exam $exam): RedirectResponse
    {
        $studentId = auth()->id();

        $this->ensureExamAvailableForStudent($exam);

        $latestAttempt = $this->latestSubmittedAttempt($studentId, $exam->id);
        if ($latestAttempt && $latestAttempt->is_passed) {
            return redirect()->route('student.exams.result', $exam)
                ->with('info', 'You have already passed this exam.');
        }

        // On retake after fail: delete the old submitted attempt to keep only latest
        if ($latestAttempt && ! $latestAttempt->is_passed) {
            $latestAttempt->delete(); // cascades to student_answers
        }

        $this->getOrCreateInProgressAttempt($studentId, $exam->id);

        return redirect()->route('student.exams.take', $exam);
    }

    public function take(Exam $exam)
    {
        $studentId = auth()->id();
        $this->ensureExamAvailableForStudent($exam);

        // Check if student already passed — no retake allowed
        $latestAttempt = $this->latestSubmittedAttempt($studentId, $exam->id);

        if ($latestAttempt && $latestAttempt->is_passed) {
            return redirect()->route('student.exams.result', $exam)
                ->with('info', 'You have already passed this exam.');
        }

        // On retake: delete the old attempt to keep only latest
        if ($latestAttempt && ! $latestAttempt->is_passed) {
            $latestAttempt->delete(); // cascades to student_answers
        }

        // Ensure attempt exists (e.g. direct-link to /take)
        $attempt = $this->getOrCreateInProgressAttempt($studentId, $exam->id);

        $questions = $exam->questions()->with('options')->get();

        // Load any saved answers for this attempt
        $savedAnswers = StudentAnswer::where('attempt_id', $attempt->id)
            ->pluck('selected_option_id', 'question_id');

        return view('student.exams.take', compact('exam', 'attempt', 'questions', 'savedAnswers'));
    }

    public function submit(Request $request, Exam $exam)
    {
        $studentId = auth()->id();

        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->firstOrFail();

        $answers = $request->input('answers', []);

        DB::transaction(function () use ($attempt, $exam, $answers) {
            $totalScore = 0;

            // Delete any previously saved progress answers
            $attempt->answers()->delete();

            foreach ($exam->questions()->with('options')->get() as $question) {
                $isCorrect = false;
                $marksAwarded = 0;
                $selectedId = null;

                if ($question->question_type === 'match') {
                    // Match questions: answers[questionId][optionId] = matchPairValue
                    // Partial credit: each correctly matched pair earns a proportional share.
                    $questionAnswers = $answers[$question->id] ?? [];
                    $options         = $question->options;
                    $pairCount       = $options->count();

                    $correctPairs     = 0;
                    $matchSelections  = []; // store what the student submitted per option

                    if ($pairCount > 0) {
                        foreach ($options as $option) {
                            $submitted = $questionAnswers[$option->id] ?? null;
                            // Store raw submission (null if not answered)
                            $matchSelections[(string) $option->id] = $submitted;
                            if ($submitted !== null && $submitted === $option->match_pair) {
                                $correctPairs++;
                            }
                        }
                    }

                    // round() distributes marks proportionally and is cleanly invertible:
                    // correctPairs ≈ round(marks_awarded * pairCount / question->marks)
                    $marksAwarded = $pairCount > 0
                        ? (int) round($correctPairs * $question->marks / $pairCount)
                        : 0;

                    // is_correct = true only when ALL pairs are correct
                    $isCorrect = $pairCount > 0 && ($correctPairs === $pairCount);

                    StudentAnswer::create([
                        'attempt_id'         => $attempt->id,
                        'question_id'        => $question->id,
                        'selected_option_id' => null,
                        'match_selections'   => $matchSelections,
                        'is_correct'         => $isCorrect,
                        'marks_awarded'      => $marksAwarded,
                    ]);
                } else {
                    // MCQ / True-False
                    $selectedId = $answers[$question->id] ?? null;

                    if ($selectedId) {
                        $isCorrect = $question->options()
                            ->where('id', $selectedId)
                            ->where('is_correct', true)
                            ->exists();
                        $marksAwarded = $isCorrect ? $question->marks : 0;
                    }

                    StudentAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'selected_option_id' => $selectedId,
                        'is_correct' => $isCorrect,
                        'marks_awarded' => $marksAwarded,
                    ]);
                }

                $totalScore += $marksAwarded;
            }

            $attempt->update([
                'score' => $totalScore,
                'is_passed' => $totalScore >= $exam->passing_marks,
                'submitted_at' => now(),
            ]);
        });

        return redirect()->route('student.exams.result', $exam);
    }

    public function result(Exam $exam)
    {
        $studentId = auth()->id();
        $this->ensureExamAvailableForStudent($exam);

        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->with(['answers.question.options', 'answers.question.correctOption', 'answers.selectedOption'])
            ->firstOrFail();

        $exam->load('questions.options');

        return view('student.exams.result', compact('exam', 'attempt'));
    }

    public function saveProgress(Request $request, Exam $exam): Response
    {
        $studentId = auth()->id();

        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->first();

        if (! $attempt) {
            return response()->noContent();
        }

        $answers = $request->input('answers', []);

        foreach ($answers as $questionId => $optionId) {
            if (is_array($optionId)) {
                continue;
            } // skip match answers in beacon save

            StudentAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                ['selected_option_id' => $optionId ?: null]
            );
        }

        return response()->noContent();
    }
}
