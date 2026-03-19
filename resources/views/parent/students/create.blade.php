@extends('layouts.app')

@section('title', 'Add Student')
@section('page-title', 'Add Student')
@section('breadcrumb', 'Parent › Add Student')

@section('content')
<div class="max-w-lg">

    {{-- Success Result Card --}}
    @if(session('student_created'))
    @php $created = session('student_created'); @endphp
    <div class="bg-yellow-400 rounded-xl p-6 mb-6 shadow-sm">
        <div class="flex items-center gap-3 mb-3">
            <span class="text-3xl">🎉</span>
            <h3 class="text-lg font-bold font-heading text-gray-900">Student Created!</h3>
        </div>
        <p class="text-gray-800 text-sm mb-3">Share these credentials with <strong>{{ $created['name'] }}</strong>:</p>
        <div class="bg-white/70 rounded-lg p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">Student Number</span>
                <span class="font-mono font-bold text-gray-900 text-sm">{{ $created['student_number'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">PIN</span>
                <span class="font-mono font-bold text-gray-900">••••</span>
            </div>
        </div>
        <p class="text-xs text-gray-700 mt-3">⚠️ Save the PIN — it cannot be retrieved after this screen.</p>
    </div>
    @endif

    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-5">Create a New Student Account</h3>

        <form method="POST" action="{{ route('parent.students.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">Student Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-input @error('name') border-red-400 @enderror"
                       placeholder="e.g. John Smith">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">4-Digit PIN <span class="text-red-500">*</span></label>
                <input type="password" name="pin"
                       class="form-input @error('pin') border-red-400 @enderror"
                       placeholder="••••" maxlength="4" inputmode="numeric">
                <p class="text-xs text-gray-400 mt-1">Student will use this PIN to log in.</p>
                @error('pin') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Confirm PIN <span class="text-red-500">*</span></label>
                <input type="password" name="pin_confirmation"
                       class="form-input"
                       placeholder="••••" maxlength="4" inputmode="numeric">
                @error('pin_confirmation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Student
                </button>
                <a href="{{ route('parent.students.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
