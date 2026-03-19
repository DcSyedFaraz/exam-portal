@extends('layouts.app')

@section('title', 'Edit Profile')
@section('page-title', 'My Profile')
@section('breadcrumb', 'Parent › Profile')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-xl">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h3 class="text-base font-semibold font-heading text-gray-900">{{ $user->name }}</h3>
                <p class="text-sm text-gray-500 capitalize">{{ $user->getRoleNames()->first() }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('parent.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="form-input @error('name') border-red-400 @enderror">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="form-input @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <hr class="border-gray-100">
            <p class="text-xs text-gray-400">Leave password fields blank to keep your current password.</p>

            <div>
                <label class="form-label">New Password</label>
                <input type="password" name="password"
                       class="form-input @error('password') border-red-400 @enderror"
                       placeholder="Minimum 8 characters">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation"
                       class="form-input" placeholder="Repeat new password">
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
