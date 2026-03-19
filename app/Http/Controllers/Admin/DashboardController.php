<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalExams    = Exam::count();
        $totalStudents = User::role('student')->count();
        $totalParents  = User::role('parent')->count();
        $todayAttempts = ExamAttempt::whereDate('submitted_at', today())->count();

        $recentAttempts = ExamAttempt::with(['student', 'exam'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalExams', 'totalStudents', 'totalParents', 'todayAttempts', 'recentAttempts'
        ));
    }
}
