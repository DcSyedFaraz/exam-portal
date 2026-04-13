<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index()
    {
        $studentId         = auth()->id();
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

    public function take(Exam $exam)
    {
        $studentId         = auth()->id();
        $studentClassLevel = auth()->user()->studentProfile?->class_level;

        abort_unless(
            is_null($exam->class_level) || $exam->class_level === $studentClassLevel,
            403,
            'This exam is not available for your class level.'
        );

        // Check if student already passed — no retake allowed
        $latestAttempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();

        if ($latestAttempt && $latestAttempt->is_passed) {
            return redirect()->route('student.exams.result', $exam)
                ->with('info', 'You have already passed this exam.');
        }

        // On retake: delete the old attempt to keep only latest
        if ($latestAttempt && !$latestAttempt->is_passed) {
            $latestAttempt->delete(); // cascades to student_answers
        }

        // Check for an in-progress attempt (started but not submitted)
        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->first();

        if (!$attempt) {
            $attempt = ExamAttempt::create([
                'student_id' => $studentId,
                'exam_id'    => $exam->id,
                'started_at' => now(),
            ]);
        }

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
                $isCorrect    = false;
                $marksAwarded = 0;
                $selectedId   = null;

                if ($question->question_type === 'match') {
                    // Match questions: answers[questionId][optionId] = matchPairValue
                    $questionAnswers = $answers[$question->id] ?? [];
                    $allCorrect = true;

                    foreach ($question->options as $option) {
                        $submitted = $questionAnswers[$option->id] ?? null;
                        if (!$submitted || $submitted !== $option->match_pair) {
                            $allCorrect = false;
                        }
                    }

                    $isCorrect    = $allCorrect && count($questionAnswers) === $question->options->count();
                    $marksAwarded = $isCorrect ? $question->marks : 0;
                    $selectedId   = null; // Not applicable for match

                    StudentAnswer::create([
                        'attempt_id'         => $attempt->id,
                        'question_id'        => $question->id,
                        'selected_option_id' => null,
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
                        'attempt_id'         => $attempt->id,
                        'question_id'        => $question->id,
                        'selected_option_id' => $selectedId,
                        'is_correct'         => $isCorrect,
                        'marks_awarded'      => $marksAwarded,
                    ]);
                }

                $totalScore += $marksAwarded;
            }

            $attempt->update([
                'score'        => $totalScore,
                'is_passed'    => $totalScore >= $exam->passing_marks,
                'submitted_at' => now(),
            ]);
        });

        return redirect()->route('student.exams.result', $exam);
    }

    public function result(Exam $exam)
    {
        $studentId         = auth()->id();
        $studentClassLevel = auth()->user()->studentProfile?->class_level;

        abort_unless(
            is_null($exam->class_level) || $exam->class_level === $studentClassLevel,
            403,
            'This exam is not available for your class level.'
        );

        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->with(['answers.question.options', 'answers.selectedOption'])
            ->firstOrFail();

        return view('student.exams.result', compact('exam', 'attempt'));
    }

    public function saveProgress(Request $request, Exam $exam): Response
    {
        $studentId = auth()->id();

        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->first();

        if (!$attempt) {
            return response()->noContent();
        }

        $answers = $request->input('answers', []);

        foreach ($answers as $questionId => $optionId) {
            if (is_array($optionId)) continue; // skip match answers in beacon save

            StudentAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                ['selected_option_id' => $optionId ?: null]
            );
        }

        return response()->noContent();
    }
}
