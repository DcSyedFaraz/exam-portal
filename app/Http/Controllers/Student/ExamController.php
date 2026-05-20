<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentSubAnswer;
use App\Services\GeminiEvaluationService;
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

        if ($latestAttempt && ! $latestAttempt->is_passed) {
            $latestAttempt->delete();
        }

        $this->getOrCreateInProgressAttempt($studentId, $exam->id);

        return redirect()->route('student.exams.take', $exam);
    }

    public function take(Exam $exam)
    {
        $studentId = auth()->id();
        $this->ensureExamAvailableForStudent($exam);

        $latestAttempt = $this->latestSubmittedAttempt($studentId, $exam->id);

        if ($latestAttempt && $latestAttempt->is_passed) {
            return redirect()->route('student.exams.result', $exam)
                ->with('info', 'You have already passed this exam.');
        }

        if ($latestAttempt && ! $latestAttempt->is_passed) {
            $latestAttempt->delete();
        }

        $attempt = $this->getOrCreateInProgressAttempt($studentId, $exam->id);

        $questions = $exam->questions()->with('options', 'subItems')->get();

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

        $gemini    = app(GeminiEvaluationService::class);
        $questions = $exam->questions()->with('options', 'subItems')->get();

        // ── Pre-pass: collect ALL AI-graded items for a single Gemini batch call ──
        // Done BEFORE opening the DB transaction so the Gemini HTTP call never
        // holds a MySQL lock (which causes deadlocks on slower connections).
        $aiBatch   = [];   // keyed by question ID — for ai_evaluated questions
        $picBatch  = [];   // keyed by "pic_{subItemId}" — for picture sub-questions

        foreach ($questions as $question) {
            $answerData = $answers[$question->id] ?? null;

            if ($question->question_type === 'ai_evaluated') {
                $text = strip_tags(trim($answerData['text'] ?? ''));
                $aiBatch[$question->id] = [
                    'question'       => $question->question_text,
                    'correct_answer' => $question->correct_answer_text ?? '',
                    'student_answer' => $text,
                    'max_marks'      => $question->marks,
                    'answer_text'    => $text,
                ];
            }

            if ($question->question_type === 'picture') {
                $subAnswers = $answerData['sub'] ?? [];
                foreach ($question->subItems as $sub) {
                    $text = strip_tags(trim($subAnswers[$sub->id] ?? ''));
                    $key  = "pic_{$sub->id}";
                    $picBatch[$key] = [
                        'question'       => "({$sub->label}) {$sub->sub_question_text}",
                        'correct_answer' => $sub->correct_answer,
                        'student_answer' => $text,
                        'max_marks'      => $sub->marks,
                        'answer_text'    => $text,
                        'sub_id'         => $sub->id,
                    ];
                }
            }
        }

        // Merge into one batch for a single Gemini API call — outside the transaction
        $allBatchItems = [];
        foreach ($aiBatch as $qid => $item) {
            $allBatchItems[$qid] = [
                'question'       => $item['question'],
                'correct_answer' => $item['correct_answer'],
                'student_answer' => $item['student_answer'],
                'max_marks'      => $item['max_marks'],
            ];
        }
        foreach ($picBatch as $key => $item) {
            $allBatchItems[$key] = [
                'question'       => $item['question'],
                'correct_answer' => $item['correct_answer'],
                'student_answer' => $item['student_answer'],
                'max_marks'      => $item['max_marks'],
            ];
        }

        $aiResults = [];
        if (! empty($allBatchItems)) {
            $aiResults = $gemini->evaluateAll($allBatchItems);
        }

        // ── Transaction: fast DB writes only, no network I/O ─────────────────────
        DB::transaction(function () use ($attempt, $exam, $answers, $questions, $aiBatch, $picBatch, $aiResults) {
            $totalScore = 0;

            $attempt->answers()->delete();

            // ── Main loop ────────────────────────────────────────────────────────
            foreach ($questions as $question) {
                $answerData = $answers[$question->id] ?? null;

                match ($question->question_type) {

                    'mcq', 'true_false' => (function () use ($question, $answerData, $attempt, &$totalScore) {
                        $selectedId = $answerData['option'] ?? $answerData ?? null;
                        $isCorrect = false;
                        $marksAwarded = 0;
                        if ($selectedId) {
                            $isCorrect = $question->options()
                                ->where('id', $selectedId)
                                ->where('is_correct', true)
                                ->exists();
                            $marksAwarded = $isCorrect ? $question->marks : 0;
                        }
                        $totalScore += $marksAwarded;
                        StudentAnswer::create([
                            'attempt_id'         => $attempt->id,
                            'question_id'        => $question->id,
                            'selected_option_id' => $selectedId,
                            'is_correct'         => $isCorrect,
                            'marks_awarded'      => $marksAwarded,
                            'ai_evaluated'       => false,
                        ]);
                    })(),

                    'match' => (function () use ($question, $answerData, $attempt, &$totalScore) {
                        $questionAnswers = $answerData ?? [];
                        $options  = $question->options;
                        $pairCount = $options->count();
                        $correctPairs = 0;
                        $matchSelections = [];

                        if ($pairCount > 0) {
                            foreach ($options as $option) {
                                $submitted = $questionAnswers[$option->id] ?? null;
                                $matchSelections[(string) $option->id] = $submitted;
                                if ($submitted !== null && $submitted === $option->match_pair) {
                                    $correctPairs++;
                                }
                            }
                        }

                        $marksAwarded = $pairCount > 0
                            ? (int) round($correctPairs * $question->marks / $pairCount)
                            : 0;
                        $isCorrect = $pairCount > 0 && ($correctPairs === $pairCount);
                        $totalScore += $marksAwarded;

                        StudentAnswer::create([
                            'attempt_id'         => $attempt->id,
                            'question_id'        => $question->id,
                            'selected_option_id' => null,
                            'match_selections'   => $matchSelections,
                            'is_correct'         => $isCorrect,
                            'marks_awarded'      => $marksAwarded,
                            'ai_evaluated'       => false,
                        ]);
                    })(),

                    'fill_blank' => (function () use ($question, $answerData, $attempt, &$totalScore) {
                        // Collect answers from inline per-blank inputs: answers[qid][fb][0], [1], …
                        $fbInputs     = $answerData['fb'] ?? [];
                        $studentParts = array_map('trim', array_values((array) $fbInputs));
                        // Store as pipe-joined string for display in result view
                        $text = implode('|', $studentParts);

                        $correctParts = array_map('trim', explode('|', $question->correct_answer_text ?? ''));
                        $total        = count($correctParts);
                        $matched      = 0;
                        foreach ($correctParts as $i => $correct) {
                            $given = strtolower($studentParts[$i] ?? '');
                            if ($given !== '' && $given === strtolower($correct)) {
                                $matched++;
                            }
                        }
                        $marksAwarded = $total > 0 ? round(($matched / $total) * $question->marks, 2) : 0;
                        $isCorrect    = $total > 0 && $matched === $total;
                        $totalScore  += $marksAwarded;
                        StudentAnswer::create([
                            'attempt_id'    => $attempt->id,
                            'question_id'   => $question->id,
                            'answer_text'   => mb_substr($text, 0, 500),
                            'is_correct'    => $isCorrect,
                            'marks_awarded' => $marksAwarded,
                            'ai_feedback'   => null,
                            'ai_evaluated'  => false,
                        ]);
                    })(),

                    'ai_evaluated' => (function () use ($question, $aiBatch, $aiResults, $attempt, &$totalScore) {
                        // Results already computed via batch call above
                        $result = $aiResults[$question->id] ?? ['marks' => 0, 'feedback' => 'AI evaluation failed. Pending manual review.'];
                        $text   = $aiBatch[$question->id]['answer_text'] ?? '';
                        $totalScore += $result['marks'];
                        StudentAnswer::create([
                            'attempt_id'    => $attempt->id,
                            'question_id'   => $question->id,
                            'answer_text'   => mb_substr($text, 0, 1000),
                            'is_correct'    => $result['marks'] >= ($question->marks * 0.5),
                            'marks_awarded' => $result['marks'],
                            'ai_feedback'   => $result['feedback'],
                            'ai_evaluated'  => true,
                        ]);
                    })(),

                    'word_bank' => (function () use ($question, $answerData, $attempt, &$totalScore) {
                        $wordBankAnswers = $answerData['word_bank'] ?? [];
                        $correctCount   = 0;
                        $totalOptions   = $question->options->count();

                        foreach ($question->options as $option) {
                            $selected = $wordBankAnswers[$option->id] ?? null;
                            if ($selected && strtolower(trim($selected)) === strtolower(trim($option->match_pair))) {
                                $correctCount++;
                            }
                        }

                        $marksPerItem = $totalOptions > 0 ? $question->marks / $totalOptions : 0;
                        $marksAwarded = round($correctCount * $marksPerItem, 2);
                        $totalScore  += $marksAwarded;

                        StudentAnswer::create([
                            'attempt_id'    => $attempt->id,
                            'question_id'   => $question->id,
                            'answer_text'   => json_encode($wordBankAnswers),
                            'is_correct'    => $correctCount === $totalOptions,
                            'marks_awarded' => $marksAwarded,
                            'ai_evaluated'  => false,
                        ]);
                    })(),

                    'picture' => (function () use ($question, $answerData, $attempt, $picBatch, $aiResults, &$totalScore) {
                        $subAnswers    = $answerData['sub'] ?? [];
                        $questionMarks = 0;
                        $allCorrect    = true;

                        foreach ($question->subItems as $sub) {
                            $key    = "pic_{$sub->id}";
                            $text   = $picBatch[$key]['answer_text'] ?? strip_tags(trim($subAnswers[$sub->id] ?? ''));
                            $result = $aiResults[$key] ?? ['marks' => 0, 'feedback' => 'AI evaluation failed. Pending manual review.'];

                            $marks     = $result['marks'];
                            $isCorrect = $marks >= $sub->marks;
                            if (!$isCorrect) $allCorrect = false;
                            $questionMarks += $marks;

                            StudentSubAnswer::create([
                                'attempt_id'    => $attempt->id,
                                'sub_item_id'   => $sub->id,
                                'answer_text'   => mb_substr($text, 0, 500),
                                'marks_awarded' => $marks,
                                'ai_feedback'   => $result['feedback'],
                                'ai_evaluated'  => true,
                            ]);
                        }

                        $totalScore += $questionMarks;
                        StudentAnswer::create([
                            'attempt_id'    => $attempt->id,
                            'question_id'   => $question->id,
                            'marks_awarded' => $questionMarks,
                            'ai_evaluated'  => true,
                            'is_correct'    => $allCorrect && $question->subItems->isNotEmpty(),
                        ]);
                    })(),

                    default => null,
                };
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
        $studentId = auth()->id();
        $this->ensureExamAvailableForStudent($exam);

        $attempt = ExamAttempt::where('student_id', $studentId)
            ->where('exam_id', $exam->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->with([
                'answers.question.options',
                'answers.question.correctOption',
                'answers.question.subItems',
                'answers.selectedOption',
            ])
            ->firstOrFail();

        $exam->load('questions.options', 'questions.subItems');

        // Load sub-answers for picture questions, keyed by sub_item_id
        $subAnswers = StudentSubAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('sub_item_id');

        return view('student.exams.result', compact('exam', 'attempt', 'subAnswers'));
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
            }

            StudentAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                ['selected_option_id' => $optionId ?: null]
            );
        }

        return response()->noContent();
    }
}
