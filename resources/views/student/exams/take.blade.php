@extends('layouts.app')

@section('title', $exam->title)
@section('page-title', $exam->title)

@section('content')

    {{-- Sticky Exam Header --}}
    <div
        class="sticky top-0 bg-white border border-gray-100 rounded-xl shadow-sm z-30 px-6 py-4 mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-400">Question Progress</p>
            <p class="text-sm font-semibold text-gray-900" id="progress-text">
                0 / {{ $questions->count() }} answered
            </p>
        </div>
        <div class="text-center">
            <p class="text-xs text-gray-400 mb-0.5">Time Remaining</p>
            <div id="exam-timer" data-exam-id="{{ $exam->id }}" data-duration="{{ $exam->duration_minutes * 60 }}"
                class="text-2xl font-mono font-bold text-gray-900">
                --:--
            </div>
        </div>
        <div>
            <p class="text-xs text-gray-400 text-right">Total Marks</p>
            <p class="text-sm font-semibold text-gray-900 text-right">{{ $exam->total_marks }}</p>
        </div>
    </div>

    {{-- Questions + sticky left navigation — 1/3 nav + 2/3 questions on lg+ --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8 items-start">

        <aside id="qnav-panel" class="hidden lg:block lg:col-span-1 sticky top-25 z-20 self-start order-first">
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto w-78">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Question Navigation</p>

                <div class="flex flex-wrap gap-2" id="question-grid">
                    @foreach ($questions as $i => $question)
                        <button type="button" id="grid-{{ $question->id }}" onclick="scrollToQuestion({{ $question->id }})"
                            class="w-9 h-9 rounded-lg text-sm font-semibold transition-all
                        {{ $savedAnswers[$question->id] ?? null ? 'bg-yellow-400 text-gray-900' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">
                            {{ $i + 1 }}
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 flex-wrap">
                    <span class="flex items-center gap-1.5"><span
                            class="w-3 h-3 rounded bg-yellow-400 inline-block shrink-0"></span> Answered</span>
                    <span class="flex items-center gap-1.5"><span
                            class="w-3 h-3 rounded bg-gray-200 inline-block shrink-0"></span> Unanswered</span>
                </div>
            </div>
        </aside>

        <div class="min-w-0 w-full lg:col-span-2">

            <form id="exam-form" method="POST" action="{{ route('student.exams.submit', $exam) }}"
                data-save-progress-url="{{ route('student.exams.save-progress', $exam) }}">
                @csrf

                <div class="space-y-6">
                    @foreach ($questions as $i => $question)
                        <div class="card question-card" id="qcard-{{ $question->id }}"
                            data-question-id="{{ $question->id }}">
                            <div class="flex items-start gap-3 mb-4">
                                <span
                                    class="bg-yellow-400 text-gray-900 font-bold text-sm w-7 h-7 rounded-full flex items-center justify-center shrink-0">
                                    {{ $i + 1 }}
                                </span>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $question->question_text }}</p>
                                    <span class="text-xs text-gray-400 mt-0.5 inline-block">
                                        {{ $question->marks }} mark{{ $question->marks > 1 ? 's' : '' }} ·
                                        {{ strtoupper(str_replace('_', '/', $question->question_type)) }}
                                    </span>
                                </div>
                            </div>

                            {{-- MCQ --}}
                            @if ($question->question_type === 'mcq')
                                <div class="space-y-2">
                                    @foreach ($question->options as $j => $option)
                                        <label
                                            class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:border-yellow-400 hover:bg-yellow-50 transition-all answer-label"
                                            for="opt-{{ $option->id }}">
                                            <input type="radio" id="opt-{{ $option->id }}"
                                                name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                                class="text-yellow-400 focus:ring-yellow-400 w-4 h-4 answer-radio"
                                                data-question="{{ $question->id }}"
                                                {{ ($savedAnswers[$question->id] ?? null) == $option->id ? 'checked' : '' }}
                                                onchange="onAnswerChange({{ $question->id }})">
                                            <span
                                                class="w-7 h-7 rounded-full bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center shrink-0">
                                                {{ chr(65 + $j) }}
                                            </span>
                                            <span class="text-sm text-gray-800">{{ $option->option_text }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- True/False --}}
                            @elseif($question->question_type === 'true_false')
                                <div class="flex gap-4">
                                    @foreach ($question->options as $option)
                                        <label
                                            class="flex-1 flex items-center justify-center gap-2 p-4 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-yellow-400 hover:bg-yellow-50 transition-all answer-label font-semibold text-gray-700"
                                            for="opt-{{ $option->id }}">
                                            <input type="radio" id="opt-{{ $option->id }}"
                                                name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                                class="sr-only answer-radio" data-question="{{ $question->id }}"
                                                {{ ($savedAnswers[$question->id] ?? null) == $option->id ? 'checked' : '' }}
                                                onchange="onAnswerChange({{ $question->id }})">
                                            {{ $option->option_text === 'True' ? '✅' : '❌' }} {{ $option->option_text }}
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Match --}}
                            @elseif($question->question_type === 'match')
                                <div class="space-y-3">
                                    @php
                                        $rightOptions = $question->options
                                            ->pluck('match_pair')
                                            ->shuffle()
                                            ->unique()
                                            ->values();
                                    @endphp
                                    @foreach ($question->options as $option)
                                        <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50">
                                            <span
                                                class="text-sm font-medium text-gray-800 flex-1">{{ $option->option_text }}</span>
                                            <span class="text-gray-400 text-sm">→</span>
                                            <select name="answers[{{ $question->id }}][{{ $option->id }}]"
                                                class="form-input w-44 text-sm"
                                                onchange="onAnswerChange({{ $question->id }})">
                                                <option value="">Select...</option>
                                                @foreach ($rightOptions as $right)
                                                    <option value="{{ $right }}">{{ $right }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Submit Button --}}
                <div class="mt-8 flex items-center justify-between">
                    <p class="text-sm text-gray-500" id="submit-status">Answer all questions before submitting.</p>
                    <button type="button" onclick="confirmSubmit()" class="btn-primary px-8 py-3 text-base">
                        Submit Exam
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const totalQuestions = {{ $questions->count() }};
        const answeredQuestions = new Set();

        // Initialize from pre-filled answers
        @foreach ($questions as $question)
            @if ($savedAnswers[$question->id] ?? null)
                answeredQuestions.add({{ $question->id }});
            @endif
        @endforeach

        updateProgress();

        function onAnswerChange(questionId) {
            answeredQuestions.add(questionId);
            const gridBtn = document.getElementById('grid-' + questionId);
            if (gridBtn) {
                gridBtn.className = 'w-9 h-9 rounded-lg text-sm font-semibold transition-all bg-yellow-400 text-gray-900';
            }
            updateProgress();
        }

        function updateProgress() {
            const count = answeredQuestions.size;
            document.getElementById('progress-text').textContent = `${count} / ${totalQuestions} answered`;
            document.getElementById('submit-status').textContent =
                count === totalQuestions ?
                'All questions answered. Ready to submit!' :
                `${totalQuestions - count} question(s) unanswered.`;
        }

        function confirmSubmit() {
            document.getElementById('confirm-modal').classList.remove('hidden');
        }

        function scrollToQuestion(id) {
            document.getElementById('qcard-' + id)?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Highlight selected radio labels on load and on change
        document.querySelectorAll('.answer-radio').forEach(radio => {
            if (radio.checked) {
                radio.closest('.answer-label')?.classList.add('border-yellow-400', 'bg-yellow-50');
            }
            radio.addEventListener('change', function() {
                const name = this.name;
                document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                    r.closest('.answer-label')?.classList.remove('border-yellow-400',
                        'bg-yellow-50');
                });
                this.closest('.answer-label')?.classList.add('border-yellow-400', 'bg-yellow-50');
            });
        });
    </script>
@endsection

@push('modals')
    {{-- Confirm Submit Modal --}}
    <div id="confirm-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
            <span class="text-4xl mb-3 block">🚀</span>
            <h3 class="text-lg font-bold font-heading text-gray-900 mb-2">Submit Exam?</h3>
            <p class="text-gray-500 text-sm mb-6">Once submitted, you cannot change your answers.</p>
            <div class="flex gap-3">
                <button onclick="document.getElementById('exam-form').submit()" class="btn-primary flex-1 justify-center">
                    Yes, Submit
                </button>
                <button onclick="document.getElementById('confirm-modal').classList.add('hidden')"
                    class="btn-secondary flex-1 justify-center">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endpush
