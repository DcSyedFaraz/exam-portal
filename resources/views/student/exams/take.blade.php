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
                            @if($question->question_type === 'fill_blank' && $question->fill_blank_instructions)
                            <p class="text-xs text-blue-600 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 mb-3">
                                📝 {{ $question->fill_blank_instructions }}
                            </p>
                            @endif
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

                            {{-- Picture --}}
                            @elseif($question->question_type === 'picture')
                                @if($question->image_path)
                                    <div class="relative mb-4 group cursor-zoom-in" onclick="openLightbox('{{ Storage::url($question->image_path) }}')">
                                        <img src="{{ Storage::url($question->image_path) }}"
                                             alt="Question Image"
                                             class="w-full max-h-72 object-contain rounded-lg border border-gray-200 transition group-hover:brightness-95">
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition pointer-events-none">
                                            <span class="bg-black/50 text-white text-xs font-semibold px-3 py-1.5 rounded-full">🔍 Click to enlarge</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="space-y-3">
                                    @foreach($question->subItems as $sub)
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <label class="form-label text-gray-600 text-sm">({{ $sub->label }}) {{ $sub->sub_question_text }}
                                            <span class="text-xs text-gray-400">({{ $sub->marks }} mark{{ $sub->marks !== 1 ? 's' : '' }})</span>
                                        </label>
                                        <textarea
                                            name="answers[{{ $question->id }}][sub][{{ $sub->id }}]"
                                            rows="2"
                                            maxlength="500"
                                            class="form-input mt-1 text-sm"
                                            placeholder="Your answer..."
                                            onchange="onAnswerChange({{ $question->id }})"></textarea>
                                    </div>
                                    @endforeach
                                </div>

                            {{-- Fill in the Blank --}}
                            @elseif($question->question_type === 'fill_blank')
                            @php
                                // Split sentence on blanks — odd indices are blanks
                                $fbParts = preg_split('/(_{4,})/', $question->question_text, -1, PREG_SPLIT_DELIM_CAPTURE);
                                $fbBlankIndex = 0;
                            @endphp
                                <div class="text-base leading-loose text-gray-900 flex flex-wrap items-center gap-x-1 gap-y-2">
                                    @foreach($fbParts as $fbPart)
                                        @if(preg_match('/^_{4,}$/', $fbPart))
                                            {{-- Inline input for this blank --}}
                                            <input type="text"
                                                   name="answers[{{ $question->id }}][fb][{{ $fbBlankIndex }}]"
                                                   autocomplete="off"
                                                   maxlength="100"
                                                   placeholder="blank {{ $fbBlankIndex + 1 }}"
                                                   oninput="fbCheckAnswered({{ $question->id }})"
                                                   class="inline-block w-32 px-3 py-1 border-b-2 border-yellow-400 bg-yellow-50 rounded-lg text-sm font-medium text-gray-900 text-center focus:outline-none focus:border-yellow-500 focus:bg-yellow-100 transition">
                                            @php $fbBlankIndex++ @endphp
                                        @else
                                            <span>{{ $fbPart }}</span>
                                        @endif
                                    @endforeach
                                </div>

                            {{-- Word Bank --}}
                            @elseif($question->question_type === 'word_bank')
                            @php $qid = $question->id; $wbWords = $question->word_bank_items ?? []; @endphp
                                {{-- Word chip tray --}}
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Word Bank</p>
                                <div id="wb-tray-{{ $qid }}"
                                     class="flex flex-wrap gap-2 p-3 bg-yellow-50 border-2 border-yellow-200 rounded-xl mb-5 min-h-[3rem]">
                                    @foreach($wbWords as $word)
                                    <button type="button"
                                            class="wb-chip px-3 py-1.5 bg-white border-2 border-yellow-400 rounded-full text-sm font-semibold text-yellow-800 hover:bg-yellow-400 hover:text-white transition select-none shadow-sm"
                                            data-word="{{ $word }}"
                                            data-qid="{{ $qid }}"
                                            onclick="wbChipClick(this)">
                                        {{ $word }}
                                    </button>
                                    @endforeach
                                </div>

                                {{-- Sentences with blank slots --}}
                                <div class="space-y-3">
                                    @foreach($question->options as $option)
                                    @php $oid = $option->id; @endphp
                                    {{-- Hidden input that actually submits the answer --}}
                                    <input type="hidden"
                                           name="answers[{{ $qid }}][word_bank][{{ $oid }}]"
                                           id="wb-val-{{ $qid }}-{{ $oid }}"
                                           value="">
                                    <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-100 flex-wrap">
                                        <span class="text-sm text-gray-800 flex-1 min-w-0">{{ $option->option_text }}</span>
                                        {{-- Drop slot --}}
                                        <div id="wb-slot-{{ $qid }}-{{ $oid }}"
                                             class="wb-slot relative flex items-center justify-center min-w-[110px] h-9 border-2 border-dashed border-gray-300 rounded-full bg-white cursor-pointer text-xs text-gray-400 transition hover:border-yellow-400"
                                             data-qid="{{ $qid }}"
                                             data-oid="{{ $oid }}"
                                             onclick="wbSlotClick(this)"
                                             title="Click to pick a word">
                                            <span class="wb-slot-label select-none">tap to fill</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                            {{-- AI Evaluated (Open Ended) --}}
                            @elseif($question->question_type === 'ai_evaluated')
                                <textarea
                                    name="answers[{{ $question->id }}][text]"
                                    rows="5"
                                    maxlength="1000"
                                    class="form-input"
                                    placeholder="Write your answer here..."
                                    oninput="onAnswerChange({{ $question->id }}); updateCharCount(this)"></textarea>
                                <p class="text-xs text-gray-400 mt-1 text-right">
                                    <span class="char-count">0</span>/1000 characters
                                </p>
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

        function updateCharCount(el) {
            const countEl = el.nextElementSibling?.querySelector('.char-count');
            if (countEl) countEl.textContent = el.value.length;
        }

        // Highlight selected radio labels on load and on change
        document.querySelectorAll('.answer-radio').forEach(radio => {
            if (radio.checked) {
                radio.closest('.answer-label')?.classList.add('border-yellow-400', 'bg-yellow-50');
            }
            radio.addEventListener('change', function() {
                const name = this.name;
                document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                    r.closest('.answer-label')?.classList.remove('border-yellow-400', 'bg-yellow-50');
                });
                this.closest('.answer-label')?.classList.add('border-yellow-400', 'bg-yellow-50');
            });
        });

        // ── Fill in the Blank: mark answered when all inline inputs have a value ─
        function fbCheckAnswered(questionId) {
            const inputs = document.querySelectorAll(`input[name^="answers[${questionId}][fb]"]`);
            const allFilled = [...inputs].every(i => i.value.trim() !== '');
            if (allFilled && inputs.length > 0) onAnswerChange(questionId);
        }

        // ── Word Bank: click-to-fill interaction ───────────────────────────────
        // State: which slot is currently "active" (waiting for a word pick)
        let wbActiveSlot = null; // { qid, oid, slotEl }

        function wbChipClick(chipEl) {
            const word = chipEl.dataset.word;
            const qid  = chipEl.dataset.qid;

            if (wbActiveSlot && wbActiveSlot.qid === qid) {
                // A slot is waiting — fill it with this word
                wbFillSlot(wbActiveSlot.slotEl, wbActiveSlot.qid, wbActiveSlot.oid, word, chipEl);
                wbClearActive();
            } else {
                // No active slot: highlight this chip so student knows to pick a slot next
                wbClearActive();
                chipEl.classList.add('ring-2', 'ring-yellow-500', 'bg-yellow-400', 'text-white');
                wbActiveSlot = { qid, oid: null, slotEl: null, chipEl, word };
            }
        }

        function wbSlotClick(slotEl) {
            const qid = slotEl.dataset.qid;
            const oid = slotEl.dataset.oid;

            if (wbActiveSlot && wbActiveSlot.word && wbActiveSlot.qid === qid) {
                // A chip is already selected — fill this slot
                wbFillSlot(slotEl, qid, oid, wbActiveSlot.word, wbActiveSlot.chipEl);
                wbClearActive();
            } else {
                // No chip selected: if slot is filled, return word to tray
                const currentVal = document.getElementById('wb-val-' + qid + '-' + oid)?.value;
                if (currentVal) {
                    wbReturnWordToTray(qid, currentVal);
                    wbEmptySlot(slotEl, qid, oid);
                    wbCheckAllFilled(qid);
                } else {
                    // Highlight slot to show it's waiting
                    wbClearActive();
                    slotEl.classList.add('border-yellow-400', 'bg-yellow-50');
                    wbActiveSlot = { qid, oid, slotEl, chipEl: null, word: null };
                }
            }
        }

        function wbFillSlot(slotEl, qid, oid, word, chipEl) {
            // If slot already had a word, return it to tray first
            const valInput = document.getElementById('wb-val-' + qid + '-' + oid);
            if (valInput && valInput.value) {
                wbReturnWordToTray(qid, valInput.value);
            }
            // Fill slot visually
            slotEl.innerHTML = `
                <span class="text-sm font-semibold text-yellow-800 px-2">${word}</span>
                <button type="button" onclick="wbClearSlot('${qid}','${oid}')"
                        class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-400 text-white rounded-full text-[10px] leading-none flex items-center justify-center hover:bg-red-600">×</button>`;
            slotEl.classList.remove('border-dashed', 'border-gray-300', 'bg-white', 'border-yellow-400', 'bg-yellow-50');
            slotEl.classList.add('border-yellow-400', 'bg-yellow-100', 'border-solid');
            // Set hidden input value
            if (valInput) valInput.value = word;
            // Hide chip from tray
            if (chipEl) chipEl.classList.add('opacity-30', 'pointer-events-none');
            else wbHideChipByWord(qid, word);
            wbCheckAllFilled(qid);
        }

        function wbClearSlot(qid, oid) {
            const slotEl  = document.getElementById('wb-slot-' + qid + '-' + oid);
            const valInput = document.getElementById('wb-val-' + qid + '-' + oid);
            const word = valInput?.value;
            if (word) wbReturnWordToTray(qid, word);
            wbEmptySlot(slotEl, qid, oid);
            wbCheckAllFilled(qid);
        }

        function wbEmptySlot(slotEl, qid, oid) {
            const valInput = document.getElementById('wb-val-' + qid + '-' + oid);
            if (valInput) valInput.value = '';
            slotEl.innerHTML = '<span class="wb-slot-label select-none">tap to fill</span>';
            slotEl.classList.remove('border-yellow-400', 'bg-yellow-100', 'border-solid');
            slotEl.classList.add('border-dashed', 'border-gray-300', 'bg-white');
        }

        function wbHideChipByWord(qid, word) {
            const tray = document.getElementById('wb-tray-' + qid);
            tray?.querySelectorAll('.wb-chip').forEach(chip => {
                if (chip.dataset.word === word && !chip.classList.contains('pointer-events-none')) {
                    chip.classList.add('opacity-30', 'pointer-events-none');
                }
            });
        }

        function wbReturnWordToTray(qid, word) {
            const tray = document.getElementById('wb-tray-' + qid);
            // Re-enable the FIRST matching grayed-out chip
            const chip = [...(tray?.querySelectorAll('.wb-chip') ?? [])].find(
                c => c.dataset.word === word && c.classList.contains('pointer-events-none')
            );
            if (chip) {
                chip.classList.remove('opacity-30', 'pointer-events-none');
            }
        }

        function wbClearActive() {
            if (wbActiveSlot?.chipEl) {
                wbActiveSlot.chipEl.classList.remove('ring-2', 'ring-yellow-500', 'bg-yellow-400', 'text-white');
            }
            if (wbActiveSlot?.slotEl) {
                wbActiveSlot.slotEl.classList.remove('border-yellow-400', 'bg-yellow-50');
            }
            wbActiveSlot = null;
        }

        function wbCheckAllFilled(qid) {
            // Mark question as answered if every slot has a value
            const allFilled = [...document.querySelectorAll(`[id^="wb-val-${qid}-"]`)]
                .every(inp => inp.value !== '');
            if (allFilled) onAnswerChange(parseInt(qid));
        }

        // Click outside clears active chip/slot selection
        document.addEventListener('click', function(e) {
            if (wbActiveSlot && !e.target.closest('.wb-chip') && !e.target.closest('.wb-slot')) {
                wbClearActive();
            }
        });

        // ── Image Lightbox (full-screen) ─────────────────────────────────────
        let lbScale = 1;
        let lbBaseScale = 1; // scale that makes the image fit the screen

        function openLightbox(src) {
            const lb  = document.getElementById('img-lightbox');
            const img = document.getElementById('lb-img');

            // Reset state
            lbScale     = 1;
            lbBaseScale = 1;
            img.style.transform = '';
            img.style.width     = '';
            img.style.height    = '';

            img.src = src;
            lb.classList.remove('hidden');
            lb.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Once image loads, size it to fill the viewport
            img.onload = function () {
                lbFitToScreen();
                document.getElementById('lb-zoom-label').textContent = 'Fit';
            };
        }

        function lbFitToScreen() {
            const img = document.getElementById('lb-img');
            const vw  = window.innerWidth;
            const vh  = window.innerHeight;
            const nw  = img.naturalWidth;
            const nh  = img.naturalHeight;
            if (!nw || !nh) return;

            // Fill the screen: scale so the image covers as much viewport as possible
            // while still being fully visible (object-contain logic)
            const ratio = Math.min(vw / nw, vh / nh);
            img.style.width  = Math.round(nw * ratio) + 'px';
            img.style.height = Math.round(nh * ratio) + 'px';
            img.style.transform = 'scale(1)';
            lbBaseScale = ratio;
            lbScale = 1;
        }

        function closeLightbox(e) {
            // Close only when clicking the backdrop/scroll area (not the controls)
            if (e && e.target.closest('button, [id="lb-zoom-label"]')) return;
            if (e && e.target !== document.getElementById('img-lightbox')
                && e.target !== document.getElementById('lb-scroll')) return;
            _doCloseLightbox();
        }

        function _doCloseLightbox() {
            const lb = document.getElementById('img-lightbox');
            lb.classList.add('hidden');
            lb.classList.remove('flex');
            document.body.style.overflow = '';
            const img = document.getElementById('lb-img');
            img.src = '';
            img.onload = null;
        }

        function lbZoom(delta) {
            lbScale = Math.min(5, Math.max(0.5, lbScale + delta));
            document.getElementById('lb-img').style.transform = `scale(${lbScale})`;
            document.getElementById('lb-zoom-label').textContent =
                lbScale === 1 ? 'Fit' : Math.round(lbScale * 100) + '%';
        }

        function lbReset() {
            lbScale = 1;
            document.getElementById('lb-img').style.transform = 'scale(1)';
            document.getElementById('lb-zoom-label').textContent = 'Fit';
            // Re-center scroll
            const scroll = document.getElementById('lb-scroll');
            scroll.scrollTop  = 0;
            scroll.scrollLeft = 0;
        }

        // Mouse-wheel zoom inside lightbox
        document.getElementById('lb-scroll')?.addEventListener('wheel', function(e) {
            if (!document.getElementById('img-lightbox').classList.contains('flex')) return;
            e.preventDefault();
            lbZoom(e.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });

        // Close lightbox with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') _doCloseLightbox();
        });
    </script>
