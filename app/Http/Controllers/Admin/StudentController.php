<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));

        $students = User::role('student')
            ->with('studentProfile.parent')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhereHas('studentProfile', fn($p) =>
                           $p->where('student_number', 'like', "%{$search}%")
                       );
                });
            })
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'search'));
    }

    public function create(): View
    {
        $parents = User::role('parent')->orderBy('name')->get();
        $classLevels = StudentProfile::CLASS_LEVELS;

        return view('admin.students.create', compact('parents', 'classLevels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'digits:4', 'confirmed'],
            'parent_id' => ['nullable', 'exists:users,id'],
            'class_level' => ['required', Rule::in(StudentProfile::CLASS_LEVELS)],
        ]);

        $studentNumber = null;

        DB::transaction(function () use ($request, &$studentNumber) {
            $user = User::create([
                'name' => $request->name,
                'email' => null,
                'password' => Hash::make(Str::random(16)),
                'is_active' => true,
            ]);
            $user->assignRole('student');

            $studentNumber = StudentNumberGenerator::next($request->class_level);

            StudentProfile::create([
                'user_id' => $user->id,
                'parent_id' => $request->parent_id ?: null,
                'student_number' => $studentNumber,
                'pin' => Hash::make($request->pin),
                'class_level' => $request->class_level,
            ]);
        });

        return redirect()->route('admin.students.index')
            ->with('success', "Student created. Number: {$studentNumber}");
    }

    public function show(User $user): View
    {
        $profile = $user->studentProfile()->with('parent')->first();
        $attempts = ExamAttempt::where('student_id', $user->id)
            ->whereNotNull('submitted_at')
            ->with('exam')
            ->latest('submitted_at')
            ->get();

        $parents = User::role('parent')->orderBy('name')->get();

        return view('admin.students.show', compact('user', 'profile', 'attempts', 'parents'));
    }

    public function edit(User $user): View
    {
        $profile = $user->studentProfile()->with('parent')->first();
        $parents = User::role('parent')->orderBy('name')->get();
        $classLevels = StudentProfile::CLASS_LEVELS;

        return view('admin.students.edit', compact('user', 'profile', 'parents', 'classLevels'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:users,id'],
            'class_level' => ['nullable', Rule::in(StudentProfile::CLASS_LEVELS)],
        ]);

        $user->update(['name' => $request->name]);

        $user->studentProfile()->update([
            'parent_id' => $request->parent_id ?: null,
            'class_level' => $request->class_level ?: null,
        ]);

        return redirect()->route('admin.students.show', $user)
            ->with('success', 'Student updated successfully.');
    }

    public function resetPin(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $user->studentProfile()->update([
            'pin' => Hash::make($request->pin),
        ]);

        return back()->with('success', 'PIN reset successfully.');
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'active' => $user->is_active,
            'message' => $user->is_active ? 'Student activated.' : 'Student deactivated.',
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('student'), 404);
        $user->delete();

        return redirect()->route('admin.students.index')
            ->with('success', 'Student deleted successfully.');
    }
}
