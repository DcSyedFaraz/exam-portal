@extends('layouts.app')

@section('title', isset($question) ? 'Edit Question' : 'Add Question')
@section('page-title', isset($question) ? 'Edit Question' : 'Add Question')
@section('breadcrumb', 'Admin › Exams › ' . $exam->title . ' › ' . (isset($question) ? 'Edit Question' : 'Add Question'))

@section('content')
@php
    $isEdit = isset($question);
    $type   = $isEdit ? $question->question_type : old('question_type', 'mcq');
    $action = $isEdit
        ? route('admin.questions.update', $question)
        : route('admin.exams.questions.store', $exam);

    // Helpers for old() with edit fallback
    $old = fn(string $key, $default = '') => old($key, $default);
@endphp

<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.exams.questions', $exam) }}" class="btn-secondary text-sm">
            ← Back to Questions
        </a>
        <div>
            <h2 class="text-lg font-bold font-heading text-gray-900">{{ $isEdit ? 'Edit Question' : 'Add New Question' }}</h2>
            <p class="text-sm text-gray-500">{{ $exam->title }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            {{-- Question Type --}}
            <div class="mb-5">
                <label class="form-label">Question Type <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach([
                        'mcq'          => ['label' => 'Multiple Choice',  'icon' => '🔘', 'ai' => false],
                        'true_false'   => ['label' => 'True / False',     'icon' => '✅', 'ai' => false],
                        'match'        => ['label' => 'Match Items',      'icon' => '🔗', 'ai' => false],
                        'fill_blank'   => ['label' => 'Fill in Blank',    'icon' => '✏️', 'ai' => false],
                        'picture'      => ['label' => 'Picture',          'icon' => '🖼️', 'ai' => false],
                        'word_bank'    => ['label' => 'Word Bank',        'icon' => '📚', 'ai' => false],
                        'ai_evaluated' => ['label' => 'Open Ended (AI)',  'icon' => '🤖', 'ai' => true],
                    ] as $val => $meta)
                    @php $isSelected = $type === $val; @endphp
                    <label class="type-card cursor-pointer" onclick="selectTypeCard(this)">
                        <input type="radio" name="question_type" value="{{ $val }}"
                               {{ $isSelected ? 'checked' : '' }}
                               onchange="onTypeChange('{{ $val }}')"
                               class="sr-only">
                        <div class="type-card-inner flex flex-col items-center justify-center gap-1 p-3 rounded-xl border-2 transition text-center relative
                                    {{ $isSelected ? 'border-yellow-400 bg-yellow-50 shadow-sm' : 'border-gray-200 bg-white hover:border-yellow-300' }}">
                            <span class="text-xl">{{ $meta['icon'] }}</span>
                            <span class="text-xs font-medium leading-tight {{ $isSelected ? 'text-yellow-700' : 'text-gray-600' }}">{{ $meta['label'] }}</span>
                            @if($meta['ai'])
                                <span class="absolute top-1 right-1 text-[9px] bg-purple-100 text-purple-600 font-semibold px-1 rounded">AI</span>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Marks --}}
            <div class="mb-5 w-36">
                <label class="form-label">Marks <span class="text-red-500">*</span></label>
                <input type="number" name="marks"
                       value="{{ old('marks', $question->marks ?? 1) }}"
                       min="1" class="form-input">
            </div>

            {{-- Shared question text (MCQ, True/False, Match, AI) --}}
            <div id="wrap-question-text" class="mb-5 {{ in_array($type, ['fill_blank', 'word_bank', 'picture']) ? 'hidden' : '' }}">
                <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                <textarea name="question_text" rows="3" class="form-input"
                          placeholder="Enter the question...">{{ old('question_text', $question->question_text ?? '') }}</textarea>
            </div>

            {{-- ── MCQ ────────────────────────────────────────────────────────── --}}
            <div id="options-mcq" class="{{ $type !== 'mcq' ? 'hidden' : '' }} mb-5">
                <label class="form-label">Options <span class="text-xs text-gray-400">(select the correct answer)</span></label>
                <div class="space-y-2" id="mcq-options-container">
                    @php
                        // On validation error, old('options') is set; on edit, use question options
                        $oldOptions = old('options');
                        if ($oldOptions) {
                            $mcqOptions = collect($oldOptions)->map(fn($o) => (object)[
                                'option_text' => $o['text'] ?? '',
                                'is_correct'  => ($o['is_correct'] ?? '0') == '1',
                            ]);
                        } elseif ($isEdit && $type === 'mcq') {
                            $mcqOptions = $question->options;
                        } else {
                            $mcqOptions = collect();
                        }
                        $oldCorrectMcq = old('correct_option_mcq', null);
                        $mcqCount = max($mcqOptions->count(), 4);
                    @endphp
                    @for($i = 0; $i < $mcqCount; $i++)
                    @php
                        $opt = $mcqOptions->get($i);
                        $isChecked = $oldCorrectMcq !== null
                            ? ((string)$i === (string)$oldCorrectMcq)
                            : ($opt && $opt->is_correct);
                        if ($oldCorrectMcq === null && !$mcqOptions->count() && $i === 0) $isChecked = true;
                    @endphp
                    <div class="flex items-center gap-3 mcq-row">
                        <input type="radio" name="correct_option_mcq" value="{{ $i }}"
                               {{ $isChecked ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-400">
                        <input type="text" name="options[{{ $i }}][text]"
                               value="{{ $opt->option_text ?? '' }}"
                               placeholder="Option {{ chr(65 + $i) }}" class="form-input flex-1">
                        <input type="hidden" name="options[{{ $i }}][is_correct]" value="0" class="mcq-is-correct">
                    </div>
                    @endfor
                </div>
                <button type="button" onclick="addMcqOption()"
                        class="text-sm text-yellow-600 hover:underline mt-2">+ Add option</button>
            </div>

            {{-- ── True / False ─────────────────────────────────────────────── --}}
            <div id="options-true_false" class="{{ $type !== 'true_false' ? 'hidden' : '' }} mb-5">
                <label class="form-label">Correct Answer</label>
                @php
                    if (old('tf_correct') !== null) {
                        $tfCorrect = old('tf_correct');
                    } elseif ($isEdit && $type === 'true_false') {
                        $tfCorrect = strtolower($question->options->firstWhere('is_correct', true)?->option_text ?? 'true');
                    } else {
                        $tfCorrect = 'true';
                    }
                @endphp
                <div class="flex gap-6 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tf_correct" value="true"
                               {{ $tfCorrect === 'true' ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-400">
                        <span class="text-sm font-medium">True</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="tf_correct" value="false"
                               {{ $tfCorrect === 'false' ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-400">
                        <span class="text-sm font-medium">False</span>
                    </label>
                </div>
                <input type="hidden" name="options[0][text]" value="True">
                <input type="hidden" name="options[0][is_correct]" value="0" id="tf-true-correct">
                <input type="hidden" name="options[1][text]" value="False">
                <input type="hidden" name="options[1][is_correct]" value="0" id="tf-false-correct">
            </div>

            {{-- ── Match Pairs ──────────────────────────────────────────────── --}}
            <div id="options-match" class="{{ $type !== 'match' ? 'hidden' : '' }} mb-5">
                <label class="form-label">Match Pairs <span class="text-xs text-gray-400">(left → right)</span></label>
                <div class="space-y-2" id="match-pairs-container">
                    @php
                        $oldMatch = old('options');
                        if ($oldMatch) {
                            $matchRows = collect($oldMatch)->map(fn($o) => (object)[
                                'option_text' => $o['text'] ?? '',
                                'match_pair'  => $o['match_pair'] ?? '',
                            ]);
                        } elseif ($isEdit && $type === 'match') {
                            $matchRows = $question->options;
                        } else {
                            $matchRows = collect([
                                (object)['option_text'=>'','match_pair'=>''],
                                (object)['option_text'=>'','match_pair'=>''],
                                (object)['option_text'=>'','match_pair'=>''],
                            ]);
                        }
                    @endphp
                    @foreach($matchRows as $idx => $row)
                    <div class="flex items-center gap-2 match-row">
                        <input type="text" name="options[{{ $idx }}][text]"
                               value="{{ $row->option_text }}"
                               placeholder="Left item" class="form-input flex-1">
                        <span class="text-gray-400 shrink-0">→</span>
                        <input type="text" name="options[{{ $idx }}][match_pair]"
                               value="{{ $row->match_pair }}"
                               placeholder="Right item" class="form-input flex-1">
                        <input type="hidden" name="options[{{ $idx }}][is_correct]" value="1">
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addMatchPair()"
                        class="text-sm text-yellow-600 hover:underline mt-2">+ Add pair</button>
            </div>

            {{-- ── Fill in the Blank ────────────────────────────────────────── --}}
            <div id="options-fill_blank" class="{{ $type !== 'fill_blank' ? 'hidden' : '' }} mb-5 space-y-4">

                {{-- Question sentence builder --}}
                <div>
                    <label class="form-label">Question Text <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="fb_question_text" id="fill-blank-question"
                               value="{{ old('fb_question_text', $isEdit && $type === 'fill_blank' ? $question->question_text : '') }}"
                               placeholder="The capital of Tanzania is ______"
                               oninput="fbUpdatePreview()"
                               class="form-input flex-1">
                        <button type="button" onclick="fbInsertBlank()"
                                class="shrink-0 px-3 py-2 bg-yellow-400 hover:bg-yellow-500 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">
                            + Blank
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Click <strong>+ Blank</strong> to insert a blank at cursor, or type <code>______</code> manually.</p>
                </div>

                {{-- Live preview with inline answer boxes --}}
                <div id="fb-preview-wrap" class="hidden">
                    <label class="form-label">Correct Answers
                        <span class="text-xs font-normal text-gray-400 ml-1">— type the answer into each box below (this is how students will see it)</span>
                        <span class="text-red-500">*</span>
                    </label>
                    @php
                        // Saved answers for edit / old() repopulation
                        $fbAnswersOld = old('fb_answers', []);
                        $fbSavedRaw   = old('fb_correct_answer',
                            ($isEdit && $type === 'fill_blank') ? ($question->correct_answer_text ?? '') : ''
                        );
                        if (count($fbAnswersOld)) {
                            $fbSavedParts = array_values($fbAnswersOld);
                        } elseif ($fbSavedRaw !== '') {
                            $fbSavedParts = explode('|', $fbSavedRaw);
                        } else {
                            $fbSavedParts = [];
                        }
                    @endphp
                    <div id="fb-preview"
                         data-saved-answers="{{ json_encode($fbSavedParts) }}"
                         class="px-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-xl text-base text-gray-900 leading-loose flex flex-wrap items-center gap-x-1 gap-y-3 min-h-[3.5rem]">
                        {{-- Populated by fbUpdatePreview() --}}
                    </div>
                </div>

                {{-- Grading mode is always exact match for fill in the blank --}}
                <input type="hidden" name="fill_blank_grading" value="exact">
            </div>

            {{-- ── Picture (exact match per sub-question) ───────────────────── --}}
            <div id="options-picture" class="{{ $type !== 'picture' ? 'hidden' : '' }} mb-5 space-y-4">
                <div class="flex items-start gap-2 p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-700">
                    <span class="text-base shrink-0">✓</span>
                    <span>Each sub-question is graded by <strong>exact match</strong> — the student's answer must match your model answer exactly (case-insensitive) to earn marks.</span>
                </div>
                <div>
                    <label class="form-label">Caption <span class="text-xs text-gray-400">(optional — shown above the image)</span></label>
                    <input type="text" name="pic_question_text" id="picture-question"
                           value="{{ old('pic_question_text', $isEdit && $type === 'picture' ? $question->question_text : '') }}"
                           placeholder="Refer to the diagram below and answer all sub-questions." class="form-input">
                </div>
                <div>
                    <label class="form-label">
                        Image
                        @if(!($isEdit && $type === 'picture'))<span class="text-red-500">*</span>@endif
                    </label>
                    @if($isEdit && $type === 'picture' && $question->image_path)
                        <div class="mb-2">
                            <img src="{{ Storage::url($question->image_path) }}" alt="Current image"
                                 class="max-h-40 rounded-lg border border-gray-200 object-contain">
                            <p class="text-xs text-gray-400 mt-1">Upload a new image to replace the current one.</p>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*" class="form-input">
                    <p class="text-xs text-gray-400 mt-1">Max 2 MB · JPG, PNG, GIF</p>
                </div>
                <div>
                    <label class="form-label">Sub-Questions <span class="text-red-500">*</span></label>
                    <div class="space-y-2" id="picture-sub-container">
                        @php
                            // Repopulate sub-questions from old() on validation error
                            $oldSubQ    = old('sub_questions', []);
                            $oldSubAns  = old('sub_correct_answers', []);
                            $oldSubMark = old('sub_marks', []);
                            $oldSubLbl  = old('sub_labels', []);
                            $subRows    = [];

                            if (count($oldSubQ)) {
                                foreach ($oldSubQ as $si => $sq) {
                                    $subRows[] = [
                                        'label'    => $oldSubLbl[$si] ?? chr(97 + $si),
                                        'question' => $sq,
                                        'answer'   => $oldSubAns[$si] ?? '',
                                        'marks'    => $oldSubMark[$si] ?? '',
                                    ];
                                }
                            } elseif ($isEdit && $type === 'picture') {
                                foreach ($question->subItems as $sub) {
                                    $subRows[] = [
                                        'label'    => $sub->label,
                                        'question' => $sub->sub_question_text,
                                        'answer'   => $sub->correct_answer,
                                        'marks'    => $sub->marks,
                                    ];
                                }
                            } else {
                                $subRows[] = ['label'=>'a','question'=>'','answer'=>'','marks'=>''];
                            }
                        @endphp
                        @foreach($subRows as $sr)
                        <div class="flex gap-2 items-start p-3 bg-gray-50 rounded-lg flex-wrap picture-sub-row">
                            <select name="sub_labels[]" class="form-input w-16 text-sm">
                                @foreach(['a','b','c','d','e','f'] as $lbl)
                                <option {{ $sr['label'] === $lbl ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="sub_questions[]" value="{{ $sr['question'] }}"
                                   placeholder="Sub-question" class="form-input flex-1 text-sm">
                            <input type="text" name="sub_correct_answers[]" value="{{ $sr['answer'] }}"
                                   placeholder="Model answer" class="form-input flex-1 text-sm">
                            <input type="number" name="sub_marks[]" value="{{ $sr['marks'] }}"
                                   placeholder="Marks" class="form-input w-20 text-sm" min="1">
                            <button type="button" onclick="this.closest('.picture-sub-row').remove()"
                                    class="text-red-400 hover:text-red-600 mt-1 shrink-0">✕</button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" onclick="addSubQuestion()"
                            class="text-sm text-yellow-600 hover:underline mt-2">+ Add sub-question</button>
                </div>
            </div>

            {{-- ── Word Bank ─────────────────────────────────────────────────── --}}
            <div id="options-word_bank" class="{{ $type !== 'word_bank' ? 'hidden' : '' }} mb-5 space-y-5">

                {{-- How it works info --}}
                <div class="flex items-start gap-2 p-3 bg-cyan-50 border border-cyan-200 rounded-xl text-xs text-cyan-800">
                    <span class="text-base shrink-0">📚</span>
                    <span>Students see a box of words and must drag or select the correct word into each blank. Write each sentence with <code class="bg-cyan-100 px-1 rounded">___</code> where the blank goes, then pick the correct word from your word bank.</span>
                </div>

                {{-- Question instruction text --}}
                <div>
                    <label class="form-label">Instruction Text <span class="text-red-500">*</span></label>
                    <textarea name="wb_question_text" id="word-bank-question" rows="2" class="form-input"
                              placeholder="Use the words in the box to fill in the blanks.">{{ old('wb_question_text', $isEdit && $type === 'word_bank' ? $question->question_text : '') }}</textarea>
                </div>

                {{-- Word Bank chips builder --}}
                <div>
                    <label class="form-label">Word Bank <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-400 ml-1">— these words are shown to students</span>
                    </label>
                    @php
                        $wbItems = old('word_bank_items', $isEdit && $type === 'word_bank'
                            ? (is_array($question->word_bank_items)
                                ? implode(', ', $question->word_bank_items)
                                : ($question->word_bank_items ?? ''))
                            : '');
                    @endphp
                    <div class="flex gap-2 items-center">
                        <input type="text" name="word_bank_items" id="wb-words-input" value="{{ $wbItems }}"
                               placeholder="e.g. Ocean, Forest, Desert, Farm"
                               oninput="wbUpdatePreview()"
                               class="form-input flex-1">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Separate words with commas. These appear as chips the student picks from.</p>
                    {{-- Live word chip preview --}}
                    <div id="wb-words-preview" class="flex flex-wrap gap-2 mt-3 min-h-[2rem]"></div>
                </div>

                {{-- Sentences with blanks --}}
                <div>
                    <label class="form-label">Sentences <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-400 ml-1">— one per row · write the sentence and choose the correct word</span>
                    </label>
                    <div class="space-y-2" id="word-bank-items-container">
                        @php
                            $oldWbOpts = old('options');
                            if ($oldWbOpts) {
                                $wbRows = collect($oldWbOpts)->map(fn($o) => (object)[
                                    'statement'    => $o['statement'] ?? '',
                                    'correct_word' => $o['correct_word'] ?? '',
                                ]);
                            } elseif ($isEdit && $type === 'word_bank') {
                                $wbRows = $question->options->map(fn($o) => (object)[
                                    'statement'    => $o->option_text,
                                    'correct_word' => $o->match_pair,
                                ]);
                            } else {
                                $wbRows = collect([
                                    (object)['statement'=>'','correct_word'=>''],
                                    (object)['statement'=>'','correct_word'=>''],
                                ]);
                            }
                        @endphp
                        @foreach($wbRows as $wi => $wrow)
                        <div class="wb-sentence-row flex gap-2 items-center p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-xs font-bold text-gray-400 w-5 shrink-0 text-center">{{ $wi + 1 }}</span>
                            <input type="text" name="options[{{ $wi }}][statement]"
                                   value="{{ $wrow->statement }}"
                                   placeholder="A fish lives in the ___."
                                   class="form-input flex-1 min-w-0 text-sm">
                            <span class="text-gray-300 shrink-0 text-lg">→</span>
                            <select name="options[{{ $wi }}][correct_word]"
                                    class="wb-correct-select form-input w-36 text-sm shrink-0">
                                <option value="{{ $wrow->correct_word }}" selected>
                                    {{ $wrow->correct_word ?: '— correct word —' }}
                                </option>
                            </select>
                            <button type="button" onclick="this.closest('.wb-sentence-row').remove(); wbRenumber()"
                                    class="text-red-400 hover:text-red-600 shrink-0 text-lg leading-none">×</button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" onclick="wbAddRow()"
                            class="mt-2 text-sm text-yellow-600 hover:underline">+ Add sentence</button>
                </div>
            </div>

            {{-- ── Open Ended / AI Evaluated ────────────────────────────────── --}}
            <div id="options-ai_evaluated" class="{{ $type !== 'ai_evaluated' ? 'hidden' : '' }} mb-5 space-y-4">
                <div class="flex items-start gap-2 p-3 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-700">
                    <span class="text-base shrink-0">🤖</span>
                    <span>Student answers are <strong>graded by AI</strong> — Gemini compares the student's response against your model answer and awards marks.</span>
                </div>
                <div>
                    <label class="form-label">Model / Correct Answer <span class="text-red-500">*</span></label>
                    <textarea name="ai_correct_answer" rows="4" class="form-input"
                              placeholder="Agriculture is the practice of cultivating land and raising livestock for food and resources...">{{ old('ai_correct_answer', $isEdit && $type === 'ai_evaluated' ? $question->correct_answer_text : '') }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Students will not see this — it is only used by AI for grading.</p>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3 pt-2 border-t border-gray-100 mt-6">
                <button type="submit" class="btn-primary">
                    {{ $isEdit ? 'Update Question' : 'Save Question' }}
                </button>
                <a href="{{ route('admin.exams.questions', $exam) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const allTypes = ['mcq', 'true_false', 'match', 'fill_blank', 'picture', 'word_bank', 'ai_evaluated'];

function selectTypeCard(clickedLabel) {
    // Reset all cards to unselected style
    document.querySelectorAll('.type-card').forEach(label => {
        const inner = label.querySelector('.type-card-inner');
        const text  = label.querySelector('span:last-of-type');
        inner.classList.remove('border-yellow-400', 'bg-yellow-50', 'shadow-sm');
        inner.classList.add('border-gray-200', 'bg-white');
        // reset label text colour (the non-AI badge span)
        label.querySelectorAll('.type-card-inner > span:not(.absolute)').forEach(s => {
            s.classList.remove('text-yellow-700');
            s.classList.add('text-gray-600');
        });
    });
    // Apply selected style to clicked card
    const inner = clickedLabel.querySelector('.type-card-inner');
    inner.classList.remove('border-gray-200', 'bg-white');
    inner.classList.add('border-yellow-400', 'bg-yellow-50', 'shadow-sm');
    clickedLabel.querySelectorAll('.type-card-inner > span:not(.absolute)').forEach(s => {
        s.classList.remove('text-gray-600');
        s.classList.add('text-yellow-700');
    });
}

function onTypeChange(type) {
    allTypes.forEach(t => {
        document.getElementById('options-' + t)?.classList.toggle('hidden', t !== type);
    });
    const hiddenForShared = ['fill_blank', 'word_bank', 'picture'];
    document.getElementById('wrap-question-text')?.classList.toggle('hidden', hiddenForShared.includes(type));
}

// ── MCQ: sync radio → hidden is_correct fields ────────────────────────────
document.addEventListener('change', function(e) {
    if (e.target.name === 'correct_option_mcq') {
        document.querySelectorAll('.mcq-is-correct').forEach((el, i) => {
            el.value = (String(i) === e.target.value) ? '1' : '0';
        });
    }
    if (e.target.name === 'tf_correct') {
        document.getElementById('tf-true-correct').value  = e.target.value === 'true'  ? '1' : '0';
        document.getElementById('tf-false-correct').value = e.target.value === 'false' ? '1' : '0';
    }
});

// Set initial is_correct values on page load
(function () {
    const checked = document.querySelector('input[name="correct_option_mcq"]:checked');
    if (checked) {
        document.querySelectorAll('.mcq-is-correct').forEach((el, i) => {
            el.value = (String(i) === checked.value) ? '1' : '0';
        });
    }
    const tfChecked = document.querySelector('input[name="tf_correct"]:checked');
    if (tfChecked) {
        document.getElementById('tf-true-correct').value  = tfChecked.value === 'true'  ? '1' : '0';
        document.getElementById('tf-false-correct').value = tfChecked.value === 'false' ? '1' : '0';
    }
})();

// ── Fill in the Blank helpers ──────────────────────────────────────────────
function fbInsertBlank() {
    const input = document.getElementById('fill-blank-question');
    const start = input.selectionStart ?? input.value.length;
    const end   = input.selectionEnd   ?? input.value.length;
    const blank = '______';
    input.value = input.value.slice(0, start) + blank + input.value.slice(end);
    input.selectionStart = input.selectionEnd = start + blank.length;
    input.focus();
    fbUpdatePreview();
}

function fbUpdatePreview() {
    const questionInput = document.getElementById('fill-blank-question');
    const wrap          = document.getElementById('fb-preview-wrap');
    const preview       = document.getElementById('fb-preview');
    const val           = questionInput.value;

    if (!val.trim()) { wrap.classList.add('hidden'); return; }
    wrap.classList.remove('hidden');

    // Save existing answer values keyed by index before rebuilding
    const existing = {};
    preview.querySelectorAll('input[name="fb_answers[]"]').forEach((el, i) => {
        existing[i] = el.value;
    });

    // Clear and rebuild preview with inline inputs at each blank
    preview.innerHTML = '';
    let blankIdx = 0;
    val.split(/_{4,}/).forEach((segment, segIdx) => {
        // Text segment
        if (segment) {
            const span = document.createElement('span');
            span.textContent = segment;
            preview.appendChild(span);
        }
        // Blank input (between every two segments)
        if (segIdx < val.split(/_{4,}/).length - 1) {
            const idx = blankIdx;
            const inp = document.createElement('input');
            inp.type        = 'text';
            inp.name        = 'fb_answers[]';
            inp.placeholder = 'answer ' + (idx + 1);
            inp.value       = existing[idx] ?? '';
            inp.autocomplete = 'off';
            inp.className   = 'inline-block w-32 px-3 py-1 border-b-2 border-yellow-400 bg-yellow-50 rounded-lg text-sm font-medium text-gray-900 text-center focus:outline-none focus:border-yellow-600 focus:bg-yellow-100 transition mx-1';
            preview.appendChild(inp);
            blankIdx++;
        }
    });
}

// Init on page load — rebuild preview with saved answers (edit / old() after validation fail)
(function fbInit() {
    const questionInput = document.getElementById('fill-blank-question');
    const preview       = document.getElementById('fb-preview');
    if (!questionInput || !preview) return;

    const savedParts = preview.dataset.savedAnswers ? JSON.parse(preview.dataset.savedAnswers) : [];

    if (questionInput.value) {
        fbUpdatePreview();
        // After preview is built, fill in saved answer values
        preview.querySelectorAll('input[name="fb_answers[]"]').forEach((el, i) => {
            if (savedParts[i] !== undefined) el.value = savedParts[i];
        });
    }
})();

// ── Add MCQ option ─────────────────────────────────────────────────────────
function addMcqOption() {
    const container = document.getElementById('mcq-options-container');
    const idx = container.querySelectorAll('.mcq-row').length;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-3 mcq-row';
    div.innerHTML = `
        <input type="radio" name="correct_option_mcq" value="${idx}" class="w-4 h-4 text-yellow-400">
        <input type="text" name="options[${idx}][text]"
               placeholder="Option ${String.fromCharCode(65 + idx)}" class="form-input flex-1">
        <input type="hidden" name="options[${idx}][is_correct]" value="0" class="mcq-is-correct">`;
    container.appendChild(div);
}

// ── Add Match Pair ─────────────────────────────────────────────────────────
function addMatchPair() {
    const container = document.getElementById('match-pairs-container');
    const idx = container.querySelectorAll('.match-row').length;
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 match-row';
    div.innerHTML = `
        <input type="text" name="options[${idx}][text]" placeholder="Left item" class="form-input flex-1">
        <span class="text-gray-400 shrink-0">→</span>
        <input type="text" name="options[${idx}][match_pair]" placeholder="Right item" class="form-input flex-1">
        <input type="hidden" name="options[${idx}][is_correct]" value="1">`;
    container.appendChild(div);
}

// ── Add Picture Sub-Question ───────────────────────────────────────────────
function addSubQuestion() {
    const container = document.getElementById('picture-sub-container');
    const opts = ['a','b','c','d','e','f'].map(l => `<option>${l}</option>`).join('');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-start p-3 bg-gray-50 rounded-lg flex-wrap picture-sub-row';
    div.innerHTML = `
        <select name="sub_labels[]" class="form-input w-16 text-sm">${opts}</select>
        <input type="text" name="sub_questions[]" placeholder="Sub-question" class="form-input flex-1 text-sm">
        <input type="text" name="sub_correct_answers[]" placeholder="Model answer" class="form-input flex-1 text-sm">
        <input type="number" name="sub_marks[]" placeholder="Marks" class="form-input w-20 text-sm" min="1">
        <button type="button" onclick="this.closest('.picture-sub-row').remove()"
                class="text-red-400 hover:text-red-600 mt-1 shrink-0">✕</button>`;
    container.appendChild(div);
}

// ── Word Bank helpers ──────────────────────────────────────────────────────
function wbGetWords() {
    const raw = document.getElementById('wb-words-input')?.value ?? '';
    return raw.split(',').map(w => w.trim()).filter(Boolean);
}

function wbBuildSelect(selectedValue = '') {
    const words = wbGetWords();
    let opts = `<option value="">— correct word —</option>`;
    words.forEach(w => {
        opts += `<option value="${w}"${w === selectedValue ? ' selected' : ''}>${w}</option>`;
    });
    return opts;
}

function wbUpdatePreview() {
    // Word chips preview
    const preview = document.getElementById('wb-words-preview');
    if (preview) {
        const words = wbGetWords();
        preview.innerHTML = words.length
            ? words.map(w => `<span class="px-3 py-1 bg-cyan-100 border border-cyan-300 rounded-full text-sm font-medium text-cyan-800">${w}</span>`).join('')
            : '<span class="text-xs text-gray-400 italic">Words will appear here as chips…</span>';
    }
    // Refresh all correct-word selects with updated word list
    document.querySelectorAll('.wb-correct-select').forEach(sel => {
        const current = sel.value;
        sel.innerHTML = wbBuildSelect(current);
    });
}

function wbRenumber() {
    document.querySelectorAll('#word-bank-items-container .wb-sentence-row').forEach((row, i) => {
        const num = row.querySelector('.text-gray-400.w-5');
        if (num) num.textContent = i + 1;
        // Re-index names
        row.querySelector('input[name^="options"]').name = `options[${i}][statement]`;
        row.querySelector('select[name^="options"]').name = `options[${i}][correct_word]`;
    });
}

function wbAddRow() {
    const container = document.getElementById('word-bank-items-container');
    const idx = container.querySelectorAll('.wb-sentence-row').length;
    const div = document.createElement('div');
    div.className = 'wb-sentence-row flex gap-2 items-center p-3 bg-gray-50 rounded-xl border border-gray-100';
    div.innerHTML = `
        <span class="text-xs font-bold text-gray-400 w-5 shrink-0 text-center">${idx + 1}</span>
        <input type="text" name="options[${idx}][statement]"
               placeholder="A fish lives in the ___."
               class="form-input flex-1 min-w-0 text-sm">
        <span class="text-gray-300 shrink-0 text-lg">→</span>
        <select name="options[${idx}][correct_word]" class="wb-correct-select form-input w-36 text-sm shrink-0">
            ${wbBuildSelect()}
        </select>
        <button type="button" onclick="this.closest('.wb-sentence-row').remove(); wbRenumber()"
                class="text-red-400 hover:text-red-600 shrink-0 text-lg leading-none">×</button>`;
    container.appendChild(div);
}

// Init word bank preview on page load (for edit mode)
(function wbInit() {
    if (document.getElementById('wb-words-input')) {
        wbUpdatePreview();
    }
})();
</script>
@endsection
