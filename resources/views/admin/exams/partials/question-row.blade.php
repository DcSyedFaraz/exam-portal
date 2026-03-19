<div class="question-row border border-gray-100 rounded-xl p-4 bg-gray-50 hover:bg-yellow-50/50 transition-colors"
     id="question-{{ $question->id }}" data-question-id="{{ $question->id }}">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="badge-pending text-xs">
                    {{ strtoupper(str_replace('_', '/', $question->question_type)) }}
                </span>
                <span class="text-xs text-gray-400">{{ $question->marks }} mark(s)</span>
            </div>
            <p class="text-sm font-medium text-gray-900">{{ $question->question_text }}</p>
            <ul class="mt-2 space-y-0.5">
                @foreach($question->options as $option)
                <li class="text-xs flex items-center gap-1.5
                    {{ $option->is_correct ? 'text-green-700 font-semibold' : 'text-gray-500' }}">
                    @if($option->is_correct)
                        <svg class="w-3 h-3 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <span class="w-3 h-3 shrink-0"></span>
                    @endif
                    {{ $option->option_text }}
                    @if($option->match_pair)
                        <span class="text-gray-400">→ {{ $option->match_pair }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                onclick="editQuestion({{ $question->id }})"
                class="text-xs bg-yellow-100 text-yellow-700 px-2.5 py-1.5 rounded hover:bg-yellow-200 transition edit-question-btn"
                data-question='@json($question->load("options"))'>
                Edit
            </button>
            <button type="button"
                onclick="deleteQuestion({{ $question->id }}, this)"
                data-url="{{ route('admin.questions.destroy', $question) }}"
                class="text-xs bg-red-100 text-red-700 px-2.5 py-1.5 rounded hover:bg-red-200 transition">
                Delete
            </button>
        </div>
    </div>
</div>
