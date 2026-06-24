@extends('layouts.app')
@section('title', 'Preview — ' . $app->name)
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6 mis-card">
        <h1 class="text-xl font-bold mb-6">{{ $app->name }} — Preview</h1>
        <div class="grid grid-cols-2 gap-4">
            @foreach($app->initialFormTemplate?->schema['fields'] ?? [] as $field)
            @php
                $labelKey = app()->getLocale() === 'th' ? 'label_th' : 'label_en';
                $label = $field[$labelKey] ?? $field['label_th'] ?? '';
                $colClass = ($field['width'] ?? 'full') === 'full' ? 'col-span-2' : '';
            @endphp
            <div class="{{ $colClass }}">
                <label class="form-label">{{ $label }} @if(!empty($field['required']))<span class="text-red-500">*</span>@endif</label>
                @if($field['type'] === 'text') <input type="text" class="form-input" disabled>
                @elseif($field['type'] === 'textarea') <textarea class="form-input" rows="2" disabled></textarea>
                @elseif($field['type'] === 'number') <input type="number" class="form-input" disabled>
                @elseif($field['type'] === 'select') <select class="form-select" disabled><option>--</option></select>
                @elseif($field['type'] === 'date') <input type="date" class="form-input" disabled>
                @elseif($field['type'] === 'file') <input type="file" class="form-input" disabled>
                @elseif($field['type'] === 'radio')
                    <div class="flex flex-wrap gap-4 mt-1">
                        @foreach($field['options'] ?? [] as $opt)
                        <label class="flex items-center space-x-2">
                            <input type="radio" class="text-indigo-600" disabled>
                            <span class="text-sm">{{ app()->getLocale() === 'th' ? ($opt['label_th'] ?? '') : ($opt['label_en'] ?? '') }}</span>
                        </label>
                        @endforeach
                        @if(empty($field['options'])) <span class="text-xs text-gray-400 italic">No options defined</span> @endif
                    </div>
                @elseif($field['type'] === 'checkbox')
                    <div class="flex flex-wrap gap-4 mt-1">
                        @foreach($field['options'] ?? [] as $opt)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" class="rounded text-indigo-600" disabled>
                            <span class="text-sm">{{ app()->getLocale() === 'th' ? ($opt['label_th'] ?? '') : ($opt['label_en'] ?? '') }}</span>
                        </label>
                        @endforeach
                        @if(empty($field['options'])) <span class="text-xs text-gray-400 italic">No options defined</span> @endif
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
