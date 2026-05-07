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

    public function store(Request $request, Exam $exam): JsonResponse
    {
        $type = $request->input('question_type');

        if (in_array($type, self::LEGACY_TYPES, true)) {
            return $this->storeLegacy($request, $exam);
        }

        return $this->storeNewType($request, $exam, $type);
    }

    private function storeLegacy(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:mcq,true_false,match',
            'marks'         => 'required|integer|min:1',
            'options'       => 'required|array|min:2',
            'options.*.text'       => 'required|string',
            'options.*.is_correct' => 'required',
        ]);

        $this->validateExactlyOneCorrect((string) $request->question_type, $request->input('options', []));

        DB::transaction(function () use ($request, $exam, &$question) {
            $order = $exam->questions()->max('order') + 1;

            $question = Question::create([
                'exam_id'       => $exam->id,
                'question_text' => $request->question_text,
                'question_type' => $request->question_type,
                'marks'         => $request->marks,
                'order'         => $order,
            ]);

            foreach ($request->options as $opt) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'is_correct'  => (bool) ($opt['is_correct'] ?? false),
                    'match_pair'  => $opt['match_pair'] ?? null,
                ]);
            }
        });

        $question->load('options', 'subItems');

        return response()->json([
            'success'  => true,
            'question' => $question,
            'html'     => view('admin.exams.partials.question-row', compact('question'))->render(),
        ]);
    }

    private function storeNewType(Request $request, Exam $exam, string $type): JsonResponse
    {
        $rules = [
            'question_type' => 'required|in:picture,fill_blank,word_bank,ai_evaluated',
            'marks'         => 'required|integer|min:1',
        ];

        match ($type) {
            'picture' => $rules += [
                'image'                    => 'required|image|max:2048',
                'sub_questions'            => 'required|array|min:1',
                'sub_questions.*'          => 'required|string|max:500',
                'sub_correct_answers'      => 'required|array|min:1',
                'sub_correct_answers.*'    => 'required|string|max:500',
                'sub_marks.*'              => 'required|integer|min:1',
            ],
            'fill_blank' => $rules += [
                'question_text'       => 'required|string|max:1000',
                'correct_answer_text' => 'required|string|max:500',
                'fill_blank_grading'  => 'nullable|in:ai,exact',
            ],
            'word_bank' => $rules += [
                'question_text'   => 'required|string|max:1000',
                'word_bank_items' => 'required|string|max:500',
                'options'         => 'required|array|min:1',
            ],
            'ai_evaluated' => $rules += [
                'question_text'       => 'required|string|max:1000',
                'correct_answer_text' => 'required|string|max:1000',
            ],
            default => null,
        };

        $request->validate($rules);

        $imagePath = null;
        if ($type === 'picture' && $request->hasFile('image')) {
            $imagePath = $request->file('image')->store('question-images', 'public');
        }

        $wordBankItems = null;
        if ($type === 'word_bank') {
            $wordBankItems = array_map('trim', explode(',', $request->word_bank_items));
        }

        DB::transaction(function () use ($request, $exam, $type, $imagePath, $wordBankItems, &$question) {
            $order = $exam->questions()->max('order') + 1;

            $question = Question::create([
                'exam_id'             => $exam->id,
                'question_text'       => $request->input('question_text', ''),
                'question_type'       => $type,
                'marks'               => $request->marks,
                'order'               => $order,
                'image_path'          => $imagePath,
                'correct_answer_text' => $request->correct_answer_text,
                'word_bank_items'     => $wordBankItems,
                'ai_max_marks'        => $type === 'ai_evaluated' ? $request->marks : null,
                'fill_blank_grading'  => $type === 'fill_blank' ? ($request->fill_blank_grading ?? 'exact') : null,
            ]);

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
                    $question->options()->create([
                        'option_text' => $opt['statement'],
                        'match_pair'  => $opt['correct_word'],
                        'is_correct'  => true,
                    ]);
                }
            }
        });

        $question->load('options', 'subItems');

        return response()->json([
            'success'  => true,
            'question' => $question,
            'html'     => view('admin.exams.partials.question-row', compact('question'))->render(),
        ]);
    }

    public function update(Request $request, Question $question): JsonResponse
    {
        if (in_array($question->question_type, self::LEGACY_TYPES, true)) {
            return $this->updateLegacy($request, $question);
        }

        return $this->updateNewType($request, $question);
    }

    private function updateLegacy(Request $request, Question $question): JsonResponse
    {
        $request->validate([
            'question_text' => 'required|string',
            'marks'         => 'required|integer|min:1',
            'options'       => 'required|array|min:2',
            'options.*.text'       => 'required|string',
            'options.*.is_correct' => 'required',
        ]);

        $this->validateExactlyOneCorrect((string) $question->question_type, $request->input('options', []));

        DB::transaction(function () use ($request, $question) {
            $question->update([
                'question_text' => $request->question_text,
                'marks'         => $request->marks,
            ]);

            $question->options()->delete();

            foreach ($request->options as $opt) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'is_correct'  => (bool) ($opt['is_correct'] ?? false),
                    'match_pair'  => $opt['match_pair'] ?? null,
                ]);
            }
        });

        $question->load('options', 'subItems');

        return response()->json([
            'success'  => true,
            'question' => $question,
            'html'     => view('admin.exams.partials.question-row', compact('question'))->render(),
        ]);
    }

    private function updateNewType(Request $request, Question $question): JsonResponse
    {
        $type = $question->question_type;

        $rules = ['marks' => 'required|integer|min:1'];

        match ($type) {
            'picture' => $rules += [
                'image'               => 'nullable|image|max:2048',
                'sub_questions'       => 'required|array|min:1',
                'sub_questions.*'     => 'required|string|max:500',
                'sub_correct_answers' => 'required|array|min:1',
                'sub_correct_answers.*' => 'required|string|max:500',
                'sub_marks.*'         => 'required|integer|min:1',
            ],
            'fill_blank' => $rules += [
                'question_text'       => 'required|string|max:1000',
                'correct_answer_text' => 'required|string|max:500',
                'fill_blank_grading'  => 'nullable|in:ai,exact',
            ],
            'word_bank' => $rules += [
                'question_text'   => 'required|string|max:1000',
                'word_bank_items' => 'required|string|max:500',
                'options'         => 'required|array|min:1',
            ],
            'ai_evaluated' => $rules += [
                'question_text'       => 'required|string|max:1000',
                'correct_answer_text' => 'required|string|max:1000',
            ],
            default => null,
        };

        $request->validate($rules);

        DB::transaction(function () use ($request, $question, $type) {
            $updates = [
                'question_text' => $request->input('question_text', $question->question_text),
                'marks'         => $request->marks,
            ];

            if ($type === 'picture' && $request->hasFile('image')) {
                if ($question->image_path) {
                    Storage::disk('public')->delete($question->image_path);
                }
                $updates['image_path'] = $request->file('image')->store('question-images', 'public');
            }

            if (in_array($type, ['fill_blank', 'ai_evaluated'])) {
                $updates['correct_answer_text'] = $request->correct_answer_text;
            }

            if ($type === 'fill_blank') {
                $updates['fill_blank_grading'] = $request->fill_blank_grading ?? 'exact';
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
                    $question->options()->create([
                        'option_text' => $opt['statement'],
                        'match_pair'  => $opt['correct_word'],
                        'is_correct'  => true,
                    ]);
                }
            }
        });

        $question->load('options', 'subItems');

        return response()->json([
            'success'  => true,
            'question' => $question,
            'html'     => view('admin.exams.partials.question-row', compact('question'))->render(),
        ]);
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
