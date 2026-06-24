@extends('layouts.app')
@section('title', $app->name)

@section('breadcrumb')
<a href="{{ route('submissions.index') }}" class="hover:text-indigo-600">{{ __('menu.all_requests') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $app->name }}</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden mis-card">
        <div class="p-6 border-b border-gray-200 dark:border-gray-600 flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                <i class="ti {{ $app->icon }} text-2xl text-indigo-500"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $app->name }}</h1>
                <p class="text-sm text-gray-500">{{ $app->description }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('submissions.store', $app) }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($app->initialFormTemplate?->schema['fields'] ?? [] as $field)
                @php
                    $labelKey   = app()->getLocale() === 'th' ? 'label_th' : 'label_en';
                    $label      = $field[$labelKey] ?? $field['label_th'] ?? '';
                    $fieldName  = "form_{$field['id']}";
                    $colClass   = ($field['width'] ?? 'full') === 'full' ? 'md:col-span-2' : '';
                    // Resolve options from OptionSet or manual list
                    if (in_array($field['type'], ['select','radio','checkbox'])) {
                        if (($field['data_source'] ?? 'manual') === 'option_set' && !empty($field['option_set_code'])) {
                            $optSet       = \App\Models\OptionSet::where('code', $field['option_set_code'])->first();
                            $fieldOptions = $optSet ? $optSet->getOptions(auth()->user()?->factory_id) : [];
                        } else {
                            $fieldOptions = $field['options'] ?? [];
                        }
                    }
                @endphp
                <div class="{{ $colClass }}">
                    <label class="form-label">
                        {{ $label }}
                        @if(!empty($field['required'])) <span class="text-red-500">*</span> @endif
                    </label>

                    @if($field['type'] === 'text')
                        <input type="text" name="{{ $fieldName }}" value="{{ old($fieldName) }}"
                               class="form-input @error($fieldName) border-red-500 @enderror"
                               {{ !empty($field['required']) ? 'required' : '' }}>
                    @elseif($field['type'] === 'textarea')
                        <textarea name="{{ $fieldName }}" rows="3"
                                  class="form-input @error($fieldName) border-red-500 @enderror"
                                  {{ !empty($field['required']) ? 'required' : '' }}>{{ old($fieldName) }}</textarea>
                    @elseif($field['type'] === 'select')
                        <select name="{{ $fieldName }}" class="form-select @error($fieldName) border-red-500 @enderror"
                                {{ !empty($field['required']) ? 'required' : '' }}>
                            <option value="">-- {{ app()->getLocale() === 'th' ? 'เลือก' : 'Select' }} --</option>
                            @foreach($fieldOptions ?? [] as $opt)
                            <option value="{{ $opt['value'] }}" {{ old($fieldName) === $opt['value'] ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'th' ? ($opt['label_th'] ?? '') : ($opt['label_en'] ?? '') }}
                            </option>
                            @endforeach
                        </select>
                    @elseif($field['type'] === 'number')
                        <input type="number" name="{{ $fieldName }}" value="{{ old($fieldName) }}"
                               class="form-input @error($fieldName) border-red-500 @enderror"
                               {{ !empty($field['required']) ? 'required' : '' }}>
                    @elseif($field['type'] === 'date')
                        <input type="date" name="{{ $fieldName }}" value="{{ old($fieldName) }}"
                               class="form-input @error($fieldName) border-red-500 @enderror"
                               {{ !empty($field['required']) ? 'required' : '' }}>
                    @elseif($field['type'] === 'file')
                        <input type="file" name="{{ $fieldName }}"
                               accept="{{ $field['accept'] ?? '*' }}"
                               class="form-input @error($fieldName) border-red-500 @enderror"
                               {{ !empty($field['required']) ? 'required' : '' }}>
                    @elseif($field['type'] === 'radio')
                        <div class="flex flex-wrap gap-4 mt-1">
                            @foreach($fieldOptions ?? [] as $opt)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="{{ $fieldName }}" value="{{ $opt['value'] }}"
                                       {{ old($fieldName) === $opt['value'] ? 'checked' : '' }}
                                       class="text-indigo-600"
                                       {{ !empty($field['required']) ? 'required' : '' }}>
                                <span class="text-sm">{{ app()->getLocale() === 'th' ? ($opt['label_th'] ?? '') : ($opt['label_en'] ?? '') }}</span>
                            </label>
                            @endforeach
                        </div>
                    @elseif($field['type'] === 'checkbox')
                        <div class="flex flex-wrap gap-4 mt-1">
                            @foreach($fieldOptions ?? [] as $opt)
                            @php $cbChecked = in_array($opt['value'], (array) old($fieldName, [])); @endphp
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $opt['value'] }}"
                                       {{ $cbChecked ? 'checked' : '' }}
                                       class="rounded text-indigo-600">
                                <span class="text-sm">{{ app()->getLocale() === 'th' ? ($opt['label_th'] ?? '') : ($opt['label_en'] ?? '') }}</span>
                            </label>
                            @endforeach
                        </div>
                    @endif
                    @error($fieldName)
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endforeach
            </div>

            <div class="flex items-center space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="submit" class="btn-primary">
                    <i class="ti ti-send mr-2"></i>{{ __('common.submit') }}
                </button>
                <a href="{{ route('submissions.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
