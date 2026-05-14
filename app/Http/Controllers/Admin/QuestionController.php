<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Services\ExamImport\QuestionPayloadFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionController extends Controller
{
    private const LEGACY_TYPES = ['mcq', 'true_false', 'match'];

    protected function validateExactlyOneCorrect(string $questionType, array $options): void
    {
        try {
            QuestionPayloadFactory::assertExactlyOneCorrect($questionType, $options);
        } catch (ValidationException $e) {
            abort(response()->json([
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422));
        }
    }

    public function index(Exam $exam): View
    {
        $questions = $exam->questions()->with('options', 'subItems')->get();

        return view('admin.exams.questions', compact('exam', 'questions'));
    }

    public function create(Exam $exam): View
    {
        return view('admin.exams.question-form', compact('exam'));
    }

    public function edit(Question $question): View
    {
        $question->load('options', 'subItems');
        $exam = $question->exam;
        return view('admin.exams.question-form', compact('exam', 'question'));
    }

    public function store(Request $request, Exam $exam): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $type = $request->input('question_type');

        if (in_array($type, self::LEGACY_TYPES, true)) {
            return $this->storeLegacy($request, $exam);
        }

        return $this->storeNewType($request, $exam, $type);
    }

    public function update(Request $request, Question $question): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        if (in_array($question->question_type, self::LEGACY_TYPES, true)) {
            return $this->updateLegacy($request, $question);
        }

        return $this->updateNewType($request, $question);
    }

    private function storeLegacy(Request $request, Exam $exam): \Illuminate\Http\RedirectResponse
    {
        $type         = $request->input('question_type');
        $correctIndex = (int) $request->input('correct_option_mcq', 0);

        $request->validate([
            'question_text'        => 'required|string',
            'question_type'        => 'required|in:mcq,true_false,match',
            'marks'                => 'required|integer|min:1',
            'options'              => 'required|array|min:2',
            'options.*.text'       => 'required|string',
            'options.*.is_correct' => 'present',
        ], [
            'options.min' => 'Please provide at least 2 options.',
        ], [
            'question_text'        => 'question text',
            'question_type'        => 'question type',
            'marks'                => 'marks',
            'options'              => 'options',
            'options.*.text'       => 'option text',
            'options.*.is_correct' => 'correct answer selection',
        ]);

        DB::transaction(function () use ($request, $exam, $type, $correctIndex, &$question) {
            $order = $exam->questions()->max('order') + 1;

            $question = Question::create([
                'exam_id'       => $exam->id,
                'question_text' => $request->question_text,
                'question_type' => $type,
                'marks'         => $request->marks,
                'order'         => $order,
            ]);

            foreach ($request->options as $i => $opt) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    // For MCQ/TF: determine correct from radio index; for match: use is_correct flag
                    'is_correct'  => $type === 'match'
                        ? (bool) ($opt['is_correct'] ?? false)
                        : ($i === $correctIndex),
                    'match_pair'  => $opt['match_pair'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.exams.questions', $exam)
            ->with('success', 'Question added successfully.');
    }

    private function storeNewType(Request $request, Exam $exam, string $type): \Illuminate\Http\RedirectResponse
    {

        $rules = [
            'question_type' => 'required|in:picture,fill_blank,word_bank,ai_evaluated',
            'marks'         => 'required|integer|min:1',
        ];

        match ($type) {
            'picture' => $rules += [
                'image'                 => 'required|image|max:2048',
                'sub_questions'         => 'required|array|min:1',
                'sub_questions.*'       => 'required|string|max:500',
                'sub_correct_answers'   => 'required|array|min:1',
                'sub_correct_answers.*' => 'required|string|max:500',
                'sub_marks.*'           => 'required|integer|min:1',
            ],
            'fill_blank' => $rules += [
                'fb_question_text' => 'required|string|max:1000',
                'fb_answers'       => 'required|array|min:1',
                'fb_answers.*'     => 'required|string|max:500',
            ],
            'word_bank' => $rules += [
                'wb_question_text' => 'required|string|max:1000',
                'word_bank_items'  => 'required|string|max:500',
                'options'          => 'required|array|min:1',
            ],
            'ai_evaluated' => $rules += [
                'question_text'    => 'required|string|max:1000',
                'ai_correct_answer'=> 'required|string|max:1000',
            ],
            default => null,
        };

        $request->validate($rules, [], [
            'question_type'         => 'question type',
            'marks'                 => 'marks',
            'image'                 => 'image',
            'sub_questions'         => 'sub-questions',
            'sub_questions.*'       => 'sub-question text',
            'sub_correct_answers'   => 'sub-question answers',
            'sub_correct_answers.*' => 'correct answer',
            'sub_marks.*'           => 'marks per sub-question',
            'fb_question_text'      => 'question text',
            'fb_answers'            => 'correct answers',
            'fb_answers.*'          => 'answer',
            'wb_question_text'      => 'question text',
            'word_bank_items'       => 'word bank items',
            'options'               => 'question items',
            'ai_correct_answer'     => 'model answer',
        ]);

        $imagePath = null;
        if ($type === 'picture' && $request->hasFile('image')) {
            $imagePath = $request->file('image')->store('question-images', 'public');
        }

        $wordBankItems = null;
        if ($type === 'word_bank') {
            $wordBankItems = array_map('trim', explode(',', $request->word_bank_items));
        }

        // Resolve question_text and correct_answer_text from type-specific field names
        $questionText = match($type) {
            'fill_blank'   => $request->fb_question_text,
            'word_bank'    => $request->wb_question_text,
            'picture'      => $request->input('pic_question_text', ''),
            default        => $request->input('question_text', ''),
        };
        $correctAnswerText = match($type) {
            'fill_blank'   => implode('|', array_map('trim', $request->input('fb_answers', []))),
            'ai_evaluated' => $request->ai_correct_answer,
            default        => null,
        };

        DB::transaction(function () use ($request, $exam, $type, $imagePath, $wordBankItems, $questionText, $correctAnswerText, &$question) {
            $order = $exam->questions()->max('order') + 1;

            $attrs = [
                'exam_id'             => $exam->id,
                'question_text'       => $questionText,
                'question_type'       => $type,
                'marks'               => $request->marks,
                'order'               => $order,
                'image_path'          => $imagePath,
                'correct_answer_text' => $correctAnswerText,
                'word_bank_items'     => $wordBankItems,
                'ai_max_marks'        => $type === 'ai_evaluated' ? $request->marks : null,
            ];

            if ($type === 'fill_blank') {
                $attrs['fill_blank_grading'] = 'exact';
            }

            $question = Question::create($attrs);

            if ($type === 'picture') {
                foreach ($request->sub_questions as $i => $subQ) {
                    $question->subItems()->create([
                        'label'             => $request->sub_labels[$i] ?? chr(97 + $i),
                        'sub_question_text' => $subQ,
                        'correct_answer'    => $request->sub_correct_answers[$i],
                        'marks'             => $request->sub_marks[$i],
                        'order'             => $i,
                    ]);
                }
            }

            if ($type === 'word_bank') {
                foreach ($request->options as $opt) {
                    if (!isset($opt['statement'])) continue;
                    $question->options()->create([
                        'option_text' => $opt['statement'],
                        'match_pair'  => $opt['correct_word'] ?? '',
                        'is_correct'  => true,
                    ]);
                }
            }
        });

        return redirect()->route('admin.exams.questions', $exam)
            ->with('success', 'Question added successfully.');
    }

    private function updateLegacy(Request $request, Question $question): \Illuminate\Http\RedirectResponse
    {
        $type         = $question->question_type;
        $correctIndex = (int) $request->input('correct_option_mcq', 0);

        $request->validate([
            'question_text'        => 'required|string',
            'marks'                => 'required|integer|min:1',
            'options'              => 'required|array|min:2',
            'options.*.text'       => 'required|string',
            'options.*.is_correct' => 'present',
        ], [
            'options.min' => 'Please provide at least 2 options.',
        ], [
            'question_text'        => 'question text',
            'marks'                => 'marks',
            'options'              => 'options',
            'options.*.text'       => 'option text',
            'options.*.is_correct' => 'correct answer selection',
        ]);

        DB::transaction(function () use ($request, $question, $type, $correctIndex) {
            $question->update([
                'question_text' => $request->question_text,
                'marks'         => $request->marks,
            ]);

            $question->options()->delete();

            foreach ($request->options as $i => $opt) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'is_correct'  => $type === 'match'
                        ? (bool) ($opt['is_correct'] ?? false)
                        : ($i === $correctIndex),
                    'match_pair'  => $opt['match_pair'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.exams.questions', $question->exam_id)
            ->with('success', 'Question updated successfully.');
    }

    private function updateNewType(Request $request, Question $question): \Illuminate\Http\RedirectResponse
    {
        $type = $question->question_type;

        $rules = ['marks' => 'required|integer|min:1'];

        match ($type) {
            'picture' => $rules += [
                'image'                 => 'nullable|image|max:2048',
                'sub_questions'         => 'required|array|min:1',
                'sub_questions.*'       => 'required|string|max:500',
                'sub_correct_answers'   => 'required|array|min:1',
                'sub_correct_answers.*' => 'required|string|max:500',
                'sub_marks.*'           => 'required|integer|min:1',
            ],
            'fill_blank' => $rules += [
                'fb_question_text' => 'required|string|max:1000',
                'fb_answers'       => 'required|array|min:1',
                'fb_answers.*'     => 'required|string|max:500',
            ],
            'word_bank' => $rules += [
                'wb_question_text' => 'required|string|max:1000',
                'word_bank_items'  => 'required|string|max:500',
                'options'          => 'required|array|min:1',
            ],
            'ai_evaluated' => $rules += [
                'question_text'     => 'required|string|max:1000',
                'ai_correct_answer' => 'required|string|max:1000',
            ],
            default => null,
        };

        $request->validate($rules, [], [
            'marks'                 => 'marks',
            'image'                 => 'image',
            'sub_questions'         => 'sub-questions',
            'sub_questions.*'       => 'sub-question text',
            'sub_correct_answers'   => 'sub-question answers',
            'sub_correct_answers.*' => 'correct answer',
            'sub_marks.*'           => 'marks per sub-question',
            'fb_question_text'      => 'question text',
            'fb_answers'            => 'correct answers',
            'fb_answers.*'          => 'answer',
            'wb_question_text'      => 'question text',
            'word_bank_items'       => 'word bank items',
            'options'               => 'question items',
            'ai_correct_answer'     => 'model answer',
        ]);

        DB::transaction(function () use ($request, $question, $type) {
            $updates = [
                'question_text' => match($type) {
                    'fill_blank'   => $request->fb_question_text,
                    'word_bank'    => $request->wb_question_text,
                    'picture'      => $request->input('pic_question_text', $question->question_text),
                    default        => $request->input('question_text', $question->question_text),
                },
                'marks' => $request->marks,
            ];

            if ($type === 'picture' && $request->hasFile('image')) {
                if ($question->image_path) {
                    Storage::disk('public')->delete($question->image_path);
                }
                $updates['image_path'] = $request->file('image')->store('question-images', 'public');
            }

            if ($type === 'fill_blank') {
                $updates['correct_answer_text'] = implode('|', array_map('trim', $request->input('fb_answers', [])));
                $updates['fill_blank_grading']  = 'exact';
            }

            if ($type === 'ai_evaluated') {
                $updates['correct_answer_text'] = $request->ai_correct_answer;
            }

            if ($type === 'word_bank') {
                $updates['word_bank_items'] = array_map('trim', explode(',', $request->word_bank_items));
            }

            if ($type === 'ai_evaluated') {
                $updates['ai_max_marks'] = $request->marks;
            }

            $question->update($updates);

            if ($type === 'picture') {
                $question->subItems()->delete();
                foreach ($request->sub_questions as $i => $subQ) {
                    $question->subItems()->create([
                        'label'             => $request->sub_labels[$i] ?? chr(97 + $i),
                        'sub_question_text' => $subQ,
                        'correct_answer'    => $request->sub_correct_answers[$i],
                        'marks'             => $request->sub_marks[$i],
                        'order'             => $i,
                    ]);
                }
            }

            if ($type === 'word_bank') {
                $question->options()->delete();
                foreach ($request->options as $opt) {
                    if (!isset($opt['statement'])) continue;
                    $question->options()->create([
                        'option_text' => $opt['statement'],
                        'match_pair'  => $opt['correct_word'] ?? '',
                        'is_correct'  => true,
                    ]);
                }
            }
        });

        return redirect()->route('admin.exams.questions', $question->exam_id)
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): JsonResponse
    {
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        $question->delete();

        return response()->json(['success' => true]);
    }
}
