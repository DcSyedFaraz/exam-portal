@extends('layouts.app')

@section('title', 'Question Builder')
@section('page-title', 'Question Builder')
@section('breadcrumb', 'Admin › Exams › ' . $exam->title . ' › Questions')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold font-heading text-gray-900">{{ $exam->title }}</h2>
        <p class="text-sm text-gray-500 mt-0.5">{{ $questions->count() }} question(s) · {{ $exam->total_marks }} total marks · {{ $exam->duration_minutes }} min</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.exams.edit', $exam) }}" class="btn-secondary text-sm">Edit Exam</a>
        <a href="{{ route('admin.exams.questions.create', $exam) }}" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Question
        </a>
    </div>
</div>

{{-- Questions List --}}
<div id="questions-list" class="space-y-3 mb-6">
    @forelse($questions as $question)
        @include('admin.exams.partials.question-row', ['question' => $question])
    @empty
        <div id="empty-state" class="card text-center py-12">
            <span class="text-5xl">❓</span>
            <p class="text-gray-500 mt-3">No questions yet. Add your first question!</p>
        </div>
    @endforelse
</div>

<div class="flex gap-3">
    <a href="{{ route('admin.exams.index') }}" class="btn-secondary">← Back to Exams</a>
</div>

<script>
const destroyBase = "{{ route('admin.questions.destroy', '__ID__') }}".replace('__ID__', '');
const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

// ── Image lightbox ─────────────────────────────────────────────────────────
function openImageModal(src) {
    const modal = document.getElementById('image-modal');
    document.getElementById('image-modal-img').src = src;
    modal.classList.remove('hidden');
}
function closeImageModal() {
    document.getElementById('image-modal').classList.add('hidden');
    document.getElementById('image-modal-img').src = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeImageModal(); });

// ── Delete ─────────────────────────────────────────────────────────────────
async function deleteQuestion(id) {
    if (!confirm('Delete this question?')) return;
    const url = destroyBase + id;
    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        if (res.ok) {
            document.getElementById('question-' + id)?.remove();
            if (!document.querySelector('.question-row')) {
                document.getElementById('questions-list').innerHTML =
                    `<div id="empty-state" class="card text-center py-12">
                        <span class="text-5xl">❓</span>
                        <p class="text-gray-500 mt-3">No questions yet. Add your first question!</p>
                    </div>`;
            }
        }
    } catch (e) { alert('Error deleting question.'); }
}
</script>
@endsection

@push('modals')
{{-- Image lightbox --}}
<div id="image-modal"
     class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/80 p-4"
     onclick="if(event.target===this) closeImageModal()">
    <div class="relative max-w-3xl w-full">
        <button onclick="closeImageModal()"
                class="absolute -top-3 -right-3 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-gray-600 hover:text-gray-900 z-10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img id="image-modal-img" src="" alt="Question image"
             class="w-full max-h-[80vh] object-contain rounded-xl shadow-2xl">
    </div>
</div>
@endpush