@endsection

@push('modals')
    {{-- Image Lightbox (full-screen) --}}
    <div id="img-lightbox"
         class="fixed inset-0 z-[70] hidden items-center justify-center bg-black"
         onclick="closeLightbox(event)">

        {{-- Close button --}}
        <button onclick="_doCloseLightbox()"
                class="absolute top-3 right-3 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white text-2xl flex items-center justify-center z-20 transition"
                aria-label="Close">&times;</button>

        {{-- Zoom controls --}}
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20 bg-black/60 backdrop-blur-sm rounded-full px-4 py-2">
            <button onclick="lbZoom(-0.25); event.stopPropagation()"
                    class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/30 text-white text-lg flex items-center justify-center transition">−</button>
            <span id="lb-zoom-label" class="text-white/80 text-xs w-14 text-center font-mono">Fit</span>
            <button onclick="lbZoom(+0.25); event.stopPropagation()"
                    class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/30 text-white text-lg flex items-center justify-center transition">+</button>
            <div class="w-px h-5 bg-white/20 mx-1"></div>
            <button onclick="lbReset(); event.stopPropagation()"
                    class="text-white/60 hover:text-white text-xs transition">Fit</button>
        </div>

        {{-- Full-screen scrollable image container --}}
        <div id="lb-scroll" class="w-full h-full overflow-auto flex items-center justify-center">
            <img id="lb-img" src="" alt="Enlarged image"
                 class="select-none transition-transform duration-150"
                 style="transform-origin: center center;"
                 draggable="false">
        </div>
    </div>

    {{-- Confirm Submit Modal --}}
    <div id="confirm-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
            <span class="text-4xl mb-3 block">🚀</span>
            <h3 class="text-lg font-bold font-heading text-gray-900 mb-2">Submit Exam?</h3>
            <p class="text-gray-500 text-sm mb-6">Once submitted, you cannot change your answers.</p>
            <div class="flex gap-3">
                <button onclick="doSubmitExam()" class="btn-primary flex-1 justify-center">
                    Yes, Submit
                </button>
                <button onclick="document.getElementById('confirm-modal').classList.add('hidden')"
                    class="btn-secondary flex-1 justify-center">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    {{-- Grading Overlay (shown while form POST is processing) --}}
    <div id="submit-overlay" class="fixed inset-0 bg-black/75 z-[60] hidden flex-col items-center justify-center gap-5 text-center px-6">
        <svg class="animate-spin w-14 h-14 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <div>
            <p class="text-white text-xl font-bold mb-1">Grading your exam…</p>
            <p class="text-white/70 text-sm">Please do not close or refresh this window.</p>
        </div>
    </div>

    <script>
        function doSubmitExam() {
            // Hide confirm modal
            document.getElementById('confirm-modal').classList.add('hidden');
            // Show grading overlay
            const overlay = document.getElementById('submit-overlay');
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            // Submit the form
            document.getElementById('exam-form').submit();
        }
    </script>
@endpush
