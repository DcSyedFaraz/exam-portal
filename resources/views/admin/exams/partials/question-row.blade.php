@php
    $typeLabels = [
        'mcq'          => ['label' => 'MCQ',            'color' => 'bg-blue-100 text-blue-700'],
        'true_false'   => ['label' => 'True / False',   'color' => 'bg-teal-100 text-teal-700'],
        'match'        => ['label' => 'Match',           'color' => 'bg-indigo-100 text-indigo-700'],
        'fill_blank'   => ['label' => 'Fill in Blank',  'color' => 'bg-orange-100 text-orange-700'],
        'picture'      => ['label' => 'Picture',         'color' => 'bg-pink-100 text-pink-700'],
        'word_bank'    => ['label' => 'Word Bank',       'color' => 'bg-cyan-100 text-cyan-700'],
        'ai_evaluated' => ['label' => 'Open Ended',     'color' => 'bg-purple-100 text-purple-700'],
    ];
    $meta = $typeLabels[$question->question_type] ?? ['label' => strtoupper($question->question_type), 'color' => 'bg-gray-100 text-gray-600'];
@endphp

<div class="question-row border border-gray-100 rounded-xl p-4 bg-white hover:bg-yellow-50/40 transition-colors"
     id="question-{{ $question->id }}">
    <div class="flex items-start justify-between gap-4">

        {{-- Left: content --}}
        <div class="flex-1 min-w-0">

            {{-- Type badge + marks + grading badge --}}
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $meta['color'] }}">
                    {{ $meta['label'] }}
                </span>
                <span class="text-xs text-gray-400">{{ $question->marks }} mark{{ $question->marks != 1 ? 's' : '' }}</span>

                @if($question->question_type === 'fill_blank')
                    <span class="text-[11px] bg-blue-50 text-blue-600 border border-blue-200 px-1.5 py-0.5 rounded-full">✓ Exact match</span>
                @elseif($question->isAiGraded())
                    <span class="text-[11px] bg-purple-50 text-purple-600 border border-purple-200 px-1.5 py-0.5 rounded-full">🤖 AI graded</span>
                @endif
            </div>

            {{-- Question text --}}
            @if($question->question_text)
                <p class="text-sm font-medium text-gray-900 mb-2">{{ $question->question_text }}</p>
            @endif

            {{-- Picture: thumbnail + sub-questions --}}
            @if($question->question_type === 'picture')
                <div class="flex items-start gap-3 mt-1">
                    @if($question->image_path)
                    <button type="button"
                            onclick="openImageModal('{{ Storage::url($question->image_path) }}')"
                            class="shrink-0 group relative">
                        <img src="{{ Storage::url($question->image_path) }}"
                             alt="Question image"
                             class="w-16 h-16 rounded-lg border border-gray-200 object-cover group-hover:opacity-80 transition">
                        <span class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <svg class="w-5 h-5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0zm-3-3l3 3"/>
                            </svg>
                        </span>
                    </button>
                    @endif
                    @if($question->subItems->count())
                    <ul class="space-y-1 flex-1 min-w-0">
                        @foreach($question->subItems as $sub)
                        <li class="text-xs text-gray-600 flex items-baseline gap-1">
                            <span class="shrink-0 font-semibold text-gray-400">({{ $sub->label }})</span>
                            <span class="truncate">{{ $sub->sub_question_text }}</span>
                            <span class="shrink-0 text-gray-400 ml-auto pl-2">{{ $sub->marks }} mk</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

            {{-- Word Bank --}}
            @elseif($question->question_type === 'word_bank')
                @if($question->word_bank_items)
                <div class="flex flex-wrap gap-1 mt-1 mb-2">
                    @foreach($question->word_bank_items as $word)
                    <span class="text-[11px] px-2 py-0.5 bg-yellow-50 border border-yellow-200 rounded-full text-gray-600">{{ $word }}</span>
                    @endforeach
                </div>
                @endif
                @if($question->options->count())
                <ul class="space-y-0.5">
                    @foreach($question->options as $opt)
                    <li class="text-xs text-gray-600 flex items-center gap-1">
                        <span class="text-gray-400">—</span>
                        {{ $opt->option_text }}
                        <span class="text-gray-300 mx-1">→</span>
                        <span class="font-medium text-green-700">{{ $opt->match_pair }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif

            {{-- MCQ / True-False / Match --}}
            @elseif(in_array($question->question_type, ['mcq','true_false','match']))
                <ul class="space-y-0.5 mt-1">
                    @foreach($question->options as $option)
                    <li class="text-xs flex items-center gap-1.5 {{ $option->is_correct ? 'text-green-700 font-semibold' : 'text-gray-500' }}">
                        @if($option->is_correct)
                            <svg class="w-3 h-3 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="w-3 h-3 rounded-full border border-gray-300 shrink-0 inline-block"></span>
                        @endif
                        {{ $option->option_text }}
                        @if($option->match_pair)
                            <span class="text-gray-400 font-normal">→ {{ $option->match_pair }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>

            {{-- Fill in Blank --}}
            @elseif($question->question_type === 'fill_blank')
                @if($question->fill_blank_instructions)
                <p class="text-xs text-blue-500 italic mt-1">📝 {{ $question->fill_blank_instructions }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">
                    Answer: <span class="font-medium text-gray-700">{{ $question->correct_answer_text }}</span>
                </p>

            {{-- AI Evaluated --}}
            @elseif($question->question_type === 'ai_evaluated')
                <p class="text-xs text-gray-400 mt-1 line-clamp-2 italic">
                    Model answer: {{ Str::limit($question->correct_answer_text, 120) }}
                </p>
            @endif

        </div>

        {{-- Right: actions --}}
        <div class="flex flex-col items-end gap-2 shrink-0">
            <a href="{{ route('admin.questions.edit', $question) }}"
               class="text-xs bg-yellow-100 text-yellow-700 px-3 py-1.5 rounded-lg hover:bg-yellow-200 transition font-medium">
                Edit
            </a>
            <button type="button"
                    onclick="deleteQuestion({{ $question->id }})"
                    class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition font-medium">
                Delete
            </button>
        </div>

    </div>
</div>
