<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Exam $exam): View
    {
        $questions = $exam->questions()->with('options')->get();
        return view('admin.exams.questions', compact('exam', 'questions'));
    }

    public function store(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:mcq,true_false,match',
            'marks'         => 'required|integer|min:1',
            'options'       => 'required|array|min:2',
            'options.*.text'       => 'required|string',
            'options.*.is_correct' => 'required',
        ]);

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

        $question->load('options');

        return response()->json([
            'success'  => true,
            'question' => $question,
            'html'     => view('admin.exams.partials.question-row', compact('question'))->render(),
        ]);
    }

    public function update(Request $request, Question $question): JsonResponse
    {
        $request->validate([
            'question_text' => 'required|string',
            'marks'         => 'required|integer|min:1',
            'options'       => 'required|array|min:2',
            'options.*.text'       => 'required|string',
            'options.*.is_correct' => 'required',
        ]);

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

        $question->load('options');

        return response()->json([
            'success'  => true,
            'question' => $question,
            'html'     => view('admin.exams.partials.question-row', compact('question'))->render(),
        ]);
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();
        return response()->json(['success' => true]);
    }
}
