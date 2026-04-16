<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(): View
    {
        $parents = User::role('parent')
            ->withCount('childProfiles')
            ->orderByRaw('CASE WHEN parent_status = ? THEN 0 ELSE 1 END', [User::PARENT_STATUS_PENDING])
            ->latest()
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'parent_status' => User::PARENT_STATUS_APPROVED,
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'min:8', 'confirmed'],
        ]);

        $user->name = $request->name;
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
        if ($user->hasRole('parent') && $user->parent_status === User::PARENT_STATUS_PENDING) {
            return response()->json([
                'active' => false,
                'message' => 'Cannot activate/deactivate a pending parent. Approve first.',
            ], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json([
            'active' => $user->is_active,
            'message' => $user->is_active ? 'Parent activated.' : 'Parent deactivated.',
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('parent'), 404);

        $user->update([
            'parent_status' => User::PARENT_STATUS_APPROVED,
            'is_active' => true,
        ]);

        return back()->with('success', 'Parent approved and activated.');
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('parent'), 404);

        $user->update([
            'parent_status' => User::PARENT_STATUS_REJECTED,
            'is_active' => false,
        ]);

        return back()->with('success', 'Parent rejected and deactivated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('parent'), 404);

        if ($user->childProfiles()->exists()) {
            return back()->with('error', 'Cannot delete this parent while students are still assigned. Remove students first.');
        }

        $user->delete();

        return redirect()->route('admin.parents.index')
            ->with('success', 'Parent deleted successfully.');
    }

    public function addStudent(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:users,id'],
        ]);

        $profile = StudentProfile::where('user_id', $request->student_id)->first();

        if (! $profile) {
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
