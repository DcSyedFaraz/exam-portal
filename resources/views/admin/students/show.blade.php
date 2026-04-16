@extends('layouts.app')

@section('title', $user->name)
@section('page-title', 'Student Details')
@section('breadcrumb', 'Admin › Students › ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Student Info Card --}}
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-xl shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold font-heading text-gray-900">{{ $user->name }}</h2>
                    <p class="font-mono text-sm text-gray-500">{{ $profile?->student_number ?? '—' }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="{{ $user->is_active ? 'badge-pass' : 'badge-fail' }}" id="status-badge-{{ $user->id }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="text-xs text-gray-400">
                            Parent: <strong>{{ $profile?->parent?->name ?? 'None' }}</strong>
                        </span>
                        <span class="text-xs text-gray-400">Joined {{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.students.edit', $user) }}" class="btn-primary text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <button id="toggle-btn-{{ $user->id }}"
                        onclick="toggleActive({{ $user->id }}, this)"
                        data-url="{{ route('admin.students.toggle-active', $user) }}"
                        class="text-sm px-4 py-2 rounded-lg font-medium transition {{ $user->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                </button>
                <form method="POST" action="{{ route('admin.students.destroy', $user) }}"
                      onsubmit="return confirm('Delete this student account?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary text-sm">Delete</button>
                </form>
                <a href="{{ route('admin.students.index') }}" class="btn-secondary text-sm">← Back</a>
            </div>
        </div>
    </div>

    {{-- Reset PIN --}}
    <div class="card">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 rounded-lg bg-yellow-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold font-heading text-gray-900">Reset Student PIN</h3>
                <p class="text-xs text-gray-400">Set a new 4-digit login PIN for this student.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.students.reset-pin', $user) }}" class="flex flex-col sm:flex-row gap-3 items-start">
            @csrf
            <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-yellow-400 focus-within:border-transparent transition overflow-hidden sm:w-44 @error('pin') border-red-400 @enderror">
                <input id="admin-pin" type="password" name="pin" maxlength="4" inputmode="numeric"
                       class="flex-1 px-3 py-2 text-sm tracking-[0.4em] text-center font-bold outline-none bg-transparent"
                       placeholder="••••" autocomplete="off">
                <button type="button" onclick="toggleAdminPin('admin-pin')"
                        class="px-3 text-gray-400 hover:text-gray-600 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
            <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-yellow-400 focus-within:border-transparent transition overflow-hidden sm:w-44">
                <input id="admin-pin-confirm" type="password" name="pin_confirmation" maxlength="4" inputmode="numeric"
                       class="flex-1 px-3 py-2 text-sm tracking-[0.4em] text-center font-bold outline-none bg-transparent"
                       placeholder="••••" autocomplete="off">
                <button type="button" onclick="toggleAdminPin('admin-pin-confirm')"
                        class="px-3 text-gray-400 hover:text-gray-600 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
            <button type="submit" class="btn-primary text-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save PIN
            </button>
        </form>
        @error('pin')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
    </div>

    {{-- Exam History --}}
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-4">
            Exam History <span class="text-gray-400 font-normal">({{ $attempts->count() }})</span>
        </h3>

        @if($attempts->isEmpty())
            <p class="text-gray-400 text-sm text-center py-6">No exams taken yet.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="table-header">
                            <th class="px-4 py-3 text-left">Exam</th>
                            <th class="px-4 py-3 text-center">Score</th>
                            <th class="px-4 py-3 text-center">Result</th>
                            <th class="px-4 py-3 text-left">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $i => $attempt)
                        <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $attempt->exam?->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $attempt->score }} / {{ $attempt->exam?->total_marks }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="{{ $attempt->is_passed ? 'badge-pass' : 'badge-fail' }}">
                                    {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at->format('d M Y, H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<script>
function toggleAdminPin(fieldId, btn) {
    const input = document.getElementById(fieldId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

async function toggleActive(id, btn) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res  = await fetch(btn.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
        const data = await res.json();
        const badge = document.getElementById('status-badge-' + id);
        badge.textContent = data.active ? 'Active' : 'Inactive';
        badge.className   = data.active ? 'badge-pass' : 'badge-fail';
        btn.textContent   = data.active ? 'Deactivate' : 'Activate';
        btn.className     = `text-sm px-4 py-2 rounded-lg font-medium transition ${data.active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'}`;
    } catch (e) { alert('Error updating status.'); }
}
</script>
@endsection
