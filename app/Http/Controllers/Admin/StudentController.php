<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = User::role('student')
            ->with('studentProfile.parent')
            ->paginate(15);

        return view('admin.students.index', compact('students'));
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);
        return response()->json([
            'active'  => $user->is_active,
            'message' => $user->is_active ? 'Student activated.' : 'Student deactivated.',
        ]);
    }
}
