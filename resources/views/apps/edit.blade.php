@extends('layouts.app')
@section('title', isset($app) ? __('appbuilder.edit_title', ['name' => $app->name]) : __('appbuilder.create_title'))
@section('breadcrumb')
<a href="{{ route('admin.apps.index') }}" class="hover:text-indigo-600">{{ __('menu.app_builder') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ isset($app) ? __('common.edit') : __('common.create') }}</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6">
        <h1 class="text-xl font-bold mb-6">{{ isset($app) ? __('appbuilder.edit_title', ['name' => $app->name]) : __('appbuilder.create_title') }}</h1>

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ isset($app) ? route('admin.apps.update', $app) : route('admin.apps.store') }}"
              class="space-y-5">
            @csrf
            @if(isset($app)) @method('PUT') @endif

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label">{{ __('appbuilder.app_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $app?->name ?? '') }}"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" value="{{ old('slug', $app?->slug ?? '') }}"
                           class="form-input font-mono" required placeholder="my-app">
                    <p class="text-xs text-gray-400 mt-1">{{ __('appbuilder.slug_help') }}</p>
                </div>
                <div>
                    <label class="form-label">Category (legacy) <span class="text-red-500">*</span></label>
                    <input type="text" name="category" value="{{ old('category', $app?->category ?? '') }}"
                           class="form-input" required placeholder="maintenance">
                </div>
                <div>
                    <label class="form-label">{{ __('appbuilder.portal_category') }}</label>
                    <select name="category_id" class="form-select">
                        <option value="">{{ __('appbuilder.no_category') }}</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('category_id', $app?->category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name_th }}{{ $cat->name_en ? ' — ' . $cat->name_en : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Icon (Tabler)</label>
                    <input type="text" name="icon" value="{{ old('icon', $app?->icon ?? 'ti-apps') }}"
                           class="form-input font-mono" placeholder="ti-tool">
                </div>
                <div class="flex items-center space-x-2 mt-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', ($app?->is_active ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}
                           class="rounded text-indigo-600">
                    <label for="is_active" class="text-sm">Active</label>
                </div>
            </div>

            <div>
                <label class="form-label">{{ __('common.description') }}</label>
                <textarea name="description" rows="2" class="form-input">{{ old('description', $app?->description ?? '') }}</textarea>
            </div>

            <!-- Form Templates -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-5">
                <h3 class="font-semibold text-sm mb-3 flex items-center space-x-2">
                    <i class="ti ti-forms text-indigo-500"></i><span>Form Templates</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Initial Form Template</label>
                        <div class="flex space-x-2">
                            <select name="initial_form_template_id" class="form-select flex-1">
                                <option value="">{{ __('appbuilder.no_form') }}</option>
                                @foreach($formTemplates as $t)
                                <option value="{{ $t->id }}"
                                    {{ old('initial_form_template_id', $app?->initial_form_template_id ?? '') == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ $t->category }})
                                </option>
                                @endforeach
                            </select>
                            <a href="{{ route('admin.form-templates.index') }}" target="_blank"
                               class="btn-secondary text-sm px-2" title="{{ __('appbuilder.create_form_tip') }}">
                                <i class="ti ti-external-link"></i>
                            </a>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ __('appbuilder.initial_form_help') }}</p>
                    </div>
                    <div>
                        <label class="form-label">Revision Form Template</label>
                        <div class="flex space-x-2">
                            <select name="revision_form_template_id" class="form-select flex-1">
                                <option value="">{{ __('appbuilder.no_form') }}</option>
                                @foreach($formTemplates as $t)
                                <option value="{{ $t->id }}"
                                    {{ old('revision_form_template_id', $app?->revision_form_template_id ?? '') == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ $t->category }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ __('appbuilder.revision_form_help') }}</p>
                    </div>
                </div>
            </div>

            <!-- Flow -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-5">
                <h3 class="font-semibold text-sm mb-3 flex items-center space-x-2">
                    <i class="ti ti-git-branch text-blue-500"></i><span>Approval Flow</span>
                </h3>
                <div>
                    <label class="form-label">Flow</label>
                    <div class="flex space-x-2">
                        <select name="flow_id" class="form-select flex-1">
                            <option value="">{{ __('appbuilder.no_form') }}</option>
                            @foreach($flows as $f)
                            <option value="{{ $f->id }}"
                                {{ old('flow_id', $app?->flow_id ?? '') == $f->id ? 'selected' : '' }}>
                                {{ $f->name }}
                            </option>
                            @endforeach
                        </select>
                        <a href="{{ route('admin.flows.index') }}" target="_blank"
                           class="btn-secondary text-sm px-2" title="{{ __('appbuilder.create_flow_tip') }}">
                            <i class="ti ti-external-link"></i>
                        </a>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ __('appbuilder.flow_help') }}</p>
                </div>
            </div>

            <!-- Portal Settings -->
            <div class="border-t border-gray-200 dark:border-gray-600 pt-5">
                <h3 class="font-semibold text-sm mb-3 flex items-center space-x-2">
                    <i class="ti ti-layout-grid text-purple-500"></i><span>Portal Settings</span>
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">{{ __('appbuilder.linked_dashboard') }}</label>
                        <select name="primary_dashboard_id" class="form-select">
                            <option value="">{{ __('appbuilder.no_dashboard') }}</option>
                            @foreach($dashboards as $db)
                            <option value="{{ $db->id }}"
                                {{ old('dashboard_id', $app?->primary_dashboard_id) == $db->id ? 'selected' : '' }}>
                                {{ $db->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('appbuilder.allowed_roles') }} <span class="font-normal text-gray-400 text-xs">({{ __('appbuilder.allowed_roles_help') }})</span></label>
                        <div class="flex flex-wrap gap-3 mt-1">
                            @foreach($roles as $role)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="allowed_roles[]" value="{{ $role->name }}"
                                       {{ in_array($role->name, old('allowed_roles', $app?->allowed_roles ?? [])) ? 'checked' : '' }}
                                       class="rounded text-indigo-600">
                                <span class="text-sm">{{ $role->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="form-label">{{ __('appbuilder.allowed_factories') }} <span class="font-normal text-gray-400 text-xs">({{ __('appbuilder.allowed_factories_help') }})</span></label>
                        <div class="flex flex-wrap gap-3 mt-1">
                            @foreach($factories as $factory)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="allowed_factories[]" value="{{ $factory->id }}"
                                       {{ in_array($factory->id, old('allowed_factories', $app?->allowed_factories ?? [])) ? 'checked' : '' }}
                                       class="rounded text-indigo-600">
                                <span class="text-sm">{{ $factory->name_th }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="submit" class="btn-primary">
                    <i class="ti ti-device-floppy mr-2"></i>{{ __('common.save') }}
                </button>
                <a href="{{ route('admin.apps.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
