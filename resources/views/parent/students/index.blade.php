@extends('layouts.app')

@section('title', 'My Children')
@section('page-title', 'My Children')
@section('breadcrumb', 'Parent › My Children')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $children->count() }} child(ren)</p>
    <a href="{{ route('parent.students.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Student
    </a>
</div>

@if($children->isEmpty())
    <div class="card text-center py-12">
        <span class="text-5xl">👶</span>
        <p class="text-gray-500 mt-3">No children yet. Add your first student!</p>
        <a href="{{ route('parent.students.create') }}" class="btn-primary mt-4 inline-flex">Add Student</a>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($children as $child)
        <div class="card hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-lg">
                    {{ strtoupper(substr($child->user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $child->user->name }}</p>
                    <p class="text-xs font-mono text-gray-400">{{ $child->student_number }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm mb-4">
                <span class="text-gray-500">Exams taken</span>
                <span class="font-bold text-gray-900">{{ $examStats[$child->user_id]['total'] }}</span>
            </div>

            @if($examStats[$child->user_id]['latest'])
                @php $l = $examStats[$child->user_id]['latest']; @endphp
                <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm">
                    <p class="text-xs text-gray-400 mb-1">Latest exam</p>
                    <p class="font-medium text-gray-800 truncate">{{ $l->exam->title ?? '—' }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-gray-600">{{ $l->score }}/{{ $l->exam->total_marks ?? '?' }}</span>
                        @if($l->is_passed)
                            <span class="badge-pass">Pass</span>
                        @else
                            <span class="badge-fail">Fail</span>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex gap-2 mt-1">
                <a href="{{ route('parent.students.results', $child) }}"
                   class="btn-secondary flex-1 justify-center text-sm">
                    View Results
                </a>
                <button onclick="openPinModal({{ $child->id }}, '{{ $child->student_number }}')"
                        class="btn-secondary flex-1 justify-center text-sm">
                    Reset PIN
                </button>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- PIN reset route map (profile id → url) --}}
@php
$pinRoutes = $children->mapWithKeys(fn($c) => [$c->id => route('parent.students.reset-pin', $c)])->toJson();
@endphp

<script>
const pinRoutes = @json(json_decode($pinRoutes));

function openPinModal(profileId, studentNumber) {
    document.getElementById('pin-modal-number').textContent = studentNumber;
    document.getElementById('pin-form').action = pinRoutes[profileId];
    document.getElementById('pin').value = '';
    document.getElementById('pin_confirmation').value = '';
    document.getElementById('pin-error').classList.add('hidden');
    document.getElementById('pin-modal').classList.remove('hidden');
}
function closePinModal() {
    document.getElementById('pin-modal').classList.add('hidden');
}
function toggleVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endsection

@push('modals')
<div id="pin-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-yellow-400 px-6 pt-8 pb-6 text-center relative">
            <button onclick="closePinModal()" class="absolute top-4 right-4 text-gray-700/60 hover:text-gray-900 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="w-14 h-14 rounded-full bg-white/30 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold font-heading text-gray-900">Reset PIN</h3>
            <p class="text-sm text-gray-700 mt-1">Student: <strong id="pin-modal-number"></strong></p>
        </div>

        {{-- Form --}}
        <form id="pin-form" method="POST" class="px-6 py-6 space-y-4">
            @csrf

            <div>
                <label class="form-label">New PIN <span class="text-red-500">*</span></label>
                <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-yellow-400 focus-within:border-transparent transition overflow-hidden">
                    <input id="pin" type="password" name="pin" maxlength="4" inputmode="numeric"
                           class="flex-1 px-3 py-2 text-sm tracking-[0.5em] text-center font-bold outline-none bg-transparent"
                           placeholder="••••" autocomplete="off">
                    <button type="button" onclick="toggleVisibility('pin')"
                            class="px-3 text-gray-400 hover:text-gray-600 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="form-label">Confirm PIN <span class="text-red-500">*</span></label>
                <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-yellow-400 focus-within:border-transparent transition overflow-hidden">
                    <input id="pin_confirmation" type="password" name="pin_confirmation" maxlength="4" inputmode="numeric"
                           class="flex-1 px-3 py-2 text-sm tracking-[0.5em] text-center font-bold outline-none bg-transparent"
                           placeholder="••••" autocomplete="off">
                    <button type="button" onclick="toggleVisibility('pin_confirmation')"
                            class="px-3 text-gray-400 hover:text-gray-600 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <p id="pin-error" class="text-red-500 text-xs hidden"></p>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary flex-1 justify-center">Save PIN</button>
                <button type="button" onclick="closePinModal()" class="btn-secondary flex-1 justify-center">Cancel</button>
            </div>
        </form>

    </div>
</div>
@endpush
