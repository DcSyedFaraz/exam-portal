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
            <a href="{{ route('student.exams.index') }}" class="btn-secondary">
                ← Back to Exams
            </a>
        </div>
    </div>

    {{-- Per-Question Breakdown (passed students only) --}}
    @if($attempt->is_passed)
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-4">Answer Breakdown</h3>

        <div class="space-y-4">
            @foreach($attempt->answers as $i => $answer)
            @php
                $question = $answer->question;

                // Resolve partial-match state for match questions
                if ($question->question_type === 'match') {
                    $pairCount    = $question->options->count();
                    // Count directly from stored selections — never back-calculate from marks
                    $selections   = $answer->match_selections ?? [];
                    $correctPairs = $question->options->filter(function ($opt) use ($selections) {
                        $sub = $selections[(string) $opt->id] ?? null;
                        return $sub !== null && $sub === $opt->match_pair;
                    })->count();
                    $isPartial = $correctPairs > 0 && !$answer->is_correct;
                } else {
                    $pairCount    = 0;
                    $correctPairs = 0;
                    $isPartial    = false;
                }

                $cardClass = $answer->is_correct
                    ? 'border-green-400 bg-green-50'
                    : ($isPartial ? 'border-amber-400 bg-amber-50' : 'border-red-400 bg-red-50');

                $iconClass = $answer->is_correct ? 'text-green-600' : ($isPartial ? 'text-amber-500' : 'text-red-500');
                $iconGlyph = $answer->is_correct ? '✅' : ($isPartial ? '⚡' : '❌');
            @endphp
            <div class="rounded-xl border-l-4 p-4 {{ $cardClass }}">
                <div class="flex items-start gap-3">
                    <span class="{{ $iconClass }} text-xl mt-0.5 shrink-0">{{ $iconGlyph }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                            Q{{ $i + 1 }}. {{ $question->question_text }}
                        </p>

                        @if($question->question_type === 'match')
                            <p class="text-xs font-medium {{ $answer->is_correct ? 'text-green-700' : ($isPartial ? 'text-amber-700' : 'text-red-600') }} mb-2">
                                {{ $correctPairs }} of {{ $pairCount }} pair{{ $pairCount !== 1 ? 's' : '' }} correct
                                @if($isPartial)
                                    &mdash; partial credit awarded
                                @endif
                            </p>
                            {{-- Correct pairs only --}}
                            <div class="space-y-1.5">
                                @foreach($question->options as $option)
                                @php
                                    $submitted = ($answer->match_selections ?? [])[(string) $option->id] ?? null;
                                    $pairOk    = $submitted !== null && $submitted === $option->match_pair;
                                @endphp
                                @if($pairOk)
                                <div class="flex items-center gap-2 text-xs rounded-lg px-2 py-1 bg-green-100">
                                    <span class="shrink-0">✅</span>
                                    <span class="font-medium text-gray-800 flex-1 min-w-0">{{ $option->option_text }}</span>
                                    <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    <span class="flex-1 min-w-0 text-green-700 font-medium">{{ $option->match_pair }}</span>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm {{ $answer->is_correct ? 'text-green-700' : 'text-red-600' }}">
                                Your answer:
                                <strong>{{ $answer->selectedOption?->option_text ?? 'Not answered' }}</strong>
                            </p>
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
    @else
    {{-- Failed: show match pair correct answers only; MCQ answers stay hidden --}}
    <div class="card mb-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📚</span>
            <div>
                <h3 class="text-base font-semibold text-gray-800">Keep Studying!</h3>
                <p class="text-xs text-gray-500">Match question answers are shown below to help you review. MCQ answers are revealed after passing.</p>
            </div>
        </div>

        @php
            $matchAnswers = $attempt->answers->filter(fn($a) => $a->question?->question_type === 'match');
        @endphp

        @if($matchAnswers->isNotEmpty())
        <div class="space-y-4">
            @foreach($matchAnswers as $answer)
            @php
                $question     = $answer->question;
                $pairCount    = $question->options->count();
                $selections   = $answer->match_selections ?? [];
                $correctPairs = $question->options->filter(function ($opt) use ($selections) {
                    $sub = $selections[(string) $opt->id] ?? null;
                    return $sub !== null && $sub === $opt->match_pair;
                })->count();
                $isPartial = $correctPairs > 0 && !$answer->is_correct;

                $cardClass = $answer->is_correct
                    ? 'border-green-400 bg-green-50'
                    : ($isPartial ? 'border-amber-400 bg-amber-50' : 'border-red-400 bg-red-50');
                $iconGlyph = $answer->is_correct ? '✅' : ($isPartial ? '⚡' : '❌');
                $iconClass = $answer->is_correct ? 'text-green-600' : ($isPartial ? 'text-amber-500' : 'text-red-500');
            @endphp
            <div class="rounded-xl border-l-4 p-4 {{ $cardClass }}">
                <div class="flex items-start gap-3">
                    <span class="{{ $iconClass }} text-xl mt-0.5 shrink-0">{{ $iconGlyph }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 mb-2">
                            {{ $question->question_text }}
                        </p>
                        <p class="text-xs font-medium {{ $answer->is_correct ? 'text-green-700' : ($isPartial ? 'text-amber-700' : 'text-red-600') }} mb-2">
                            {{ $correctPairs }} of {{ $pairCount }} pair{{ $pairCount !== 1 ? 's' : '' }} correct
                            @if($isPartial)
                                &mdash; partial credit awarded
                            @endif
                        </p>
                        {{-- Correct pairs only --}}
                        <div class="mt-2 space-y-1.5">
                            @foreach($question->options as $option)
                            @php
                                $submitted = ($answer->match_selections ?? [])[(string) $option->id] ?? null;
                                $pairOk    = $submitted !== null && $submitted === $option->match_pair;
                            @endphp
                            @if($pairOk)
                            <div class="flex items-center gap-2 text-xs rounded-lg px-2 py-1 bg-green-100">
                                <span class="shrink-0">✅</span>
                                <span class="font-medium text-gray-800 flex-1 min-w-0">{{ $option->option_text }}</span>
                                <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                <span class="flex-1 min-w-0 text-green-700 font-medium">{{ $option->match_pair }}</span>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            {{ $answer->marks_awarded }}/{{ $question->marks }} mark(s)
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="mt-6 text-center">
            <a href="{{ route('student.exams.instructions', $exam) }}" class="btn-primary inline-flex">
                🔁 Retake Exam
            </a>
        </div>
    </div>
    @endif

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
