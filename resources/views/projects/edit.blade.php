@extends('layouts.app')
@section('title', 'Edit: ' . $project->name)
@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500">Projects</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500">{{ $project->name }}</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>Edit</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-xl font-bold">Edit Project</h1>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="form-label">Project Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $project->name) }}" class="form-input" required>
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Factory <span class="text-red-500">*</span></label>
                    <select name="factory_id" class="form-select" required>
                        @foreach($factories as $f)
                        <option value="{{ $f->id }}" {{ old('factory_id', $project->factory_id) == $f->id ? 'selected' : '' }}>{{ $f->name_th }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $v => $l)
                        <option value="{{ $v }}" {{ old('status', $project->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $v => $l)
                        <option value="{{ $v }}" {{ old('priority', $project->priority) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Color</label>
                    <input type="color" name="color" value="{{ old('color', $project->color) }}" class="form-input h-10 p-1 cursor-pointer">
                </div>
            </div>

            <div>
                <label class="form-label">Objective</label>
                <textarea name="objective" rows="2" class="form-input">{{ old('objective', $project->objective) }}</textarea>
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-input">{{ old('description', $project->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">Budget (THB)</label>
                <input type="number" name="budget" value="{{ old('budget', $project->budget) }}" class="form-input" min="0" step="0.01">
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_cross_factory" value="1" {{ old('is_cross_factory', $project->is_cross_factory) ? 'checked' : '' }} class="rounded">
                    <span class="text-sm font-medium">Cross-Factory Project</span>
                </label>
            </div>

            <div class="flex justify-between items-center pt-2">
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                      onsubmit="return confirm('Delete this project and all its data?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 flex items-center gap-1">
                        <i class="ti ti-trash"></i> Delete Project
                    </button>
                </form>
                <div class="flex gap-3">
                    <a href="{{ route('projects.show', $project) }}" class="btn-secondary px-4 py-2 rounded-xl text-sm">Cancel</a>
                    <button type="submit" class="btn-primary px-4 py-2 rounded-xl text-sm">
                        <i class="ti ti-check mr-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
