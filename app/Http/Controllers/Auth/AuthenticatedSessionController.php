<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        Log::info('Login: attempt', [
            'ip' => $request->ip(),
            'is_student' => $request->filled('student_number'),
        ]);

        // Student login branch: student_number + PIN
        if ($request->filled('student_number')) {
            try {
                $request->validate([
                    'student_number' => 'required|string',
                    'pin' => 'required|string',
                ]);
            } catch (ValidationException $e) {
                Log::warning('Login: failed', [
                    'reason' => 'form_validation',
                    'context' => 'student',
                    'errors' => $e->errors(),
                ]);
                throw $e;
            }

            Log::info('Login: student branch', [
                'student_number' => $request->string('student_number')->toString(),
            ]);

            $profile = StudentProfile::where('student_number', $request->student_number)
                ->with('user')
                ->first();

            if (! $profile || ! Hash::check($request->pin, $profile->pin)) {
                Log::warning('Login: failed', [
                    'reason' => 'invalid_student_or_pin',
                    'student_number' => $request->string('student_number')->toString(),
                ]);
                throw ValidationException::withMessages([
                    'student_number' => 'Invalid student number or PIN.',
                ]);
            }

            if (! $profile->user->is_active) {
                Log::warning('Login: failed', [
                    'reason' => 'student_inactive',
                    'user_id' => $profile->user->id,
                ]);
                throw ValidationException::withMessages([
                    'student_number' => 'Your account has been deactivated. Please contact your parent or admin.',
                ]);
            }

            Auth::login($profile->user);
            $request->session()->regenerate();

            Log::info('Login: success', [
                'user_id' => $profile->user->id,
                'role' => 'student',
                'redirect' => 'student.dashboard',
            ]);

            return redirect()->route('student.dashboard');
        }

        // Staff/Parent login branch: email + password
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::warning('Login: failed', [
                'reason' => 'form_validation',
                'context' => 'staff',
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        $email = $request->string('email')->toString();
        Log::info('Login: email branch', ['email' => $email]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::warning('Login: failed', [
                'reason' => 'invalid_email_or_password',
                'email' => $email,
            ]);
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        // Only admin/parent can use email login
        if (! $user->hasRole('admin') && ! $user->hasRole('parent')) {
            Log::warning('Login: failed', [
                'reason' => 'wrong_role_for_email_login',
                'user_id' => $user->id,
                'email' => $email,
            ]);
            throw ValidationException::withMessages([
                'email' => 'Your account does not have the required role.',
            ]);
        }

        if ($user->hasRole('parent')) {
            $status = $user->parent_status;
            if ($status === User::PARENT_STATUS_PENDING) {
                Log::warning('Login: failed', [
                    'reason' => 'parent_pending',
                    'user_id' => $user->id,
                    'email' => $email,
                ]);
                throw ValidationException::withMessages([
                    'email' => 'Your parent account is pending admin approval.',
                ]);
            }
            if ($status === User::PARENT_STATUS_REJECTED) {
                Log::warning('Login: failed', [
                    'reason' => 'parent_rejected',
                    'user_id' => $user->id,
                    'email' => $email,
                ]);
                throw ValidationException::withMessages([
                    'email' => 'Your parent account request was rejected. Please contact admin.',
                ]);
            }
        }

        if (! $user->is_active) {
            Log::warning('Login: failed', [
                'reason' => 'user_inactive',
                'user_id' => $user->id,
                'email' => $email,
            ]);
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact admin.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->hasRole('admin')) {
            Log::info('Login: success', [
                'user_id' => $user->id,
                'role' => 'admin',
                'redirect' => 'admin.dashboard',
            ]);

            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('parent')) {
            Log::info('Login: success', [
                'user_id' => $user->id,
                'role' => 'parent',
                'redirect' => 'parent.dashboard',
            ]);

            return redirect()->route('parent.dashboard');
        }

        Auth::guard('web')->logout();
        Log::warning('Login: failed', [
            'reason' => 'no_matching_role_after_checks',
            'user_id' => $user->id,
            'email' => $email,
        ]);
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
