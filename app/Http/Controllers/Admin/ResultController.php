<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $exams = Exam::orderBy('title')->get();

        $attempts = ExamAttempt::with(['student', 'exam'])
            ->whereNotNull('submitted_at')
            ->when($request->exam_id, fn ($q) => $q->where('exam_id', $request->exam_id))
            ->when($request->from,    fn ($q) => $q->whereDate('submitted_at', '>=', $request->from))
            ->when($request->to,      fn ($q) => $q->whereDate('submitted_at', '<=', $request->to))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.results.index', compact('attempts', 'exams'));
    }
}
