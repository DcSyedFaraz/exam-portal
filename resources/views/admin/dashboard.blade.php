@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb', config('app_settings.name') . ' › Admin')

@section('content')
{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Total Exams</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $totalExams }}">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-2xl">📝</div>
        </div>
        <a href="{{ route('admin.exams.index') }}" class="text-xs text-yellow-600 hover:underline mt-3 inline-block">View all →</a>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Total Students</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $totalStudents }}">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-2xl">👩‍🎓</div>
        </div>
        <a href="{{ route('admin.students.index') }}" class="text-xs text-yellow-600 hover:underline mt-3 inline-block">View all →</a>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Total Parents</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $totalParents }}">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-2xl">👪</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="form-label">Exams Taken Today</p>
                <p class="text-3xl font-bold font-heading text-gray-900" data-count-to="{{ $todayAttempts }}">0</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-2xl">✅</div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="flex gap-3 mb-6">
    <a href="{{ route('admin.exams.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Exam
    </a>
</div>

{{-- Recent Attempts --}}
<div class="card">
    <h3 class="text-base font-semibold font-heading text-gray-900 mb-4">Recent Exam Attempts</h3>

    @if($recentAttempts->isEmpty())
        <p class="text-gray-400 text-sm text-center py-8">No exam attempts yet.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Exam</th>
                        <th class="px-4 py-3 text-center">Score</th>
                        <th class="px-4 py-3 text-center">Result</th>
                        <th class="px-4 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAttempts as $i => $attempt)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $attempt->student->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $attempt->exam->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            {{ $attempt->score }} / {{ $attempt->exam->total_marks ?? '?' }}
                        </td>
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
