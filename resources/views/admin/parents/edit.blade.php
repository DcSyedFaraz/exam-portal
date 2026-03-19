@extends('layouts.app')

@section('title', 'Edit Parent')
@section('page-title', 'Edit Parent')
@section('breadcrumb', 'Admin › Parents › ' . $user->name . ' › Edit')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-6">Edit Parent Details</h3>

        <form method="POST" action="{{ route('admin.parents.update', $user) }}" class="space-y-4">
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
            <p class="text-xs text-gray-400">Leave password fields blank to keep the current password.</p>

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

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('admin.parents.show', $user) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
