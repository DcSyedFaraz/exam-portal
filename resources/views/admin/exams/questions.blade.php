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

<script>
const storeUrl   = "{{ route('admin.exams.questions.store', $exam) }}";
const updateBase  = "{{ route('admin.questions.update', '__ID__') }}".replace('__ID__', '');
const destroyBase = "{{ route('admin.questions.destroy', '__ID__') }}".replace('__ID__', '');
const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;

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
const allTypes = ['mcq','true_false','match','picture','fill_blank','word_bank','ai_evaluated'];

function onTypeChange(type) {
    allTypes.forEach(t => {
        const el = document.getElementById('options-' + t);
        if (el) el.classList.toggle('hidden', t !== type);
    });
    // Show/hide the shared question_text for types that use it in the top field
    const sharedText = document.getElementById('shared-question-text-wrap');
    const hiddenForTypes = ['fill_blank', 'word_bank', 'ai_evaluated']; // these have their own textarea
    if (sharedText) sharedText.classList.toggle('hidden', hiddenForTypes.includes(type));
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

// ── Picture: Sub Question ──────────────────────────────────────────────────
function addSubQuestion() {
    const container = document.getElementById('sub-questions-container');
    const tmpl = document.getElementById('sub-question-template');
    const clone = tmpl.content.cloneNode(true);
    container.appendChild(clone);
}

// ── Word Bank: Answer Item ─────────────────────────────────────────────────
function addWordBankItem() {
    const container = document.getElementById('word-bank-answers-container');
    const div = document.createElement('div');
    div.className = 'word-bank-item-row flex gap-3 items-center mt-2';
    div.innerHTML = `
        <input type="text" name="wb_statement[]" placeholder="Statement" class="form-input flex-1">
        <input type="text" name="wb_correct_word[]" placeholder="Correct word" class="form-input w-40">
        <button type="button" onclick="this.closest('.word-bank-item-row').remove()" class="text-red-400 hover:text-red-600 text-sm">✕</button>`;
    container.appendChild(div);
}

// ── Save Question (Add / Edit) ─────────────────────────────────────────────
async function saveQuestion() {
    const type       = document.getElementById('question_type').value;
    const questionId = document.getElementById('question-id').value;
    const errorEl    = document.getElementById('form-error');
    errorEl.classList.add('hidden');

    const isNewType = ['picture','fill_blank','word_bank','ai_evaluated'].includes(type);

    if (isNewType) {
        await saveNewTypeQuestion(type, questionId, errorEl);
    } else {
        await saveLegacyQuestion(type, questionId, errorEl);
    }
}

async function saveLegacyQuestion(type, questionId, errorEl) {
    const text  = document.getElementById('question_text').value.trim();
    const marks = document.getElementById('marks').value;

    if (!text) { errorEl.textContent = 'Question text is required.'; errorEl.classList.remove('hidden'); return; }

    const options = buildOptions(type);
    if (!options) return;

    const payload = { question_text: text, question_type: type, marks, options, _token: csrfToken };
    await submitQuestion(questionId, payload, 'application/json', errorEl);
}

async function saveNewTypeQuestion(type, questionId, errorEl) {
    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('question_type', type);
    formData.append('marks', document.getElementById('marks').value);

    if (questionId) {
        formData.append('_method', 'PUT');
    }

    if (type === 'picture') {
        const imgFile = document.getElementById('picture-image-input').files[0];
        if (imgFile) formData.append('image', imgFile);

        const rows = document.querySelectorAll('.sub-question-row');
        if (rows.length === 0) {
            errorEl.textContent = 'Add at least one sub-question.';
            errorEl.classList.remove('hidden');
            return;
        }
        rows.forEach((row, i) => {
            formData.append(`sub_labels[]`, row.querySelector('select[name="sub_labels[]"]').value);
            formData.append(`sub_questions[]`, row.querySelector('input[name="sub_questions[]"]').value);
            formData.append(`sub_correct_answers[]`, row.querySelector('input[name="sub_correct_answers[]"]').value);
            formData.append(`sub_marks[]`, row.querySelector('input[name="sub_marks[]"]').value);
        });

    } else if (type === 'fill_blank') {
        formData.append('question_text', document.getElementById('fill-blank-question').value);
        formData.append('correct_answer_text', document.getElementById('fill-blank-answer').value);
        const gradingMode = document.querySelector('input[name="fill_blank_grading_radio"]:checked')?.value ?? 'exact';
        formData.append('fill_blank_grading', gradingMode);

    } else if (type === 'word_bank') {
        formData.append('question_text', document.getElementById('word-bank-question').value);
        formData.append('word_bank_items', document.getElementById('word-bank-items-input').value);
        const statements = document.querySelectorAll('input[name="wb_statement[]"]');
        const words      = document.querySelectorAll('input[name="wb_correct_word[]"]');
        if (statements.length === 0) {
            errorEl.textContent = 'Add at least one question item.';
            errorEl.classList.remove('hidden');
            return;
        }
        statements.forEach((s, i) => {
            formData.append(`options[${i}][statement]`, s.value);
            formData.append(`options[${i}][correct_word]`, words[i].value);
        });

    } else if (type === 'ai_evaluated') {
        formData.append('question_text', document.getElementById('ai-question').value);
        formData.append('correct_answer_text', document.getElementById('ai-correct-answer').value);
    }

    await submitQuestion(questionId, formData, null, errorEl);
}

async function submitQuestion(questionId, payload, contentType, errorEl) {
    const url    = questionId ? (updateBase + questionId) : storeUrl;
    const isForm = payload instanceof FormData;

    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken };
    if (contentType) headers['Content-Type'] = contentType;

    // FormData with PUT needs method spoofing; it's already added to FormData above
    const method = (questionId && !isForm) ? 'PUT' : 'POST';

    try {
        document.getElementById('save-question-btn').textContent = 'Saving...';
        const res  = await fetch(url, {
            method,
            headers,
            body: isForm ? payload : JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) {
            errorEl.textContent = Object.values(data.errors || {}).flat().join(', ') || 'Error saving question.';
            errorEl.classList.remove('hidden');
            return;
        }

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
    document.getElementById('marks').value = q.marks;
    onTypeChange(q.question_type);

    if (q.question_type === 'mcq') {
        document.getElementById('question_text').value = q.question_text;
        const texts = q.options.map(o => o.option_text);
        resetMcqOptions(texts);
        const correctIdx = q.options.findIndex(o => o.is_correct);
        const radios = document.querySelectorAll('input[name="correct_option_mcq"]');
        if (radios[correctIdx]) radios[correctIdx].checked = true;
    }
    if (q.question_type === 'true_false') {
        document.getElementById('question_text').value = q.question_text;
        const correct = q.options.find(o => o.is_correct)?.option_text?.toLowerCase();
        document.querySelectorAll('input[name="correct_tf"]').forEach(r => { r.checked = r.value === correct; });
    }
    if (q.question_type === 'match') {
        document.getElementById('question_text').value = q.question_text;
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
    if (q.question_type === 'fill_blank') {
        document.getElementById('fill-blank-question').value = q.question_text;
        document.getElementById('fill-blank-answer').value = q.correct_answer_text || '';
        const grading = q.fill_blank_grading || 'exact';
        document.querySelectorAll('input[name="fill_blank_grading_radio"]').forEach(r => {
            r.checked = r.value === grading;
        });
    }
    if (q.question_type === 'word_bank') {
        document.getElementById('word-bank-question').value = q.question_text;
        const items = Array.isArray(q.word_bank_items) ? q.word_bank_items.join(', ') : (q.word_bank_items || '');
        document.getElementById('word-bank-items-input').value = items;
        const container = document.getElementById('word-bank-answers-container');
        container.innerHTML = '';
        (q.options || []).forEach(opt => {
            const div = document.createElement('div');
            div.className = 'word-bank-item-row flex gap-3 items-center mt-2';
            div.innerHTML = `
                <input type="text" name="wb_statement[]" value="${opt.option_text}" placeholder="Statement" class="form-input flex-1">
                <input type="text" name="wb_correct_word[]" value="${opt.match_pair || ''}" placeholder="Correct word" class="form-input w-40">
                <button type="button" onclick="this.closest('.word-bank-item-row').remove()" class="text-red-400 hover:text-red-600 text-sm">✕</button>`;
            container.appendChild(div);
        });
    }
    if (q.question_type === 'ai_evaluated') {
        document.getElementById('ai-question').value = q.question_text;
        document.getElementById('ai-correct-answer').value = q.correct_answer_text || '';
    }
    if (q.question_type === 'picture') {
        const container = document.getElementById('sub-questions-container');
        container.innerHTML = '';
        (q.sub_items || []).forEach(sub => {
            const tmpl = document.getElementById('sub-question-template');
            const clone = tmpl.content.cloneNode(true);
            const row = clone.querySelector('.sub-question-row');
            row.querySelector('select[name="sub_labels[]"]').value = sub.label;
            row.querySelector('input[name="sub_questions[]"]').value = sub.sub_question_text;
            row.querySelector('input[name="sub_correct_answers[]"]').value = sub.correct_answer;
            row.querySelector('input[name="sub_marks[]"]').value = sub.marks;
            container.appendChild(clone);
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

function updateCharCount(el, max) {
    const countEl = el.nextElementSibling?.querySelector('.char-count');
    if (countEl) countEl.textContent = el.value.length;
}
</script>
@endsection

@push('modals')
{{-- Question Modal --}}
<div id="question-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 id="modal-title" class="text-lg font-semibold font-heading">Add Question</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="question-form" class="p-6 space-y-4" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="question-id" value="">

            <div>
                <label class="form-label">Question Type <span class="text-red-500">*</span></label>
                <select id="question_type" name="question_type"
                        class="form-input" onchange="onTypeChange(this.value)">
                    <option value="mcq">Multiple Choice (MCQ)</option>
                    <option value="true_false">True / False</option>
                    <option value="match">Match Items</option>
                    <option value="picture">Picture Question</option>
                    <option value="fill_blank">Fill in the Blank</option>
                    <option value="word_bank">Word Bank</option>
                    <option value="ai_evaluated">Open Ended (AI Graded)</option>
                </select>
            </div>

            {{-- Shared question text (MCQ, True/False, Match only) --}}
            <div id="shared-question-text-wrap">
                <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                <textarea id="question_text" name="question_text" rows="3"
                          class="form-input" placeholder="Enter the question..."></textarea>
            </div>

            <div class="w-32">
                <label class="form-label">Marks <span class="text-red-500">*</span></label>
                <input type="number" id="marks" name="marks" value="1" min="1" class="form-input">
            </div>

            {{-- MCQ Options --}}
            <div id="options-mcq">
                <label class="form-label">Options (select correct answer)</label>
                <div class="space-y-2" id="mcq-options-container">
                    @for($i = 0; $i < 4; $i++)
                    <div class="flex items-center gap-3">
                        <input type="radio" name="correct_option_mcq" value="{{ $i }}"
                               class="text-yellow-400 focus:ring-yellow-400 w-4 h-4" {{ $i === 0 ? 'checked' : '' }}>
                        <input type="text" class="form-input mcq-option-text" placeholder="Option {{ chr(65+$i) }}">
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

            {{-- Picture Question --}}
            <div id="options-picture" class="hidden space-y-4">
                <div>
                    <label class="form-label">Upload Image</label>
                    <input type="file" id="picture-image-input" name="image" accept="image/*" class="form-input">
                    <p class="text-xs text-gray-400 mt-1">Max 2MB. JPG, PNG, GIF only.</p>
                </div>
                <div>
                    <label class="form-label">Sub-Questions</label>
                    <div id="sub-questions-container" class="space-y-2"></div>
                    <button type="button" onclick="addSubQuestion()" class="text-sm text-yellow-600 hover:underline mt-2">+ Add Sub Question</button>
                </div>
                <template id="sub-question-template">
                    <div class="sub-question-row flex gap-2 items-start mt-2 p-3 bg-gray-50 rounded-lg flex-wrap">
                        <select name="sub_labels[]" class="form-input w-16 text-sm">
                            <option>a</option><option>b</option><option>c</option>
                            <option>d</option><option>e</option><option>f</option>
                        </select>
                        <input type="text" name="sub_questions[]" placeholder="Sub-question text" class="form-input flex-1 text-sm">
                        <input type="text" name="sub_correct_answers[]" placeholder="Correct answer" class="form-input flex-1 text-sm">
                        <input type="number" name="sub_marks[]" placeholder="Marks" class="form-input w-20 text-sm" min="1">
                        <button type="button" onclick="this.closest('.sub-question-row').remove()" class="text-red-400 hover:text-red-600 mt-1">✕</button>
                    </div>
                </template>
            </div>

            {{-- Fill in the Blank --}}
            <div id="options-fill_blank" class="hidden space-y-3">
                <div>
                    <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                    <input type="text" id="fill-blank-question" placeholder="The capital of Tanzania is ______" class="form-input">
                    <p class="text-xs text-gray-400 mt-1">Use ______ to indicate blank positions.</p>
                </div>
                <div>
                    <label class="form-label">Correct Answer(s) <span class="text-red-500">*</span></label>
                    <input type="text" id="fill-blank-answer" placeholder="Dodoma" class="form-input">
                    <p class="text-xs text-gray-400 mt-1">For multiple blanks, separate with | e.g. Dodoma|Tanzania</p>
                </div>
                <div>
                    <label class="form-label">Grading Mode</label>
                    <div class="flex gap-5 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" id="fill-grading-exact" name="fill_blank_grading_radio" value="exact"
                                   checked class="text-yellow-400 focus:ring-yellow-400 w-4 h-4">
                            <span class="text-sm font-medium">Exact Match
                                <span class="text-xs text-gray-400 font-normal">(compare saved words)</span>
                            </span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" id="fill-grading-ai" name="fill_blank_grading_radio" value="ai"
                                   class="text-yellow-400 focus:ring-yellow-400 w-4 h-4">
                            <span class="text-sm font-medium">AI Grading
                                <span class="text-xs text-gray-400 font-normal">(Gemini)</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Word Bank --}}
            <div id="options-word_bank" class="hidden space-y-3">
                <div>
                    <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                    <textarea id="word-bank-question" rows="2" class="form-input"
                              placeholder="Match each item with the correct word from the box..."></textarea>
                </div>
                <div>
                    <label class="form-label">Word Bank Items (comma separated) <span class="text-red-500">*</span></label>
                    <input type="text" id="word-bank-items-input" class="form-input"
                           placeholder="Gold, Salt, Iron, Diamond, Coal">
                </div>
                <div>
                    <label class="form-label">Question Items</label>
                    <div id="word-bank-answers-container"></div>
                    <button type="button" onclick="addWordBankItem()" class="text-sm text-yellow-600 hover:underline mt-2">+ Add Question Item</button>
                </div>
            </div>

            {{-- AI Evaluated --}}
            <div id="options-ai_evaluated" class="hidden space-y-3">
                <div>
                    <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                    <textarea id="ai-question" rows="3" class="form-input"
                              placeholder="What is agriculture?"></textarea>
                </div>
                <div>
                    <label class="form-label">Model / Correct Answer (for AI comparison) <span class="text-red-500">*</span></label>
                    <textarea id="ai-correct-answer" rows="3" class="form-input"
                              placeholder="Agriculture is the practice of cultivating land and raising livestock..."></textarea>
                    <p class="text-xs text-gray-400 mt-1">This answer is used by AI to grade the student's response. Students will not see it.</p>
                </div>
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
@endpush
