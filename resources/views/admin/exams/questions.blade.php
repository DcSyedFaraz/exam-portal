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
        <button id="open-question-modal" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Question
        </button>
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

{{-- Question Modal --}}
<div id="question-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 id="modal-title" class="text-lg font-semibold font-heading">Add Question</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="question-form" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="question-id" value="">

            <div>
                <label class="form-label">Question Type <span class="text-red-500">*</span></label>
                <select id="question_type" name="question_type"
                        class="form-input" onchange="onTypeChange(this.value)">
                    <option value="mcq">Multiple Choice (MCQ)</option>
                    <option value="true_false">True / False</option>
                    <option value="match">Match Items</option>
                </select>
            </div>

            <div>
                <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                <textarea id="question_text" name="question_text" rows="3"
                          class="form-input" placeholder="Enter the question..."></textarea>
            </div>

            <div class="w-32">
                <label class="form-label">Marks <span class="text-red-500">*</span></label>
                <input type="number" id="marks" name="marks" value="1" min="1"
                       class="form-input">
            </div>

            {{-- MCQ Options --}}
            <div id="options-mcq">
                <label class="form-label">Options (select correct answer)</label>
                <div class="space-y-2" id="mcq-options-container">
                    @for($i = 0; $i < 4; $i++)
                    <div class="flex items-center gap-3">
                        <input type="radio" name="correct_option_mcq" value="{{ $i }}"
                               class="text-yellow-400 focus:ring-yellow-400 w-4 h-4" {{ $i === 0 ? 'checked' : '' }}>
                        <input type="text" class="form-input mcq-option-text"
                               placeholder="Option {{ chr(65+$i) }}">
                    </div>
                    @endfor
                </div>
            </div>

            {{-- True/False Options --}}
            <div id="options-true_false" class="hidden">
                <label class="form-label">Correct Answer</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="correct_tf" value="true" checked
                               class="text-yellow-400 focus:ring-yellow-400 w-4 h-4">
                        <span class="text-sm font-medium">True</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="correct_tf" value="false"
                               class="text-yellow-400 focus:ring-yellow-400 w-4 h-4">
                        <span class="text-sm font-medium">False</span>
                    </label>
                </div>
            </div>

            {{-- Match Options --}}
            <div id="options-match" class="hidden">
                <label class="form-label">Match Pairs (left → right)</label>
                <div class="space-y-2" id="match-pairs-container">
                    @for($i = 0; $i < 3; $i++)
                    <div class="flex items-center gap-2">
                        <input type="text" class="form-input match-left" placeholder="Left item">
                        <span class="text-gray-400">→</span>
                        <input type="text" class="form-input match-right" placeholder="Right item">
                    </div>
                    @endfor
                </div>
                <button type="button" onclick="addMatchPair()"
                        class="text-sm text-yellow-600 hover:underline mt-2">+ Add pair</button>
            </div>

            <div id="form-error" class="text-red-500 text-sm hidden"></div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button" id="save-question-btn"
                        onclick="saveQuestion()"
                        class="btn-primary">
                    Save Question
                </button>
                <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const storeUrl  = "{{ route('admin.exams.questions.store', $exam) }}";
const updateBase = "{{ route('admin.questions.update', '__ID__') }}".replace('__ID__', '');
const destroyBase = "{{ route('admin.questions.destroy', '__ID__') }}".replace('__ID__', '');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ── Modal ──────────────────────────────────────────────────────────────────
document.getElementById('open-question-modal').addEventListener('click', () => {
    document.getElementById('modal-title').textContent = 'Add Question';
    document.getElementById('question-id').value = '';
    document.getElementById('question-form').reset();
    document.getElementById('question_type').value = 'mcq';
    onTypeChange('mcq');
    resetMcqOptions();
    document.getElementById('question-modal').classList.remove('hidden');
});

function closeModal() {
    document.getElementById('question-modal').classList.add('hidden');
    document.getElementById('form-error').classList.add('hidden');
}

