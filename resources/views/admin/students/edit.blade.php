@extends('layouts.app')

@section('title', 'Edit Student')
@section('page-title', 'Edit Student')
@section('breadcrumb', 'Admin › Students › ' . $user->name . ' › Edit')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-6">Edit Student Details</h3>

        <form method="POST" action="{{ route('admin.students.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="form-input @error('name') border-red-400 @enderror">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Student Number</label>
                <input type="text" value="{{ $profile?->student_number ?? '—' }}"
                       class="form-input bg-gray-50 text-gray-400 font-mono" disabled>
                <p class="text-xs text-gray-400 mt-1">Student number cannot be changed.</p>
            </div>

            <div>
                <label class="form-label">Assigned Parent</label>
                <select name="parent_id" class="form-input @error('parent_id') border-red-400 @enderror">
                    <option value="">— No parent —</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}"
                            {{ old('parent_id', $profile?->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }} ({{ $parent->email }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('admin.students.show', $user) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
