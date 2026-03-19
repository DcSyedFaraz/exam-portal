<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(): View
    {
        $parents = User::role('parent')
            ->withCount('childProfiles')
            ->paginate(15);

        return view('admin.parents.index', compact('parents'));
    }

    public function create(): View
    {
        return view('admin.parents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);
        $user->assignRole('parent');

        return redirect()->route('admin.parents.show', $user)
            ->with('success', 'Parent account created successfully.');
    }

    public function show(User $user): View
    {
        $children = $user->childProfiles()->with('user')->get();

        // Unassigned students (students with no parent)
        $unassignedStudents = User::role('student')
            ->whereHas('studentProfile', fn ($q) => $q->whereNull('parent_id'))
            ->get();

        return view('admin.parents.show', compact('user', 'children', 'unassignedStudents'));
    }

    public function edit(User $user): View
    {
        return view('admin.parents.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'min:8', 'confirmed'],
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.parents.show', $user)
            ->with('success', 'Parent details updated successfully.');
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'active'  => $user->is_active,
            'message' => $user->is_active ? 'Parent activated.' : 'Parent deactivated.',
        ]);
    }

    public function addStudent(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:users,id'],
        ]);

        $profile = StudentProfile::where('user_id', $request->student_id)->first();

        if (!$profile) {
            return back()->with('error', 'Student profile not found.');
        }

        $profile->update(['parent_id' => $user->id]);

        return back()->with('success', 'Student assigned to this parent.');
    }

    public function removeStudent(User $user, StudentProfile $profile): RedirectResponse
    {
        if ($profile->parent_id !== $user->id) {
            return back()->with('error', 'This student does not belong to this parent.');
        }

        $profile->update(['parent_id' => null]);

        return back()->with('success', 'Student removed from this parent.');
    }
}
