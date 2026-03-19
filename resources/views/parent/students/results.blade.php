@extends('layouts.app')

@section('title', $profile->user->name . "'s Results")
@section('page-title', $profile->user->name . "'s Results")
@section('breadcrumb', 'Parent › My Children › Results')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold">
            {{ strtoupper(substr($profile->user->name, 0, 1)) }}
        </div>
        <div>
            <p class="font-semibold text-gray-900">{{ $profile->user->name }}</p>
            <p class="text-xs font-mono text-gray-400">{{ $profile->student_number }}</p>
        </div>
    </div>
    <a href="{{ route('parent.students.index') }}" class="btn-secondary text-sm">← Back</a>
</div>

<div class="card">
    @if($attempts->isEmpty())
        <p class="text-gray-400 text-sm text-center py-8">This student hasn't taken any exams yet.</p>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($attempts as $i => $attempt)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $attempt->exam->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-900">
                            {{ $attempt->score }} / {{ $attempt->exam->total_marks ?? '?' }}
                        </td>
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
    @endif
</div>
@endsection
