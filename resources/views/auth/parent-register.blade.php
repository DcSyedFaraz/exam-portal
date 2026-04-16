@extends('layouts.auth')

@section('title', 'Parent Registration')

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
                Create your parent account request
            </div>
            <div class="flex items-center gap-3 text-sm">
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Admin will approve before you can log in
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
            <h2 class="text-2xl font-bold font-heading text-gray-900 mb-2">Parent Registration</h2>
            <p class="text-gray-500 text-sm mb-6">Submit your details. After admin approval you can log in.</p>

            <form method="POST" action="{{ route('parent.register.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-input @error('name') border-red-400 @enderror"
                           placeholder="e.g. Jane Smith" autocomplete="name" autofocus>
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-input @error('email') border-red-400 @enderror"
                           placeholder="parent@example.com" autocomplete="email">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password"
                           class="form-input @error('password') border-red-400 @enderror"
                           placeholder="Minimum 8 characters" autocomplete="new-password">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation"
                           class="form-input"
                           placeholder="Re-enter password" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-primary w-full justify-center py-3">
                    Submit for Approval
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-yellow-700 hover:underline">
                    ← Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

