<?php

namespace App\Http\Controllers\ParentPanel;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;

class DashboardController extends Controller
{
    public function index()
    {
        $children = auth()->user()->childProfiles()->with('user')->get();

        $studentIds = $children->pluck('user_id');

        $totalAttempts = ExamAttempt::whereIn('student_id', $studentIds)
            ->whereNotNull('submitted_at')
            ->count();

        $avgScore = ExamAttempt::whereIn('student_id', $studentIds)
            ->whereNotNull('submitted_at')
            ->avg('score') ?? 0;

        // Latest attempt per child
        $latestAttempts = [];
        foreach ($children as $child) {
            $latestAttempts[$child->user_id] = ExamAttempt::where('student_id', $child->user_id)
                ->whereNotNull('submitted_at')
                ->with('exam')
                ->latest('submitted_at')
                ->first();
        }

        return view('parent.dashboard', compact(
            'children', 'totalAttempts', 'avgScore', 'latestAttempts'
        ));
    }
}
