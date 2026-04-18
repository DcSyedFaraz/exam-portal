@extends('layouts.app')

@section('title', $exam->title . ' — Instructions')
@section('page-title', 'Exam Instructions')
@section('breadcrumb', 'Student › Exams › Instructions')

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- Exam Summary Card --}}
    <div class="card">
        <h2 class="text-xl font-bold font-heading text-gray-900 mb-1">{{ $exam->title }}</h2>
        @if($exam->description)
            <p class="text-sm text-gray-500 mt-1">{{ $exam->description }}</p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-400 mb-1">Questions</p>
                <p class="text-2xl font-bold font-heading text-gray-900">{{ $questionsCount }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-400 mb-1">Time Limit</p>
                <p class="text-2xl font-bold font-heading text-gray-900">
                    @if($exam->duration_minutes >= 60 && $exam->duration_minutes % 60 === 0)
                        {{ (int) ($exam->duration_minutes / 60) }}h
                    @else
                        {{ $exam->duration_minutes }}<span class="text-base font-medium">min</span>
                    @endif
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-400 mb-1">Pass Mark</p>
                <p class="text-2xl font-bold font-heading text-gray-900">{{ $exam->passing_marks }}<span class="text-base font-medium text-gray-500">/{{ $exam->total_marks }}</span></p>
            </div>
        </div>
    </div>

    {{-- Instructions Card --}}
    <div class="card border-l-4 border-yellow-400">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-xl shrink-0">📌</div>
            <h3 class="text-lg font-bold font-heading text-gray-900">EXAM INSTRUCTIONS</h3>
        </div>
        <p class="text-sm text-gray-600 mb-4">Please read the following instructions carefully before starting your exam:</p>

        <ul class="space-y-3">
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">📝</span>
                <span class="text-sm text-gray-700">This is a <strong>timed examination</strong>. You must complete your work within the given time.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">🚫</span>
                <span class="text-sm text-gray-700"><strong>Do not close the browser</strong> during the exam. Closing it may lead to loss of your answers.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">🚫</span>
                <span class="text-sm text-gray-700"><strong>Do not minimize or switch tabs/windows</strong> while the exam is in progress. This may be recorded as malpractice.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">⏱️</span>
                <span class="text-sm text-gray-700">The exam will be <strong>automatically submitted</strong> when time runs out.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">📤</span>
                <span class="text-sm text-gray-700"><strong>Do not click Submit</strong> before you have finished all questions unless you are sure you are done.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">🔄</span>
                <span class="text-sm text-gray-700">Once submitted, <strong>you cannot go back or change answers</strong>.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">📶</span>
                <span class="text-sm text-gray-700">Ensure you have a <strong>stable internet connection</strong> throughout the exam.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">🧠</span>
                <span class="text-sm text-gray-700"><strong>Read each question carefully</strong> before answering.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">⚠️</span>
                <span class="text-sm text-gray-700">Any form of <strong>cheating or unauthorized assistance</strong> is strictly prohibited.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-lg shrink-0 mt-0.5">👍</span>
                <span class="text-sm text-gray-700">Click <strong>"Start Exam"</strong> only when you are ready to begin.</span>
            </li>
        </ul>

        {{-- Acknowledgement checkbox --}}
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <label class="flex items-start gap-3 cursor-pointer select-none">
                <input type="checkbox" id="ack-checkbox"
                       class="mt-0.5 w-4 h-4 accent-yellow-400 shrink-0 cursor-pointer"
                       onchange="toggleBeginBtn(this.checked)">
                <span class="text-sm text-yellow-900 font-medium">
                    I have read and understood all the instructions above and I am ready to start the exam.
                </span>
            </label>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-5">
            <form method="POST" action="{{ route('student.exams.begin', $exam) }}" class="flex-1">
                @csrf
                <button type="submit" id="begin-btn" disabled
                        class="btn-primary w-full justify-center opacity-50 cursor-not-allowed"
                        title="Please tick the box above to confirm you have read the instructions">
                    🚀 Start Exam
                </button>
            </form>
            <a href="{{ route('student.exams.index') }}" class="btn-secondary flex-1 justify-center">
                ← Back to Exams
            </a>
        </div>
    </div>

</div>

<script>
function toggleBeginBtn(checked) {
    const btn = document.getElementById('begin-btn');
    btn.disabled = !checked;
    btn.classList.toggle('opacity-50', !checked);
    btn.classList.toggle('cursor-not-allowed', !checked);
}
</script>
@endsection
