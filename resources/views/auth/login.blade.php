@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex">

    {{-- Left Panel (hidden on mobile) --}}
    <div class="hidden lg:flex w-1/2 bg-yellow-400 flex-col items-center justify-center p-12">
        <span class="text-7xl mb-6">{{ config('app_settings.logo_icon') }}</span>
        <h1 class="text-4xl font-bold font-heading text-gray-900 text-center">{{ config('app_settings.name') }}</h1>
        <p class="text-gray-700 mt-3 text-lg text-center">{{ config('app_settings.tagline') }}</p>
        <div class="mt-12 space-y-3 text-gray-800">
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Timed exams with instant results
            </div>
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                MCQ, True/False, and Match questions
            </div>
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Parent & student progress tracking
            </div>
        </div>
    </div>

    {{-- Right Panel --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-6 lg:p-12">

        {{-- Mobile logo --}}
        <div class="lg:hidden absolute top-0 left-0 right-0 bg-yellow-400 py-5 text-center">
            <h1 class="text-2xl font-bold font-heading text-gray-900">{{ config('app_settings.name') }}</h1>
            <p class="text-sm text-gray-700">{{ config('app_settings.tagline') }}</p>
        </div>

        <div class="w-full max-w-md mt-24 lg:mt-0">

            <h2 class="text-2xl font-bold font-heading text-gray-900 mb-2">Welcome back</h2>
            <p class="text-gray-500 text-sm mb-8">Sign in to your account to continue</p>

            {{-- Tab Switcher --}}
            <div class="flex rounded-xl bg-gray-100 p-1 mb-6">
                <button id="tab-staff"
                    onclick="switchTab('staff')"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all duration-200 bg-white text-gray-900 shadow-sm">
                    Staff / Parent
                </button>
                <button id="tab-student"
                    onclick="switchTab('student')"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all duration-200 text-gray-500">
                    Student
                </button>
            </div>

            {{-- Staff / Parent Form --}}
            <form id="form-staff" method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-input @error('email') border-red-400 @enderror"
                           placeholder="admin@exam.com" autocomplete="email">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password"
                           class="form-input"
                           placeholder="••••••••" autocomplete="current-password">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary w-full justify-center py-3">
                    Sign In
                </button>
            </form>

            {{-- Student Form --}}
            <form id="form-student" method="POST" action="{{ route('login') }}" class="space-y-4 hidden">
                @csrf
                <div>
                    <label class="form-label">Student Number</label>
                    <input type="text" name="student_number" value="{{ old('student_number') }}"
                           class="form-input @error('student_number') border-red-400 @enderror"
                           placeholder="STU-YYYYMMDD-XXXX" autocomplete="off">
                    @error('student_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="form-label">4-Digit PIN</label>
                    <input type="password" name="pin"
                           class="form-input"
                           placeholder="••••" maxlength="4" inputmode="numeric" autocomplete="off">
                    @error('pin')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary w-full justify-center py-3">
                    Sign In
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-8">
                {{ config('app_settings.name') }} v{{ config('app_settings.version') }}
            </p>

        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    const isStaff = tab === 'staff';
    document.getElementById('form-staff').classList.toggle('hidden', !isStaff);
    document.getElementById('form-student').classList.toggle('hidden', isStaff);

    const tabStaff   = document.getElementById('tab-staff');
    const tabStudent = document.getElementById('tab-student');

    if (isStaff) {
        tabStaff.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
        tabStaff.classList.remove('text-gray-500');
        tabStudent.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
        tabStudent.classList.add('text-gray-500');
    } else {
        tabStudent.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
        tabStudent.classList.remove('text-gray-500');
        tabStaff.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
        tabStaff.classList.add('text-gray-500');
    }
}

// Auto-switch to student tab if student_number was submitted
@if(old('student_number'))
    document.addEventListener('DOMContentLoaded', function() { switchTab('student'); });
@endif
</script>
@endsection
