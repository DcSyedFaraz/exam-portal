<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;

class DashboardController extends Controller
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

        $availableExams = Exam::where('is_published', true)->where($classFilter)->count();
        $examsTaken     = ExamAttempt::where('student_id', $studentId)->whereNotNull('submitted_at')->count();
        $examsPassed    = ExamAttempt::where('student_id', $studentId)->where('is_passed', true)->whereNotNull('submitted_at')->count();
        $avgScore       = ExamAttempt::where('student_id', $studentId)->whereNotNull('submitted_at')->avg('score') ?? 0;

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

        return view('student.dashboard', compact(
            'availableExams', 'examsTaken', 'examsPassed', 'avgScore', 'exams'
        ));
    }
}
