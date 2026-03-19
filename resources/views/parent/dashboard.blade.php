@extends('layouts.app')

@section('title', 'Parent Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', config('app_settings.name') . ' › Parent')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold font-heading text-gray-900">Welcome, {{ auth()->user()->name }}!</h2>
    <p class="text-gray-500 mt-1">Monitor your children's progress and exam results.</p>
</div>

{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Children</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $children->count() }}">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-2xl">👨‍👧</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Total Exams Taken</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $totalAttempts }}">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-2xl">📝</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Average Score</p>
                <p class="text-3xl font-bold font-heading text-gray-900">{{ number_format($avgScore, 1) }}</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-2xl">⭐</div>
        </div>
    </div>
</div>

{{-- Children Cards --}}
<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold font-heading text-gray-900">My Children</h3>
    <a href="{{ route('parent.students.create') }}" class="btn-primary text-sm py-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Student
    </a>
</div>

@if($children->isEmpty())
    <div class="card text-center py-12">
        <span class="text-5xl">👶</span>
        <p class="text-gray-500 mt-3">No children yet.</p>
        <a href="{{ route('parent.students.create') }}" class="btn-primary mt-4 inline-flex">Add First Student</a>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($children as $child)
        <div class="card hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold">
                    {{ strtoupper(substr($child->user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $child->user->name }}</p>
                    <p class="text-xs font-mono text-gray-400">{{ $child->student_number }}</p>
                </div>
            </div>

            @if(isset($latestAttempts[$child->user_id]) && $latestAttempts[$child->user_id])
                @php $latest = $latestAttempts[$child->user_id]; @endphp
                <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm">
                    <p class="text-gray-500 text-xs mb-1">Latest: {{ $latest->exam->title ?? '—' }}</p>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-900">{{ $latest->score }}/{{ $latest->exam->total_marks ?? '?' }}</span>
                        @if($latest->is_passed)
                            <span class="badge-pass">Pass</span>
                        @else
                            <span class="badge-fail">Fail</span>
                        @endif
                    </div>
                </div>
            @else
                <p class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3 mb-4">No exams taken yet.</p>
            @endif

            <a href="{{ route('parent.students.results', $child) }}" class="btn-secondary w-full justify-center text-sm">
                View Results
            </a>
        </div>
        @endforeach
    </div>
@endif
@endsection
