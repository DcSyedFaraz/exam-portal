@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')
@section('breadcrumb', 'Admin › Students')

@section('content')
<div class="card">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
        {{-- Title + count --}}
        <div class="flex-1">
            <h3 class="text-base font-semibold font-heading">
                All Students
                <span class="text-gray-400 font-normal text-sm" id="student-count">({{ $students->total() }})</span>
            </h3>
        </div>

        {{-- Search bar --}}
        <div class="relative flex-1 sm:max-w-xs">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
            </span>
            <input type="text"
                   id="student-search"
                   value="{{ $search }}"
                   placeholder="Search by name or number…"
                   autocomplete="off"
                   class="form-input pl-9 pr-8 text-sm w-full">
            <button id="search-clear"
                    onclick="clearSearch()"
                    class="{{ $search ? '' : 'hidden' }} absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition"
                    title="Clear search">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Add button --}}
        <a href="{{ route('admin.students.create') }}" class="btn-primary text-sm shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Student
        </a>
    </div>

    {{-- Spinner shown during search --}}
    <div id="search-spinner" class="hidden text-center py-3">
        <svg class="animate-spin w-5 h-5 text-yellow-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Results region (replaced by AJAX) --}}
    <div id="students-results">
        @include('admin.students._table', compact('students'))
    </div>
</div>

<script>
// ── Throttled live search ──────────────────────────────────────────────────
let searchTimer = null;

document.getElementById('student-search').addEventListener('input', function () {
    const val = this.value;

    // Show / hide clear button
    document.getElementById('search-clear').classList.toggle('hidden', val.trim() === '');

    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchStudents(val.trim()), 350); // 350 ms throttle
});

async function fetchStudents(search) {
    const spinner = document.getElementById('search-spinner');
    const results = document.getElementById('students-results');

    spinner.classList.remove('hidden');
    results.style.opacity = '0.4';

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('search', search);
        url.searchParams.delete('page'); // reset to page 1 on new search

        const res  = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        });
        const html = await res.text();

        // Parse the response and extract only the #students-results inner HTML
        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, 'text/html');
        const fresh  = doc.getElementById('students-results');
        const count  = doc.getElementById('student-count');

        if (fresh) results.innerHTML = fresh.innerHTML;
        if (count)  document.getElementById('student-count').textContent = count.textContent;

        // Update browser URL without reload
        window.history.replaceState({}, '', url.toString());
    } catch (e) {
        console.error('Search failed', e);
    } finally {
        spinner.classList.add('hidden');
        results.style.opacity = '1';
    }
}

function clearSearch() {
    document.getElementById('student-search').value = '';
    document.getElementById('search-clear').classList.add('hidden');
    fetchStudents('');
}

// ── Toggle active ─────────────────────────────────────────────────────────
// Delegated listener so it works after AJAX re-renders the table
document.getElementById('students-results').addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-toggle-active]');
    if (!btn) return;

    const id  = btn.dataset.toggleActive;
    const url = btn.dataset.url;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    try {
        const res  = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
        const data = await res.json();
        const badge = document.getElementById('status-badge-' + id);
        if (badge) {
            badge.textContent = data.active ? 'Active' : 'Inactive';
            badge.className   = data.active ? 'badge-pass' : 'badge-fail';
        }
        btn.textContent = data.active ? 'Deactivate' : 'Activate';
        btn.className   = `text-xs px-3 py-1.5 rounded transition ${data.active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'}`;
    } catch (e) { alert('Error updating student status.'); }
});
</script>
@endsection
