@extends('layouts.app')

@section('title', 'Create Exam')
@section('page-title', 'Create Exam')
@section('breadcrumb', 'Admin › Exams › Create')

@section('content')
<div class="max-w-2xl">
    <div class="card">
        <form method="POST" action="{{ route('admin.exams.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">Exam Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="form-input @error('title') border-red-400 @enderror"
                       placeholder="e.g. General Knowledge Quiz">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3"
                          class="form-input @error('description') border-red-400 @enderror"
                          placeholder="Optional exam description...">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Class Level</label>
                <select name="class_level" class="form-input @error('class_level') border-red-400 @enderror">
                    <option value="">— All classes (no restriction) —</option>
                    @foreach($classLevels as $level)
                        <option value="{{ $level }}" {{ old('class_level') === $level ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Leave blank to make this exam visible to all students.</p>
                @error('class_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Duration (minutes) <span class="text-red-500">*</span></label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 30) }}"
                           class="form-input @error('duration_minutes') border-red-400 @enderror"
                           min="1" max="300">
                    @error('duration_minutes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Total Marks <span class="text-red-500">*</span></label>
                    <input type="number" name="total_marks" value="{{ old('total_marks', 10) }}"
                           class="form-input @error('total_marks') border-red-400 @enderror" min="1">
                    @error('total_marks') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Passing Marks <span class="text-red-500">*</span></label>
                    <input type="number" name="passing_marks" value="{{ old('passing_marks', 6) }}"
                           class="form-input @error('passing_marks') border-red-400 @enderror" min="1">
                    @error('passing_marks') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create & Add Questions
                </button>
                <a href="{{ route('admin.exams.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
