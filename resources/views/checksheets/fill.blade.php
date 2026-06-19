@extends('layouts.app')
@section('title', 'กรอกข้อมูล: ' . $template->name)
@section('breadcrumb')
<a href="{{ route('checksheets.index') }}" class="hover:text-indigo-500">Checksheet</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<a href="{{ route('checksheets.records', $template) }}" class="hover:text-indigo-500">{{ $template->name }}</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>กรอกข้อมูล</span>
@endsection

@section('content')
<div x-data="checksheetFill(@js($parameters->toArray()), @js($timeSlots->toArray()))" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $template->name }}</h1>
    </div>

    <form method="POST" action="{{ route('checksheets.store', $template) }}" id="checksheet-form">
        @csrf
        <input type="hidden" name="action" id="form-action" value="draft">

        {{-- Header Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">วันที่ <span class="text-red-500">*</span></label>
                    <input type="date" name="record_date" value="{{ now()->toDateString() }}"
                           class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Factory <span class="text-red-500">*</span></label>
                    <select name="factory_id" class="form-select" required>
                        <option value="">— เลือก Factory —</option>
                        @foreach($factories as $factory)
                        <option value="{{ $factory->id }}" {{ auth()->user()->factory_id == $factory->id ? 'selected' : '' }}>
                            {{ $factory->name_th }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if($timeSlots->count() > 0)
                <div>
                    <label class="form-label">Time Slot</label>
                    <select name="time_slot_id" class="form-select">
                        <option value="">— เลือก Time Slot —</option>
                        @foreach($timeSlots as $slot)
                        <option value="{{ $slot->id }}">{{ $slot->label }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div></div>
                @endif
            </div>
        </div>

        {{-- Data Grid --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden mb-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-max">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700 text-xs">
                            <th class="px-4 py-3 text-left font-semibold min-w-[160px]">Parameter</th>
                            <th class="px-4 py-3 text-center w-16">Unit</th>
                            <th class="px-4 py-3 text-center w-28 text-gray-400">Spec</th>
                            @foreach($timeSlots as $slot)
                            <th class="px-4 py-3 text-center min-w-[120px] text-indigo-600 dark:text-indigo-400">
                                {{ $slot->label }}
                            </th>
                            @endforeach
                            @if($timeSlots->count() === 0)
                            <th class="px-4 py-3 text-center min-w-[160px]">ค่า</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($parameters as $param)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50">
                            <td class="px-4 py-2.5">
                                <div class="font-medium">{{ $param->name }}</div>
                                @php
                                    $typeColors = [
                                        'number' => 'bg-blue-100 text-blue-600',
                                        'text' => 'bg-gray-100 text-gray-600',
                                        'boolean' => 'bg-green-100 text-green-600',
                                        'enum' => 'bg-yellow-100 text-yellow-700',
                                        'pass_fail' => 'bg-purple-100 text-purple-600',
                                    ];
                                @endphp
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $typeColors[$param->type] ?? 'bg-gray-100' }}">
                                    {{ $param->type }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-400 text-xs">{{ $param->unit ?? '-' }}</td>
                            <td class="px-4 py-2.5 text-center text-xs text-gray-400">
                                @if($param->type === 'number' && ($param->spec_min !== null || $param->spec_max !== null))
                                {{ $param->spec_min ?? '∞' }} – {{ $param->spec_max ?? '∞' }}
                                @else
                                —
                                @endif
                            </td>
                            @php $slotKeys = $timeSlots->count() > 0 ? $timeSlots->pluck('id') : collect([null]); @endphp
                            @foreach($slotKeys as $slotId)
                            <td class="px-3 py-2 text-center"
                                x-bind:class="getCellClass('{{ $param->id }}', '{{ $slotId }}', '{{ $param->type }}', {{ $param->spec_min ?? 'null' }}, {{ $param->spec_max ?? 'null' }})">
                                @php $inputName = $slotId ? "values[{$param->id}_{$slotId}]" : "values[{$param->id}]"; @endphp
                                @php $inputKey = $slotId ? "{$param->id}_{$slotId}" : "{$param->id}"; @endphp

                                @if($param->type === 'number')
                                <input type="number" step="0.01"
                                       name="{{ $inputName }}"
                                       class="w-full text-center text-sm rounded border border-gray-200 dark:border-gray-600 bg-transparent focus:outline-none focus:ring-1 focus:ring-indigo-400 px-2 py-1"
                                       placeholder="0"
                                       x-model="values['{{ $inputKey }}']"
                                       @input="checkSpec('{{ $inputKey }}', $event.target.value, {{ $param->spec_min ?? 'null' }}, {{ $param->spec_max ?? 'null' }}, '{{ $param->alert_on }}')">

                                @elseif($param->type === 'text')
                                <input type="text"
                                       name="{{ $inputName }}"
                                       class="w-full text-sm rounded border border-gray-200 dark:border-gray-600 bg-transparent focus:outline-none focus:ring-1 focus:ring-indigo-400 px-2 py-1"
                                       placeholder="—">

                                @elseif($param->type === 'boolean')
                                <input type="checkbox"
                                       name="{{ $inputName }}"
                                       value="1"
                                       class="rounded text-indigo-600">

                                @elseif($param->type === 'enum')
                                <select name="{{ $inputName }}"
                                        class="w-full text-sm rounded border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-400 px-2 py-1">
                                    <option value="">—</option>
                                    @foreach($param->options ?? [] as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>

                                @elseif($param->type === 'pass_fail')
                                <div class="flex items-center justify-center space-x-3">
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="{{ $inputName }}" value="Pass" class="text-green-500">
                                        <span class="text-xs text-green-600 font-medium">Pass</span>
                                    </label>
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="{{ $inputName }}" value="Fail" class="text-red-500">
                                        <span class="text-xs text-red-600 font-medium">Fail</span>
                                    </label>
                                </div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center space-x-3 justify-end">
            <a href="{{ route('checksheets.records', $template) }}" class="btn-secondary">
                <i class="ti ti-arrow-left mr-1"></i>ยกเลิก
            </a>
            <button type="button" @click="submitForm('draft')" class="btn-secondary flex items-center space-x-2">
                <i class="ti ti-device-floppy"></i><span>Save Draft</span>
            </button>
            <button type="button" @click="submitForm('submit')" class="btn-primary flex items-center space-x-2">
                <i class="ti ti-send"></i><span>Submit</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function checksheetFill(parameters, timeSlots) {
    return {
        parameters,
        timeSlots,
        values: {},
        specStatus: {},

        checkSpec(key, value, specMin, specMax, alertOn) {
            if (value === '' || value === null) {
                this.specStatus[key] = 'empty';
                return;
            }
            const num = parseFloat(value);
            if (isNaN(num)) {
                this.specStatus[key] = 'empty';
                return;
            }
            let out = false;
            if (alertOn === 'above_max' && specMax !== null && num > specMax) out = true;
            if (alertOn === 'below_min' && specMin !== null && num < specMin) out = true;
            if (alertOn === 'both') {
                if (specMax !== null && num > specMax) out = true;
                if (specMin !== null && num < specMin) out = true;
            }

            if (out) {
                this.specStatus[key] = 'alert';
            } else if (specMin !== null || specMax !== null) {
                this.specStatus[key] = 'ok';
            } else {
                this.specStatus[key] = 'empty';
            }
        },

        getCellClass(paramId, slotId, type, specMin, specMax) {
            if (type !== 'number') return '';
            const key = slotId && slotId !== 'null' ? `${paramId}_${slotId}` : paramId;
            const status = this.specStatus[key];
            if (status === 'alert') return 'bg-red-50 dark:bg-red-900/20';
            if (status === 'ok') return 'bg-green-50 dark:bg-green-900/20';
            return '';
        },

        submitForm(action) {
            document.getElementById('form-action').value = action;
            document.getElementById('checksheet-form').submit();
        },
    };
}
</script>
@endpush
