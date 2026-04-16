@extends('layouts.app')

@section('title', 'Parents')
@section('page-title', 'Parents')
@section('breadcrumb', 'Admin › Parents')

@section('content')
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold font-heading">All Parents</h3>
        <a href="{{ route('admin.parents.create') }}" class="btn-primary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Parent
        </a>
    </div>

    @if($parents->isEmpty())
        <p class="text-gray-400 text-sm text-center py-8">No parents found.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-header">
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Students</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parents as $i => $parent)
                    <tr class="{{ $i % 2 === 0 ? 'table-row-even' : 'table-row-odd' }} hover:bg-yellow-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($parent->name, 0, 1)) }}
                                </div>
                                <a href="{{ route('admin.parents.show', $parent) }}" class="font-medium text-gray-900 hover:text-yellow-600 transition">
                                    {{ $parent->name }}
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $parent->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                {{ $parent->child_profiles_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $status = $parent->parent_status;
                                $isPending = $status === \App\Models\User::PARENT_STATUS_PENDING;
                                $isRejected = $status === \App\Models\User::PARENT_STATUS_REJECTED;
                                $label = $isPending ? 'Pending' : ($isRejected ? 'Rejected' : ($parent->is_active ? 'Active' : 'Inactive'));
                                $badgeClass = $isPending ? 'bg-yellow-100 text-yellow-700' : ($isRejected ? 'bg-red-100 text-red-700' : ($parent->is_active ? 'badge-pass' : 'badge-fail'));
                            @endphp
                            <span id="status-badge-{{ $parent->id }}" class="text-xs px-3 py-1 rounded-full font-semibold {{ $badgeClass }}">
                                {{ $label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.parents.show', $parent) }}"
                                   class="text-xs px-3 py-1.5 rounded bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                    View
                                </a>
                                <a href="{{ route('admin.parents.edit', $parent) }}"
                                   class="text-xs px-3 py-1.5 rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200 transition">
                                    Edit
                                </a>
                                @if($parent->parent_status === \App\Models\User::PARENT_STATUS_PENDING)
                                    <form method="POST" action="{{ route('admin.parents.approve', $parent) }}"
                                          onsubmit="return confirm('Approve this parent account?')">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs px-3 py-1.5 rounded bg-green-100 text-green-700 hover:bg-green-200 transition">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.parents.reject', $parent) }}"
                                          onsubmit="return confirm('Reject this parent account request?')">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs px-3 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200 transition">
                                            Reject
                                        </button>
                                    </form>
                                @else
                                <button
                                    id="toggle-btn-{{ $parent->id }}"
                                    onclick="toggleActive({{ $parent->id }}, this)"
                                    data-url="{{ route('admin.parents.toggle-active', $parent) }}"
                                    class="text-xs px-3 py-1.5 rounded transition {{ $parent->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                    {{ $parent->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                @endif
                                <form method="POST" action="{{ route('admin.parents.destroy', $parent) }}"
                                      onsubmit="return confirm('Delete this parent account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs px-3 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $parents->links() }}</div>
    @endif
</div>

<script>
async function toggleActive(id, btn) {
    const url = btn.dataset.url;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    try {
        const res  = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
        const data = await res.json();

        if (!res.ok) {
            alert(data.message || 'Error updating parent status.');
            return;
        }

        const badge = document.getElementById('status-badge-' + id);
        badge.textContent = data.active ? 'Active' : 'Inactive';
        badge.className   = data.active ? 'badge-pass' : 'badge-fail';

        btn.textContent = data.active ? 'Deactivate' : 'Activate';
        btn.className   = `text-xs px-3 py-1.5 rounded transition ${data.active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'}`;
    } catch (e) { alert('Error updating parent status.'); }
}
</script>
@endsection
