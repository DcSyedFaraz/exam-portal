<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        // Student login branch: student_number + PIN
        if ($request->filled('student_number')) {
            $request->validate([
                'student_number' => 'required|string',
                'pin'            => 'required|string',
            ]);

            $profile = StudentProfile::where('student_number', $request->student_number)
                ->with('user')
                ->first();

            if (!$profile || !Hash::check($request->pin, $profile->pin)) {
                throw ValidationException::withMessages([
                    'student_number' => 'Invalid student number or PIN.',
                ]);
            }

            if (!$profile->user->is_active) {
                throw ValidationException::withMessages([
                    'student_number' => 'Your account has been deactivated. Please contact your parent or admin.',
                ]);
            }

            Auth::login($profile->user);
            $request->session()->regenerate();

            return redirect()->route('student.dashboard');
        }

        // Staff/Parent login branch: email + password
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password, 'is_active' => true])) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect or your account is inactive.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('parent')) {
            return redirect()->route('parent.dashboard');
        }

        Auth::guard('web')->logout();
        throw ValidationException::withMessages([
            'email' => 'Your account does not have the required role.',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
