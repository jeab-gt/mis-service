@extends('layouts.app')
@section('title', 'New Project')
@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500">Projects</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>New Project</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-xl font-bold">Create New Project</h1>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">Project Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required placeholder="e.g. IT Infrastructure Upgrade">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Factory <span class="text-red-500">*</span></label>
                    <select name="factory_id" class="form-select" required>
                        <option value="">— Select Factory —</option>
                        @foreach($factories as $f)
                        <option value="{{ $f->id }}" {{ old('factory_id') == $f->id ? 'selected' : '' }}>{{ $f->name_th }}</option>
                        @endforeach
                    </select>
                    @error('factory_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $v => $l)
                        <option value="{{ $v }}" {{ old('priority', 'medium') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label">Objective</label>
                <textarea name="objective" rows="2" class="form-input" placeholder="Project objective and goals...">{{ old('objective') }}</textarea>
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-input" placeholder="Describe the project scope...">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-input">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Budget (THB)</label>
                    <input type="number" name="budget" value="{{ old('budget') }}" class="form-input" placeholder="0.00" min="0" step="0.01">
                </div>
                <div>
                    <label class="form-label">Color</label>
                    <input type="color" name="color" value="{{ old('color', '#6366f1') }}" class="form-input h-10 p-1 cursor-pointer">
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_cross_factory" value="1" {{ old('is_cross_factory') ? 'checked' : '' }} class="rounded">
                    <span class="text-sm font-medium">Cross-Factory Project</span>
                    <span class="text-xs text-gray-400">(Members from multiple factories)</span>
                </label>
            </div>

            <div>
                <label class="form-label">Initial Members</label>
                <select name="member_ids[]" multiple class="form-select" style="height: 120px">
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ in_array($u->id, old('member_ids', [])) ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->employee_code }})
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Hold Ctrl/Cmd to select multiple</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('projects.index') }}" class="btn-secondary px-4 py-2 rounded-xl text-sm">Cancel</a>
                <button type="submit" class="btn-primary px-4 py-2 rounded-xl text-sm">
                    <i class="ti ti-check mr-1"></i> Create Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