document.getElementById('question-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// ── Type Change ────────────────────────────────────────────────────────────
function onTypeChange(type) {
    ['mcq','true_false','match'].forEach(t => {
        document.getElementById('options-' + t).classList.toggle('hidden', t !== type);
    });
}

// ── MCQ Helpers ────────────────────────────────────────────────────────────
function resetMcqOptions(values) {
    const container = document.getElementById('mcq-options-container');
    container.innerHTML = '';
    const opts = values || ['', '', '', ''];
    opts.forEach((val, i) => {
        container.innerHTML += `
            <div class="flex items-center gap-3">
                <input type="radio" name="correct_option_mcq" value="${i}" class="text-yellow-400 w-4 h-4">
                <input type="text" class="form-input mcq-option-text" value="${val}" placeholder="Option ${String.fromCharCode(65+i)}">
            </div>`;
    });
}

// ── Match Pair ─────────────────────────────────────────────────────────────
function addMatchPair() {
    const container = document.getElementById('match-pairs-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2';
    div.innerHTML = `<input type="text" class="form-input match-left" placeholder="Left item">
        <span class="text-gray-400">→</span>
        <input type="text" class="form-input match-right" placeholder="Right item">`;
    container.appendChild(div);
}

// ── Save Question (Add / Edit) ─────────────────────────────────────────────
async function saveQuestion() {
    const type      = document.getElementById('question_type').value;
    const text      = document.getElementById('question_text').value.trim();
    const marks     = document.getElementById('marks').value;
    const questionId = document.getElementById('question-id').value;
    const errorEl   = document.getElementById('form-error');

    if (!text) { errorEl.textContent = 'Question text is required.'; errorEl.classList.remove('hidden'); return; }

    const options = buildOptions(type);
    if (!options) return;

    const payload = { question_text: text, question_type: type, marks, options, _token: csrfToken };

    const url    = questionId ? (updateBase + questionId) : storeUrl;
    const method = questionId ? 'PUT' : 'POST';

    try {
        document.getElementById('save-question-btn').textContent = 'Saving...';
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) {
            errorEl.textContent = Object.values(data.errors || {}).flat().join(', ') || 'Error saving question.';
            errorEl.classList.remove('hidden');
            return;
        }

        // Update DOM
        const existingRow = document.getElementById('question-' + data.question.id);
        const newHtml = new DOMParser().parseFromString(data.html, 'text/html').body.firstElementChild;
        if (existingRow) {
            existingRow.replaceWith(newHtml);
        } else {
            document.getElementById('empty-state')?.remove();
            document.getElementById('questions-list').insertAdjacentElement('beforeend', newHtml);
        }

        closeModal();
    } catch (e) {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    } finally {
        document.getElementById('save-question-btn').textContent = 'Save Question';
    }
}

function buildOptions(type) {
    const errorEl = document.getElementById('form-error');
    if (type === 'mcq') {
        const texts   = [...document.querySelectorAll('.mcq-option-text')].map(i => i.value.trim());
        const correct = document.querySelector('input[name="correct_option_mcq"]:checked')?.value;
        if (texts.some(t => !t)) { errorEl.textContent = 'All MCQ options are required.'; errorEl.classList.remove('hidden'); return null; }
        return texts.map((text, i) => ({ text, is_correct: i == correct ? '1' : '0' }));
    }
    if (type === 'true_false') {
        const correct = document.querySelector('input[name="correct_tf"]:checked')?.value;
        return [
            { text: 'True',  is_correct: correct === 'true'  ? '1' : '0' },
            { text: 'False', is_correct: correct === 'false' ? '1' : '0' },
        ];
    }
    if (type === 'match') {
        const lefts  = [...document.querySelectorAll('.match-left')].map(i => i.value.trim());
        const rights = [...document.querySelectorAll('.match-right')].map(i => i.value.trim());
        if (lefts.some(t => !t) || rights.some(t => !t)) { errorEl.textContent = 'All match pairs must be filled.'; errorEl.classList.remove('hidden'); return null; }
        return lefts.map((left, i) => ({ text: left, is_correct: '1', match_pair: rights[i] }));
    }
    return [];
}

// ── Edit ───────────────────────────────────────────────────────────────────
function editQuestion(id) {
    const btn = document.querySelector(`[onclick="editQuestion(${id})"]`);
    const q   = JSON.parse(btn.dataset.question);

    document.getElementById('modal-title').textContent = 'Edit Question';
    document.getElementById('question-id').value = q.id;
    document.getElementById('question_type').value = q.question_type;
    document.getElementById('question_text').value = q.question_text;
    document.getElementById('marks').value = q.marks;
    onTypeChange(q.question_type);

    if (q.question_type === 'mcq') {
        const texts = q.options.map(o => o.option_text);
        resetMcqOptions(texts);
        const correctIdx = q.options.findIndex(o => o.is_correct);
        const radios = document.querySelectorAll('input[name="correct_option_mcq"]');
        if (radios[correctIdx]) radios[correctIdx].checked = true;
    }
    if (q.question_type === 'true_false') {
        const correct = q.options.find(o => o.is_correct)?.option_text?.toLowerCase();
        document.querySelectorAll('input[name="correct_tf"]').forEach(r => { r.checked = r.value === correct; });
    }
    if (q.question_type === 'match') {
        const container = document.getElementById('match-pairs-container');
        container.innerHTML = '';
        q.options.forEach(o => {
            container.innerHTML += `<div class="flex items-center gap-2">
                <input type="text" class="form-input match-left" value="${o.option_text}">
                <span class="text-gray-400">→</span>
                <input type="text" class="form-input match-right" value="${o.match_pair || ''}">
            </div>`;
        });
    }

    document.getElementById('question-modal').classList.remove('hidden');
}

// ── Delete ─────────────────────────────────────────────────────────────────
async function deleteQuestion(id, btn) {
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
