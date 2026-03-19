@extends('layouts.app')

@section('title', 'Add Student')
@section('page-title', 'Add Student')
@section('breadcrumb', 'Admin › Students › Add')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card">
        <h3 class="text-base font-semibold font-heading text-gray-900 mb-6">Create Student Account</h3>

        <form method="POST" action="{{ route('admin.students.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-input @error('name') border-red-400 @enderror"
                       placeholder="e.g. John Smith" autofocus>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Assign to Parent <span class="text-gray-400 font-normal">(optional)</span></label>
                <select name="parent_id" class="form-input @error('parent_id') border-red-400 @enderror">
                    <option value="">— No parent —</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }} ({{ $parent->email }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">PIN <span class="text-red-500">*</span></label>
                <input type="password" name="pin" maxlength="4" inputmode="numeric"
                       class="form-input @error('pin') border-red-400 @enderror"
                       placeholder="4-digit PIN">
                @error('pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Confirm PIN <span class="text-red-500">*</span></label>
                <input type="password" name="pin_confirmation" maxlength="4" inputmode="numeric"
                       class="form-input" placeholder="Repeat PIN">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Create Student</button>
                <a href="{{ route('admin.students.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
