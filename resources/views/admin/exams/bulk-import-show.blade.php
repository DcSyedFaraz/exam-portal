@extends('layouts.app')

@section('title', 'Import result')
@section('page-title', 'Import result')
@section('breadcrumb', 'Admin › Exams › Import from Excel › Result')

@section('content')
<div class="max-w-3xl space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="card space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">{{ $batch->original_filename }}</h2>
        <p class="text-sm text-gray-500">Status: <span class="font-medium text-gray-800 capitalize">{{ $batch->status }}</span></p>
        @if($batch->summary_json)
            @php($summary = $batch->summary_json)
            @if(!empty($summary['fatal']))
                <p class="text-sm text-red-700">{{ $summary['fatal'] }}</p>
            @endif
            @if(($summary['exams_created'] ?? 0) > 0)
                <p class="text-sm text-gray-700">Your exam was saved. Open it from the exams list to review or add more questions.</p>
            @endif
            @if(isset($summary['errors_count']) && $summary['errors_count'] > 0)
                <p class="text-sm text-gray-700">Issues to review: {{ $summary['errors_count'] }}</p>
            @endif
            @if(!empty($summary['created_exam_ids']))
                <p class="text-xs text-gray-500 font-mono">Exam IDs: {{ implode(', ', $summary['created_exam_ids']) }}</p>
            @endif
        @endif

        @if($batch->error_report_path)
            <a href="{{ route('admin.exams.bulk-import.errors', $batch) }}" class="btn-secondary text-sm inline-flex">
                Download error report (.xlsx)
            </a>
        @endif
    </div>

    <div class="flex gap-3">
        <a href="{{ route('admin.exams.bulk-import') }}" class="btn-secondary">← New import</a>
        <a href="{{ route('admin.exams.index') }}" class="btn-secondary">All exams</a>
    </div>
</div>
@endsection
