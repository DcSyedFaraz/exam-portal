@extends('layouts.app')

@section('title', $user->name)
@section('page-title', 'Parent Details')
@section('breadcrumb', 'Admin › Parents › ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Parent Info Card --}}
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-xl shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold font-heading text-gray-900">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @php
                            $status = $user->parent_status;
                            $isPending = $status === \App\Models\User::PARENT_STATUS_PENDING;
                            $isRejected = $status === \App\Models\User::PARENT_STATUS_REJECTED;
                            $label = $isPending ? 'Pending' : ($isRejected ? 'Rejected' : ($user->is_active ? 'Active' : 'Inactive'));
                            $badgeClass = $isPending ? 'bg-yellow-100 text-yellow-700' : ($isRejected ? 'bg-red-100 text-red-700' : ($user->is_active ? 'badge-pass' : 'badge-fail'));
                        @endphp
                        <span class="text-xs px-3 py-1 rounded-full font-semibold {{ $badgeClass }}">
                            {{ $label }}
                        </span>
                        <span class="text-xs text-gray-400">Joined {{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.parents.edit', $user) }}" class="btn-primary text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                @if($user->parent_status === \App\Models\User::PARENT_STATUS_PENDING)
                    <form method="POST" action="{{ route('admin.parents.approve', $user) }}"
                          onsubmit="return confirm('Approve this parent account?')">
                        @csrf
                        <button type="submit" class="btn-primary text-sm">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.parents.reject', $user) }}"
                          onsubmit="return confirm('Reject this parent account request?')">
                        @csrf
                        <button type="submit" class="btn-secondary text-sm">Reject</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.parents.destroy', $user) }}"
                      onsubmit="return confirm('Delete this parent account?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary text-sm">Delete</button>
                </form>
                <a href="{{ route('admin.parents.index') }}" class="btn-secondary text-sm">← Back</a>
            </div>
        </div>
    </div>

    {{-- Connected Students --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold font-heading text-gray-900">
                Connected Students <span class="text-gray-400 font-normal">({{ $children->count() }})</span>
            </h3>
        </div>

        @if($children->isEmpty())
            <p class="text-gray-400 text-sm text-center py-6">No students connected to this parent yet.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="table-header">
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-left">Student Number</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($children as $i => $profile)
                        <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($profile->user->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $profile->user->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $profile->student_number }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="{{ $profile->user->is_active ? 'badge-pass' : 'badge-fail' }}">
                                    {{ $profile->user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('admin.parents.remove-student', [$user, $profile]) }}"
                                      onsubmit="return confirm('Remove this student from {{ $user->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs px-3 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200 transition">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Assign Existing Student --}}
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-4">Assign Student</h3>

        @if($unassignedStudents->isEmpty())
            <p class="text-gray-400 text-sm text-center py-4">No unassigned students available.</p>
        @else
            <form method="POST" action="{{ route('admin.parents.add-student', $user) }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <select name="student_id" class="form-input flex-1">
                    <option value="">Select a student...</option>
                    @foreach($unassignedStudents as $student)
                        <option value="{{ $student->id }}">
                            {{ $student->name }} ({{ $student->studentProfile?->student_number }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary text-sm whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Assign Student
                </button>
            </form>
            @error('student_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        @endif
    </div>

</div>
@endsection
