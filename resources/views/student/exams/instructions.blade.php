@extends('layouts.app')

@section('title', $exam->title . ' — Instructions')
@section('page-title', 'Exam Instructions')
@section('breadcrumb', 'Student › Exams › Instructions')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="card">
        <h2 class="text-xl font-bold font-heading text-gray-900 mb-1">{{ $exam->title }}</h2>
        @if($exam->description)
            <p class="text-sm text-gray-600 mt-2">{{ $exam->description }}</p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-400">Questions</p>
                <p class="font-semibold text-gray-900">{{ $questionsCount }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-400">Time Limit</p>
                <p class="font-semibold text-gray-900">
                    @if($exam->duration_minutes >= 60 && $exam->duration_minutes % 60 === 0)
                        {{ (int) ($exam->duration_minutes / 60) }} hour{{ (int) ($exam->duration_minutes / 60) === 1 ? '' : 's' }}
                    @else
                        {{ $exam->duration_minutes }} min
                    @endif
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-400">Total Marks</p>
                <p class="font-semibold text-gray-900">{{ $exam->total_marks }}</p>
            </div>
        </div>

        <div class="mt-6 space-y-2 text-sm text-gray-700">
            <p class="font-semibold text-gray-900">Please read carefully:</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Attempt all questions.</li>
                <li>Your exam timer starts after you click <strong>Begin Exam</strong>.</li>
                <li>Make sure you submit your exam on time.</li>
            </ul>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            <form method="POST" action="{{ route('student.exams.begin', $exam) }}">
                @csrf
                <button type="submit" class="btn-primary w-full justify-center">
                    Begin Exam
                </button>
            </form>
            <a href="{{ route('student.exams.index') }}" class="btn-secondary w-full justify-center">
                ← Back to Exams
            </a>
        </div>
    </div>

</div>
@endsection

