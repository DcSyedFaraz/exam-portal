@extends('layouts.app')

@section('title', 'Exam Result')
@section('page-title', 'Exam Result')
@section('breadcrumb', 'Student › Exams › Result')

@section('content')
@php
    $percentage = $exam->total_marks > 0 ? round(($attempt->score / $exam->total_marks) * 100) : 0;
    $circumference = 2 * pi() * 45;
    $dash = $circumference - ($percentage / 100) * $circumference;
@endphp

<div class="max-w-3xl mx-auto" data-show-confetti="{{ $attempt->is_passed ? 'true' : 'false' }}">

    {{-- Score Card --}}
    <div class="card text-center mb-6">
        <div class="flex flex-col items-center mb-6">
            {{-- SVG Ring --}}
            <div class="relative w-36 h-36 mb-4">
                <svg class="w-36 h-36 -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#E5E7EB" stroke-width="8"/>
                    <circle cx="50" cy="50" r="45" fill="none"
                            stroke="{{ $attempt->is_passed ? '#10B981' : '#EF4444' }}"
                            stroke-width="8"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $circumference }}"
                            class="score-ring"
                            data-target-offset="{{ $dash }}"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold font-heading text-gray-900">{{ $percentage }}%</span>
                    <span class="text-xs text-gray-400">score</span>
                </div>
            </div>

            <h2 class="text-3xl font-bold font-heading {{ $attempt->is_passed ? 'text-green-600' : 'text-red-500' }}">
                {{ $attempt->is_passed ? '🎉 PASSED!' : '😔 FAILED' }}
            </h2>
            <p class="text-gray-500 mt-1">
                {{ $attempt->score }} / {{ $exam->total_marks }} marks
                · Passing mark: {{ $exam->passing_marks }}
            </p>

            <p class="text-xs text-gray-400 mt-2">
                Submitted {{ $attempt->submitted_at?->format('d M Y, H:i') }}
            </p>
        </div>

        <div class="flex gap-3 justify-center">
            @if(!$attempt->is_passed)
                <a href="{{ route('student.exams.take', $exam) }}" class="btn-primary">
                    🔁 Retake Exam
                </a>
            @endif
            <a href="{{ route('student.exams.index') }}" class="btn-secondary">
                ← Back to Exams
            </a>
        </div>
    </div>

    {{-- Per-Question Breakdown --}}
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-4">Answer Breakdown</h3>

        <div class="space-y-4">
            @foreach($attempt->answers as $i => $answer)
            @php $question = $answer->question; @endphp
            <div class="rounded-xl border-l-4 p-4 {{ $answer->is_correct ? 'border-green-400 bg-green-50' : 'border-red-400 bg-red-50' }}">
                <div class="flex items-start gap-3">
                    <span class="{{ $answer->is_correct ? 'text-green-600' : 'text-red-500' }} text-xl mt-0.5 shrink-0">
                        {{ $answer->is_correct ? '✅' : '❌' }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                            Q{{ $i + 1 }}. {{ $question->question_text }}
                        </p>

                        @if($question->question_type === 'match')
                            <p class="text-xs text-gray-500">
                                Match question — {{ $answer->marks_awarded }}/{{ $question->marks }} marks awarded
                            </p>
                        @else
                            <p class="text-sm {{ $answer->is_correct ? 'text-green-700' : 'text-red-600' }}">
                                Your answer:
                                <strong>{{ $answer->selectedOption?->option_text ?? 'Not answered' }}</strong>
                            </p>
                            @if(!$answer->is_correct && $question->correctOption)
                            <p class="text-sm text-green-700 mt-1">
                                Correct answer:
                                <strong>{{ $question->correctOption->option_text }}</strong>
                            </p>
                            @endif
                        @endif

                        <p class="text-xs text-gray-400 mt-1">
                            {{ $answer->marks_awarded }}/{{ $question->marks }} mark(s)
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
// Animate score ring on load
document.addEventListener('DOMContentLoaded', () => {
    const ring = document.querySelector('.score-ring');
    if (ring) {
        const target = parseFloat(ring.dataset.targetOffset);
        setTimeout(() => {
            ring.style.transition = 'stroke-dashoffset 1s ease-in-out';
            ring.style.strokeDashoffset = target;
        }, 300);
    }
});
</script>
@endsection
