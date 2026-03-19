@extends('layouts.app')

@section('title', 'Add Parent')
@section('page-title', 'Add Parent')
@section('breadcrumb', 'Admin › Parents › Add')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-6">Create Parent Account</h3>

        <form method="POST" action="{{ route('admin.parents.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-input @error('name') border-red-400 @enderror"
                       placeholder="e.g. Jane Smith" autofocus>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-input @error('email') border-red-400 @enderror"
                       placeholder="parent@example.com">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password"
                       class="form-input @error('password') border-red-400 @enderror"
                       placeholder="Minimum 8 characters">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation"
                       class="form-input"
                       placeholder="Repeat password">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Create Parent</button>
                <a href="{{ route('admin.parents.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
