@extends('layouts.app')

@section('title', 'Student Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', config('app_settings.name') . ' › Student')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold font-heading text-gray-900">Hello, {{ auth()->user()->name }}! 👋</h2>
    <p class="text-gray-500 mt-1">Ready to test your knowledge today?</p>
</div>

@if(!auth()->user()->studentProfile?->class_level)
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-lg px-4 py-3 mb-6">
    Your account has no class level assigned. Contact your parent or admin to have a class assigned so you can access all available exams.
</div>
@endif

{{-- Stat Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Available</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $availableExams }}">0</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-xl">📚</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Taken</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $examsTaken }}">0</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-xl">✍️</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Passed</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $examsPassed }}">0</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-xl">🏆</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Avg Score</p>
                <p class="text-3xl font-bold font-heading text-gray-900">{{ number_format($avgScore, 1) }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-xl">⭐</div>
        </div>
    </div>
</div>

{{-- Available Exams --}}
<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold font-heading text-gray-900">Available Exams</h3>
    <a href="{{ route('student.exams.index') }}" class="text-sm text-yellow-600 hover:underline">View all →</a>
</div>

@if($exams->isEmpty())
    <div class="card text-center py-12">
        <span class="text-5xl">📭</span>
        <p class="text-gray-500 mt-3">No exams available right now. Check back later!</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($exams->take(6) as $exam)
        <div class="card hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <h4 class="font-semibold text-gray-900 leading-tight">{{ $exam->title }}</h4>
                @if($exam->latest_attempt)
                    @if($exam->latest_attempt->is_passed)
                        <span class="badge-pass ml-2 shrink-0">Done ✓</span>
                    @else
                        <span class="badge-fail ml-2 shrink-0">Retry</span>
                    @endif
                @else
                    <span class="badge-pending ml-2 shrink-0">New</span>
                @endif
            </div>

            <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                <span>⏱ {{ $exam->duration_minutes }} min</span>
                <span>❓ {{ $exam->questions_count }} questions</span>
                <span>🎯 {{ $exam->passing_marks }}/{{ $exam->total_marks }}</span>
            </div>

            @if(!$exam->latest_attempt || !$exam->latest_attempt->is_passed)
                <a href="{{ route('student.exams.instructions', $exam) }}"
                   class="btn-primary w-full justify-center text-sm">
                    {{ $exam->latest_attempt ? 'Retake Exam' : 'Start Exam' }}
                </a>
            @else
                <a href="{{ route('student.exams.result', $exam) }}"
                   class="btn-secondary w-full justify-center text-sm">
                    View Result
                </a>
            @endif
        </div>
        @endforeach
    </div>
@endif
@endsection
