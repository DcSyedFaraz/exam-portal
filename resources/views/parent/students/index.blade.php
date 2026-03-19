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

            <a href="{{ route('parent.students.results', $child) }}"
               class="btn-secondary w-full justify-center text-sm">
                View All Results
            </a>
        </div>
        @endforeach
    </div>
@endif
@endsection
