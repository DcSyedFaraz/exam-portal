@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')
@section('breadcrumb', 'Admin › Students')

@section('content')
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold font-heading">All Students <span class="text-gray-400 font-normal text-sm">({{ $students->total() }})</span></h3>
        <a href="{{ route('admin.students.create') }}" class="btn-primary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Student
        </a>
    </div>

    @if($students->isEmpty())
        <p class="text-gray-400 text-sm text-center py-8">No students found.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Student Number</th>
                        <th class="px-4 py-3 text-left">Class</th>
                        <th class="px-4 py-3 text-left">Parent</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($student->name, 0, 1)) }}
                                </div>
                                <a href="{{ route('admin.students.show', $student) }}" class="font-medium text-gray-900 hover:text-yellow-600 transition">
                                    {{ $student->name }}
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">
                            {{ $student->studentProfile?->student_number ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($student->studentProfile?->class_level)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $student->studentProfile->class_level }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $student->studentProfile?->parent?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span id="status-badge-{{ $student->id }}"
                                  class="{{ $student->is_active ? 'badge-pass' : 'badge-fail' }}">
                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.students.show', $student) }}"
                                   class="text-xs px-3 py-1.5 rounded bg-blue-100 text-blue-700 hover:bg-blue-200 transition">View</a>
                                <a href="{{ route('admin.students.edit', $student) }}"
                                   class="text-xs px-3 py-1.5 rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200 transition">Edit</a>
                                <button
                                    id="toggle-btn-{{ $student->id }}"
                                    onclick="toggleActive({{ $student->id }}, this)"
                                    data-url="{{ route('admin.students.toggle-active', $student) }}"
                                    class="text-xs px-3 py-1.5 rounded transition {{ $student->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                    {{ $student->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                      onsubmit="return confirm('Delete this student account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs px-3 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200 transition">
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
        <div class="mt-4">{{ $students->links() }}</div>
    @endif
</div>

<script>
async function toggleActive(id, btn) {
    const url = btn.dataset.url;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res  = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
        const data = await res.json();
        const badge = document.getElementById('status-badge-' + id);
        badge.textContent = data.active ? 'Active' : 'Inactive';
        badge.className   = data.active ? 'badge-pass' : 'badge-fail';
        btn.textContent   = data.active ? 'Deactivate' : 'Activate';
        btn.className     = `text-xs px-3 py-1.5 rounded transition ${data.active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'}`;
    } catch (e) { alert('Error updating student status.'); }
}
</script>
@endsection
