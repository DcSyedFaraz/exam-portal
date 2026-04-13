@extends('layouts.app')

@section('title', 'Edit Exam')
@section('page-title', 'Edit Exam')
@section('breadcrumb', 'Admin › Exams › Edit')

@section('content')
<div class="max-w-2xl">
    <div class="card">
        <form method="POST" action="{{ route('admin.exams.update', $exam) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="form-label">Exam Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $exam->title) }}"
                       class="form-input @error('title') border-red-400 @enderror">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3"
                          class="form-input @error('description') border-red-400 @enderror">{{ old('description', $exam->description) }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Class Level</label>
                <select name="class_level" class="form-input @error('class_level') border-red-400 @enderror">
                    <option value="">— All classes (no restriction) —</option>
                    @foreach($classLevels as $level)
                        <option value="{{ $level }}"
                            {{ old('class_level', $exam->class_level) === $level ? 'selected' : '' }}>
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
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}"
                           class="form-input @error('duration_minutes') border-red-400 @enderror" min="1" max="300">
                    @error('duration_minutes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Total Marks <span class="text-red-500">*</span></label>
                    <input type="number" name="total_marks" value="{{ old('total_marks', $exam->total_marks) }}"
                           class="form-input @error('total_marks') border-red-400 @enderror" min="1">
                    @error('total_marks') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Passing Marks <span class="text-red-500">*</span></label>
                    <input type="number" name="passing_marks" value="{{ old('passing_marks', $exam->passing_marks) }}"
                           class="form-input @error('passing_marks') border-red-400 @enderror" min="1">
                    @error('passing_marks') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('admin.exams.questions', $exam) }}" class="btn-secondary">Manage Questions</a>
                <a href="{{ route('admin.exams.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
