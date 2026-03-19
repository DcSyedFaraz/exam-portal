<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;

class ResultController extends Controller
{
    public function index()
    {
        $attempts = ExamAttempt::where('student_id', auth()->id())
            ->whereNotNull('submitted_at')
            ->with('exam')
            ->latest('submitted_at')
            ->get()
            ->unique('exam_id') // Only latest per exam
            ->values();

        return view('student.results.index', compact('attempts'));
    }
}
