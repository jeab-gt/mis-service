@php $app = (isset($app) && $app instanceof \App\Models\App) ? $app : null; @endphp
@extends('layouts.app')
@section('title', $app ? 'Edit App' : 'Create App')
@section('breadcrumb')
<a href="{{ route('admin.apps.index') }}" class="hover:text-indigo-600">{{ __('menu.app_builder') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $app ? __('common.edit') : __('common.create') }}</span>
@endsection
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-bold">{{ $app ? 'แก้ไข App' : 'สร้าง App' }}</h1>
        </div>
        <form method="POST" action="{{ $app ? route('admin.apps.update', $app) : route('admin.apps.store') }}" class="p-6 space-y-4">
            @csrf
            @if($app) @method('PUT') @endif

            <div>
                <label class="form-label">App Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $app->name ?? '') }}" class="form-input" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" value="{{ old('slug', $app->slug ?? '') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Category</label>
                    <input type="text" name="category" value="{{ old('category', $app->category ?? 'general') }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Icon (Tabler Icons class)</label>
                <input type="text" name="icon" value="{{ old('icon', $app->icon ?? 'ti-app') }}" class="form-input" placeholder="ti-tool">
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-input">{{ old('description', $app->description ?? '') }}</textarea>
            </div>
            <div>
                <label class="form-label">Form Schema (JSON) <span class="text-red-500">*</span></label>
                <textarea name="form_schema" rows="8" class="form-input font-mono text-xs" required>{{ old('form_schema', $app ? json_encode($app->form_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{"fields":[]}') }}</textarea>
            </div>
            <div>
                <label class="form-label">Flow Schema (JSON) <span class="text-red-500">*</span></label>
                <textarea name="flow_schema" rows="8" class="form-input font-mono text-xs" required>{{ old('flow_schema', $app ? json_encode($app->flow_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{"steps":[]}') }}</textarea>
            </div>
            <div class="flex items-center space-x-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
                       {{ old('is_active', $app->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
                <a href="{{ route('admin.apps.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
                @if($app)
                <a href="{{ route('admin.apps.designer', $app) }}" class="btn-outline ml-auto">
                    <i class="ti ti-layout-grid-add mr-1"></i>Form Designer
                </a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
