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
            {{-- Live features --}}
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Timed exams with instant results
            </div>
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                MCQ, True/False, and Match questions
            </div>
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Parent &amp; student progress tracking
            </div>

            {{-- Coming Soon --}}
            <div class="mt-4 pt-4 border-t border-gray-900/15">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-600 mb-2">Coming Soon</p>
                <div class="space-y-2">
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <span class="text-base leading-none">⏳</span>
                        Fill in the Blanks
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <span class="text-base leading-none">⏳</span>
                        Short Answer
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <span class="text-base leading-none">⏳</span>
                        Structured / Essay + Calculations
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact info --}}
        <div class="mt-10 w-full max-w-sm border-t border-gray-900/15 pt-6">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-700 mb-3">Contact</p>
            <div class="rounded-xl border border-gray-900/15 bg-gray-900/6 px-4 py-4 space-y-3.5 shadow-sm">
                <div class="flex items-start gap-3 text-sm text-gray-900">
                    <span class="mt-0.5 inline-flex shrink-0 rounded-md bg-gray-900/10 p-1.5 text-gray-900" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <span class="font-medium leading-snug">Dodoma, Tanzania</span>
                </div>
                <div class="flex items-start gap-3 text-sm">
                    <span class="mt-0.5 inline-flex shrink-0 rounded-md bg-gray-900/10 p-1.5 text-gray-900" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </span>
                    <a href="tel:+255744950150" class="font-medium text-gray-900 underline decoration-gray-900/30 underline-offset-2 hover:decoration-gray-900">+255 744 950 150</a>
                </div>
                <div class="flex items-start gap-3 text-sm">
                    <span class="mt-0.5 inline-flex shrink-0 rounded-md bg-gray-900/10 p-1.5 text-gray-900" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <a href="mailto:info@rmstechnology.co.tz" class="font-medium text-gray-900 break-all underline decoration-gray-900/30 underline-offset-2 hover:decoration-gray-900">info@rmstechnology.co.tz</a>
                </div>
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

            <div id="staff-extra" class="mt-4 text-center">
                <a href="{{ route('parent.register') }}" class="text-sm text-yellow-700 hover:underline">
                    Register as Parent (Admin approval required)
                </a>
            </div>

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
    document.getElementById('staff-extra').classList.toggle('hidden', !isStaff);

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
