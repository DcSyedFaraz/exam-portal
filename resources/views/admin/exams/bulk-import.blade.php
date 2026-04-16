@extends('layouts.app')

@section('title', 'Import exam from Excel')
@section('page-title', 'Import exam from Excel')
@section('breadcrumb', 'Admin › Exams › Import from Excel')

@section('content')
<div class="max-w-3xl space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="card space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Download a ready-made Excel file</h2>
            <p class="text-sm text-gray-500 mt-1">The file uses everyday words (no codes). Start with the <strong>Read me</strong> tab for short steps. One file = <strong>one exam</strong> with any mix of multiple choice, true/false, and matching questions.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.exams.bulk-import.template', ['with_examples' => 0]) }}" class="btn-secondary text-sm">
                Blank template
            </a>
            <a href="{{ route('admin.exams.bulk-import.template', ['with_examples' => 1]) }}" class="btn-primary text-sm">
                Template with a filled-in example
            </a>
        </div>
    </div>

    <div class="card space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Upload your completed file</h2>
        <ul class="text-sm text-gray-600 list-disc list-inside space-y-1">
            <li>Fill <strong>Exam details</strong> once (exam name, time limit, scores).</li>
            <li>Add each question on the <strong>Questions</strong> tab; use plain types like "Multiple choice" or "True or false".</li>
            <li>For <strong>Matching</strong> questions, list pairs on the <strong>Matching pairs</strong> tab using the same question number.</li>
            <li>The marks for all questions added together must equal <strong>Total marks</strong> on the exam tab.</li>
            <li>Very large files may process in the background—run a queue worker on the server if you use that option.</li>
        </ul>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                {{ $errors->first('file') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.exams.bulk-import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Excel file (.xlsx or .xls)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                       class="form-input @error('file') border-red-400 @enderror">
                @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary">Import exam</button>
        </form>
    </div>

    <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Recent uploads</h2>
        @if($batches->isEmpty())
            <p class="text-sm text-gray-500">No imports yet.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="table-header">
                            <th class="px-4 py-2 text-left">File</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">When</th>
                            <th class="px-4 py-2 text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batches as $b)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2">{{ $b->original_filename }}</td>
                            <td class="px-4 py-2 capitalize">{{ $b->status }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $b->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('admin.exams.bulk-import.show', $b) }}" class="text-yellow-700 hover:underline text-sm">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <a href="{{ route('admin.exams.index') }}" class="btn-secondary">← Back to exams</a>
</div>
@endsection
