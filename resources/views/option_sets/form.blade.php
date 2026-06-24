@php $optionSet = isset($optionSet) && $optionSet instanceof \App\Models\OptionSet ? $optionSet : null; @endphp
@extends('layouts.app')
@section('title', $optionSet ? 'Edit Option Set' : 'Create Option Set')
@section('breadcrumb')
<a href="{{ route('admin.option-sets.index') }}" class="hover:text-indigo-600">Option Sets</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $optionSet ? 'Edit' : 'Create' }}</span>
@endsection
@section('content')
<div class="max-w-2xl mx-auto" x-data="optionSetForm({!! htmlspecialchars(json_encode([
    'source_type' => old('source_type', $optionSet->source_type ?? 'static'),
    'items' => $optionSet && $optionSet->source_type === 'static'
        ? $optionSet->items->map(fn($i) => ['value'=>$i->value,'label_th'=>$i->label_th,'label_en'=>$i->label_en])->toArray()
        : [],
]), ENT_QUOTES, 'UTF-8') !!})">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden mis-card">
        <div class="p-6 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-bold">{{ $optionSet ? 'แก้ไข Option Set' : 'สร้าง Option Set' }}</h1>
        </div>
        <form method="POST"
              action="{{ $optionSet ? route('admin.option-sets.update', $optionSet) : route('admin.option-sets.store') }}"
              class="p-6 space-y-5"
              @submit.prevent="submitForm($el)">
            @csrf
            @if($optionSet) @method('PUT') @endif

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div class="{{ $optionSet ? 'col-span-2' : '' }}">
                    <label class="form-label">Code <span class="text-red-500">*</span></label>
                    @if($optionSet)
                    <input type="text" class="form-input bg-gray-50" value="{{ $optionSet->code }}" readonly>
                    <input type="hidden" name="code" value="{{ $optionSet->code }}">
                    @else
                    <input type="text" name="code" value="{{ old('code') }}" class="form-input font-mono"
                           placeholder="device_types" required pattern="[a-zA-Z0-9_-]+">
                    <p class="text-xs text-gray-400 mt-1">Lowercase, letters, numbers, dash, underscore only</p>
                    @endif
                </div>
                <div>
                    <label class="form-label">Name (TH) <span class="text-red-500">*</span></label>
                    <input type="text" name="name_th" value="{{ old('name_th', $optionSet->name_th ?? '') }}"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Name (EN) <span class="text-red-500">*</span></label>
                    <input type="text" name="name_en" value="{{ old('name_en', $optionSet->name_en ?? '') }}"
                           class="form-input" required>
                </div>
            </div>

            <div>
                <label class="form-label">Source Type <span class="text-red-500">*</span></label>
                <select name="source_type" x-model="sourceType" class="form-select">
                    <option value="static">Static (manual items)</option>
                    <option value="master">Master Data</option>
                    <option value="users">Users</option>
                    <option value="roles">Roles</option>
                </select>
            </div>

            <div x-show="sourceType === 'master'">
                <label class="form-label">Master Type</label>
                <input type="text" name="master_type" value="{{ old('master_type', $optionSet->master_type ?? '') }}"
                       class="form-input font-mono" placeholder="factory / department / section">
                <p class="text-xs text-gray-400 mt-1">Type value from masters.type column</p>
            </div>

            <div x-show="['master','users'].includes(sourceType)" class="flex items-center space-x-3">
                <input type="checkbox" name="filter_by_factory" id="filter_by_factory" value="1" class="rounded"
                       {{ old('filter_by_factory', $optionSet->filter_by_factory ?? false) ? 'checked' : '' }}>
                <label for="filter_by_factory" class="text-sm">Filter by factory (show only records for submitter's factory)</label>
            </div>

            <div>
                <label class="form-label">Description</label>
                <input type="text" name="description" value="{{ old('description', $optionSet->description ?? '') }}"
                       class="form-input">
            </div>

            <!-- Static items editor -->
            <div x-show="sourceType === 'static'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="form-label mb-0">Items</label>
                    <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <i class="ti ti-plus mr-1"></i>Add Item
                    </button>
                </div>
                <div class="border border-gray-300 dark:border-gray-600 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-750">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Value</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Label (TH)</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Label (EN)</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr>
                                    <td class="px-2 py-1.5">
                                        <input type="text" :name="`items[${idx}][value]`" x-model="item.value"
                                               class="form-input text-xs font-mono py-1" placeholder="value">
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input type="text" :name="`items[${idx}][label_th]`" x-model="item.label_th"
                                               class="form-input text-xs py-1" placeholder="Thai label">
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <input type="text" :name="`items[${idx}][label_en]`" x-model="item.label_en"
                                               class="form-input text-xs py-1" placeholder="English label">
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <button type="button" @click="removeItem(idx)"
                                                class="text-red-400 hover:text-red-600 p-1">
                                            <i class="ti ti-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">
                                    ยังไม่มี item — คลิก "Add Item" เพื่อเพิ่ม
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
                <a href="{{ route('admin.option-sets.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function optionSetForm(initialData) {
    return {
        sourceType: initialData.source_type || 'static',
        items: initialData.items || [],

        addItem() {
            this.items.push({ value: '', label_th: '', label_en: '' });
        },

        removeItem(idx) {
            this.items.splice(idx, 1);
        },

        submitForm(form) {
            form.submit();
        },
    };
}
</script>
@endpush
