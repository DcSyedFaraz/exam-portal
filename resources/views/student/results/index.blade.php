@extends('layouts.app')

@section('title', 'My Results')
@section('page-title', 'My Results')
@section('breadcrumb', 'Student › Results')

@section('content')
<div class="card">
    <h3 class="text-base font-semibold font-heading text-gray-900 mb-4">My Exam History</h3>

    @if($attempts->isEmpty())
        <div class="text-center py-12">
            <span class="text-5xl">📊</span>
            <p class="text-gray-500 mt-3">No results yet. Take your first exam!</p>
            <a href="{{ route('student.exams.index') }}" class="btn-primary mt-4 inline-flex">View Exams</a>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left">Exam</th>
                        <th class="px-4 py-3 text-center">Score</th>
                        <th class="px-4 py-3 text-center">Passing</th>
                        <th class="px-4 py-3 text-center">Result</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attempts as $i => $attempt)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $attempt->exam->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-900">
                            {{ $attempt->score }} / {{ $attempt->exam->total_marks ?? '?' }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 text-xs">
                            {{ $attempt->exam->passing_marks ?? '?' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($attempt->is_passed)
                                <span class="badge-pass">Pass</span>
                            @else
                                <span class="badge-fail">Fail</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('student.exams.result', $attempt->exam_id) }}"
                               class="text-xs text-yellow-600 hover:underline">Details</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
