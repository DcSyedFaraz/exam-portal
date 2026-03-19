@extends('layouts.app')

@section('title', 'Results')
@section('page-title', 'Exam Results')
@section('breadcrumb', 'Admin › Results')

@section('content')
{{-- Filters --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.results.index') }}" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-40">
            <label class="form-label">Filter by Exam</label>
            <select name="exam_id" class="form-input">
                <option value="">All Exams</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                        {{ $exam->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
        </div>
        <div>
            <label class="form-label">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-input">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filter</button>
            <a href="{{ route('admin.results.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    @if($attempts->isEmpty())
        <p class="text-gray-400 text-sm text-center py-8">No results found.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Exam</th>
                        <th class="px-4 py-3 text-center">Score</th>
                        <th class="px-4 py-3 text-center">Passing</th>
                        <th class="px-4 py-3 text-center">Result</th>
                        <th class="px-4 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attempts as $i => $attempt)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $attempt->student->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $attempt->exam->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-semibold">{{ $attempt->score }} / {{ $attempt->exam->total_marks ?? '?' }}</td>
                        <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ $attempt->exam->passing_marks ?? '?' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($attempt->is_passed)
                                <span class="badge-pass">Pass</span>
                            @else
                                <span class="badge-fail">Fail</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at?->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $attempts->links() }}</div>
    @endif
</div>
@endsection
