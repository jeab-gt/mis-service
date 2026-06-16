@extends('layouts.app')
@section('title', isset($master) ? __('common.edit') : __('common.create'))

@section('breadcrumb')
<a href="{{ route('admin.masters.index') }}" class="hover:text-indigo-600">{{ __('menu.masters') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ isset($master) ? __('common.edit') : __('common.create') }}</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h1 class="text-xl font-bold">{{ isset($master) ? __('common.edit') . ' Master' : __('common.create') . ' Master' }}</h1>
        </div>

        <form method="POST"
              action="{{ isset($master) ? route('admin.masters.update', $master) : route('admin.masters.store') }}"
              class="p-6 space-y-5"
              x-data="{ selectedType: '{{ old('type', $suggestedType ?? $master->type ?? '') }}' }">
            @csrf
            @if(isset($master)) @method('PUT') @endif

            {{-- Parent --}}
            <div>
                <label class="form-label">Parent</label>
                <select name="parent_id" class="form-select">
                    <option value="">-- Root ({{ app()->getLocale() === 'th' ? 'ไม่มี Parent' : 'No Parent' }}) --</option>
                    @foreach($parents as $p)
                    <option value="{{ $p->id }}"
                        {{ old('parent_id', $selectedParentId ?? $master->parent_id ?? '') == $p->id ? 'selected' : '' }}>
                        {{ $p->full_path }} [{{ \App\Models\Master::typeLabel($p->type) }}]
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    {{ app()->getLocale() === 'th'
                        ? 'ลำดับชั้น: Company → Factory → Plant/Department → Section → Team'
                        : 'Hierarchy: Company → Factory → Plant/Department → Section → Team' }}
                </p>
            </div>

            {{-- Type selector --}}
            <div>
                <p class="form-label mb-2">
                    {{ app()->getLocale() === 'th' ? 'เลือกประเภท' : 'Select Type' }}
                    <span class="text-red-500">*</span>
                </p>

                {{-- Hidden input carries the actual value --}}
                <input type="hidden" name="type" :value="selectedType">

                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:4px;">
                    @foreach([
                        'company'    => ['icon' => 'ti-building',        'label' => 'Company'],
                        'factory'    => ['icon' => 'ti-building-factory', 'label' => 'Factory'],
                        'plant'      => ['icon' => 'ti-home',             'label' => 'Plant'],
                        'department' => ['icon' => 'ti-layout-grid',      'label' => 'Department'],
                        'section'    => ['icon' => 'ti-users-group',      'label' => 'Section'],
                        'team'       => ['icon' => 'ti-users',            'label' => 'Team'],
                        'line'       => ['icon' => 'ti-share',            'label' => 'Line'],
                    ] as $value => $item)
                    <button type="button"
                        @click="selectedType = '{{ $value }}'"
                        @mouseenter="$el.dataset.hover = '1'"
                        @mouseleave="$el.dataset.hover = '0'"
                        style="display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:12px; padding:14px 8px; text-align:center; cursor:pointer; transition:all 0.2s; width:100%;"
                        :style="selectedType === '{{ $value }}'
                            ? 'background:#eff6ff; border:2px solid #2563eb; color:#1d4ed8;'
                            : ($el.dataset.hover === '1'
                                ? 'background:#f8faff; border:1.5px solid #93c5fd; color:#374151;'
                                : 'background:white; border:1.5px solid #e5e7eb; color:#374151;')">
                        <i class="ti {{ $item['icon'] }}"
                           style="font-size:24px; color:#3b82f6;"></i>
                        <span style="font-size:13px; margin-top:6px; color:#374151;">{{ $item['label'] }}</span>
                    </button>
                    @endforeach
                </div>
                <p x-show="selectedType" class="mt-2 text-sm text-blue-700 font-medium">
                    ✓ เลือกแล้ว: <span x-text="selectedType.charAt(0).toUpperCase() + selectedType.slice(1)"></span>
                </p>
            </div>

            {{-- Code & Sort Order --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code"
                           value="{{ old('code', $master->code ?? '') }}"
                           class="form-input" required placeholder="e.g. FAC-F1">
                </div>
                <div>
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order"
                           value="{{ old('sort_order', $master->sort_order ?? 0) }}"
                           class="form-input" min="0">
                </div>
            </div>

            {{-- Thai & English names --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">ชื่อภาษาไทย <span class="text-red-500">*</span></label>
                    <input type="text" name="name_th"
                           value="{{ old('name_th', $master->name_th ?? '') }}"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">English Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name_en"
                           value="{{ old('name_en', $master->name_en ?? '') }}"
                           class="form-input" required>
                </div>
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center space-x-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
                       {{ old('is_active', $master->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm">{{ app()->getLocale() === 'th' ? 'เปิดใช้งาน' : 'Active' }}</label>
            </div>

            <div class="flex items-center space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <button type="submit" class="btn-primary" :disabled="!selectedType"
                        :class="!selectedType ? 'opacity-50 cursor-not-allowed' : ''">
                    {{ __('common.save') }}
                </button>
                <a href="{{ route('admin.masters.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
