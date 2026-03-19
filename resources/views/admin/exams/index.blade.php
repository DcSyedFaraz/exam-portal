@extends('layouts.app')

@section('title', 'Exams')
@section('page-title', 'Exams')
@section('breadcrumb', 'Admin › Exams')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $exams->total() }} exam(s) total</p>
    <a href="{{ route('admin.exams.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Exam
    </a>
</div>

<div class="card">
    @if($exams->isEmpty())
        <div class="text-center py-12">
            <span class="text-5xl">📝</span>
            <p class="text-gray-500 mt-3">No exams yet. Create your first exam!</p>
            <a href="{{ route('admin.exams.create') }}" class="btn-primary mt-4 inline-flex">Create Exam</a>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left">Title</th>
                        <th class="px-4 py-3 text-center">Questions</th>
                        <th class="px-4 py-3 text-center">Duration</th>
                        <th class="px-4 py-3 text-center">Passing</th>
                        <th class="px-4 py-3 text-center">Published</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exams as $i => $exam)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $exam->title }}</p>
                            @if($exam->description)
                            <p class="text-gray-400 text-xs truncate max-w-xs">{{ $exam->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $exam->questions_count }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $exam->duration_minutes }} min</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $exam->passing_marks }}/{{ $exam->total_marks }}</td>
                        <td class="px-4 py-3 text-center">
                            <button
                                class="publish-toggle relative inline-flex h-6 w-11 rounded-full transition-colors duration-200 focus:outline-none"
                                data-exam-id="{{ $exam->id }}"
                                data-url="{{ route('admin.exams.toggle-publish', $exam) }}"
                                data-published="{{ $exam->is_published ? 'true' : 'false' }}"
                                style="background-color: {{ $exam->is_published ? '#22c55e' : '#d1d5db' }}">
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform duration-200 mt-0.5
                                    {{ $exam->is_published ? 'translate-x-5' : 'translate-x-0.5' }}">
                                </span>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.exams.questions', $exam) }}"
                                   class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded hover:bg-yellow-200 transition">
                                    Questions
                                </a>
                                <a href="{{ route('admin.exams.edit', $exam) }}"
                                   class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200 transition">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}"
                                      onsubmit="return confirm('Delete this exam?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $exams->links() }}</div>
    @endif
</div>

<script>
document.querySelectorAll('.publish-toggle').forEach(btn => {
    btn.addEventListener('click', async function() {
        const url = this.dataset.url;
        const span = this.querySelector('span');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            });
            const data = await res.json();

            this.style.backgroundColor = data.published ? '#22c55e' : '#d1d5db';
            span.classList.toggle('translate-x-5', data.published);
            span.classList.toggle('translate-x-0.5', !data.published);
            this.dataset.published = data.published ? 'true' : 'false';
        } catch (e) {
            console.error(e);
        }
    });
});
</script>
@endsection
