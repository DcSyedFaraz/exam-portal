<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $exams = Exam::withCount('questions')->latest()->paginate(15);
        return view('admin.exams.index', compact('exams'));
    }

    public function create(): View
    {
        return view('admin.exams.create');
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = Exam::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.exams.questions', $exam)
            ->with('success', 'Exam created! Now add questions.');
    }

    public function edit(Exam $exam): View
    {
        return view('admin.exams.edit', compact('exam'));
    }

    public function update(StoreExamRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->validated());
        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();
        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam deleted.');
    }

    public function togglePublish(Exam $exam): JsonResponse
    {
        $exam->update(['is_published' => !$exam->is_published]);
        return response()->json([
            'published' => $exam->is_published,
            'message'   => $exam->is_published ? 'Exam published.' : 'Exam unpublished.',
        ]);
    }
}
