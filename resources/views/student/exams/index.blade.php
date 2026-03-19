@extends('layouts.app')

@section('title', 'Available Exams')
@section('page-title', 'Available Exams')
@section('breadcrumb', 'Student › Exams')

@section('content')
@if($exams->isEmpty())
    <div class="card text-center py-16">
        <span class="text-6xl">📭</span>
        <p class="text-gray-500 mt-4 text-lg">No exams available right now.</p>
        <p class="text-gray-400 text-sm mt-1">Check back later!</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($exams as $exam)
        <div class="card hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between mb-3">
                <h3 class="font-semibold font-heading text-gray-900 leading-tight">{{ $exam->title }}</h3>
                @if($exam->latest_attempt)
                    @if($exam->latest_attempt->is_passed)
                        <span class="badge-pass ml-2 shrink-0">Completed ✓</span>
                    @else
                        <span class="badge-fail ml-2 shrink-0">Failed – Retry</span>
                    @endif
                @else
                    <span class="badge-pending ml-2 shrink-0">Not Started</span>
                @endif
            </div>

            @if($exam->description)
                <p class="text-gray-500 text-sm mb-4 line-clamp-2">{{ $exam->description }}</p>
            @endif

            <div class="grid grid-cols-3 gap-2 mb-5">
                <div class="bg-gray-50 rounded-lg p-2.5 text-center">
                    <p class="text-xs text-gray-400">Duration</p>
                    <p class="font-semibold text-gray-900 text-sm">{{ $exam->duration_minutes }}m</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-2.5 text-center">
                    <p class="text-xs text-gray-400">Questions</p>
                    <p class="font-semibold text-gray-900 text-sm">{{ $exam->questions_count }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-2.5 text-center">
                    <p class="text-xs text-gray-400">Pass Mark</p>
                    <p class="font-semibold text-gray-900 text-sm">{{ $exam->passing_marks }}/{{ $exam->total_marks }}</p>
                </div>
            </div>

            @if($exam->latest_attempt)
                @if($exam->latest_attempt->is_passed)
                    <a href="{{ route('student.exams.result', $exam) }}"
                       class="btn-secondary w-full justify-center">
                        View Result
                    </a>
                @else
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-1">
                            <span>Last attempt:</span>
                            <span class="font-semibold text-gray-900">
                                {{ $exam->latest_attempt->score }}/{{ $exam->total_marks }}
                            </span>
                        </div>
                        <a href="{{ route('student.exams.take', $exam) }}"
                           class="btn-primary w-full justify-center">
                            Retake Exam
                        </a>
                    </div>
                @endif
            @else
                <a href="{{ route('student.exams.take', $exam) }}"
                   class="btn-primary w-full justify-center">
                    Start Exam
                </a>
            @endif
        </div>
        @endforeach
    </div>
@endif
@endsection
