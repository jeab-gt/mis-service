@extends('layouts.app')
@section('title', 'Form Designer — ' . $app->name)
@section('breadcrumb')
<a href="{{ route('admin.apps.index') }}" class="hover:text-indigo-600">Apps</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>Form Designer</span>
@endsection
@section('content')
<div x-data="formDesigner({!! htmlspecialchars(json_encode($app->form_schema ?? ['fields'=>[]]), ENT_QUOTES, 'UTF-8') !!}, {!! htmlspecialchars(json_encode($optionSets->map(fn($o)=>['id'=>$o->id,'code'=>$o->code,'name_th'=>$o->name_th,'name_en'=>$o->name_en])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!})" class="flex gap-4 h-[calc(100vh-10rem)]">
    <!-- Left: field palette -->
    <div class="w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto flex-shrink-0 mis-card">
        <h3 class="font-semibold text-sm mb-3">{{ app()->getLocale() === 'th' ? 'ประเภทฟิลด์' : 'Field Types' }}</h3>
        <div class="space-y-2">
            @foreach([['type'=>'text','icon'=>'ti-text-size','label'=>'Text'],['type'=>'textarea','icon'=>'ti-text-wrap','label'=>'Textarea'],['type'=>'select','icon'=>'ti-selector','label'=>'Select'],['type'=>'radio','icon'=>'ti-circle-dot','label'=>'Radio'],['type'=>'checkbox','icon'=>'ti-checkbox','label'=>'Checkbox'],['type'=>'date','icon'=>'ti-calendar','label'=>'Date'],['type'=>'number','icon'=>'ti-number','label'=>'Number'],['type'=>'file','icon'=>'ti-paperclip','label'=>'File Upload']] as $ft)
            <div class="palette-item cursor-pointer border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-2.5 flex items-center space-x-2 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                 @click="addField('{{ $ft['type'] }}')">
                <i class="ti {{ $ft['icon'] }} text-indigo-500"></i>
                <span class="text-sm">{{ $ft['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Center: canvas -->
    <div class="flex-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto mis-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">{{ app()->getLocale() === 'th' ? 'ออกแบบฟอร์ม' : 'Form Canvas' }}</h3>
            <button @click="saveForm" class="btn-primary text-sm">
                <i class="ti ti-device-floppy mr-1"></i>{{ __('common.save') }}
            </button>
        </div>

        <div id="field-canvas" x-ref="canvas" class="grid grid-cols-2 gap-3 min-h-[200px] p-2 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl">
            <template x-for="(field, idx) in schema.fields" :key="field.id">
                <div :class="field.width === 'full' ? 'col-span-2' : 'col-span-1'"
                     class="relative p-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-750 group cursor-move">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-indigo-600 uppercase" x-text="field.type"></span>
                        <div class="flex space-x-1 opacity-0 group-hover:opacity-100">
                            <button @click="editField(idx)" class="p-1 text-blue-500 hover:text-blue-700"><i class="ti ti-edit text-xs"></i></button>
                            <button @click="removeField(idx)" class="p-1 text-red-400 hover:text-red-600"><i class="ti ti-trash text-xs"></i></button>
                            <button @click="toggleWidth(idx)" class="p-1 text-gray-400 hover:text-gray-600 text-xs" x-text="field.width === 'full' ? '½' : '⬛'"></button>
                        </div>
                    </div>
                    <p class="text-sm font-medium" x-text="field.label_th || field.label_en || 'Unnamed'"></p>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="field.required ? 'Required' : 'Optional'"></p>
                </div>
            </template>
            <div x-show="schema.fields.length === 0" class="col-span-2 text-center py-12 text-gray-400">
                <i class="ti ti-drag-drop text-4xl block mb-2"></i>
                <p class="text-sm">{{ app()->getLocale() === 'th' ? 'คลิกประเภทฟิลด์ด้านซ้ายเพื่อเพิ่ม' : 'Click field types on the left to add' }}</p>
            </div>
        </div>
    </div>

    <!-- Right: field editor -->
    <div class="w-72 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto flex-shrink-0 mis-card" x-show="editingIdx !== null">
        <h3 class="font-semibold text-sm mb-3">{{ app()->getLocale() === 'th' ? 'แก้ไขฟิลด์' : 'Edit Field' }}</h3>
        <template x-if="editingIdx !== null && schema.fields[editingIdx]">
            <div class="space-y-3">
                <div>
                    <label class="form-label text-xs">Label (TH)</label>
                    <input type="text" class="form-input text-sm" x-model="schema.fields[editingIdx].label_th">
                </div>
                <div>
                    <label class="form-label text-xs">Label (EN)</label>
                    <input type="text" class="form-input text-sm" x-model="schema.fields[editingIdx].label_en">
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" class="rounded" x-model="schema.fields[editingIdx].required">
                    <label class="text-sm">Required</label>
                </div>
                <div x-show="schema.fields[editingIdx].type === 'file'">
                    <label class="form-label text-xs">Accept</label>
                    <input type="text" class="form-input text-sm" placeholder="image/*"
                           x-model="schema.fields[editingIdx].accept">
                </div>
                <div x-show="['select','radio','checkbox'].includes(schema.fields[editingIdx].type)" class="space-y-2">
                    <div>
                        <label class="form-label text-xs">Data Source</label>
                        <select class="form-select text-sm" x-model="schema.fields[editingIdx].data_source">
                            <option value="manual">Manual (enter below)</option>
                            <option value="option_set">Option Set</option>
                        </select>
                    </div>
                    <div x-show="schema.fields[editingIdx].data_source === 'option_set'">
                        <label class="form-label text-xs">Option Set</label>
                        <select class="form-select text-sm" x-model="schema.fields[editingIdx].option_set_code">
                            <option value="">-- Select Option Set --</option>
                            <template x-for="os in optionSets" :key="os.id">
                                <option :value="os.code" x-text="os.name_th + ' (' + os.code + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="!schema.fields[editingIdx].data_source || schema.fields[editingIdx].data_source === 'manual'">
                        <label class="form-label text-xs">Options (one per line: value|th|en)</label>
                        <textarea rows="5" class="form-input text-xs font-mono"
                                  :value="(schema.fields[editingIdx].options||[]).map(o=>o.value+'|'+(o.label_th||'')+'|'+(o.label_en||'')).join('\n')"
                                  @input="updateOptions($event, editingIdx)"></textarea>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<form id="save-form" method="POST" action="{{ route('admin.apps.update', $app) }}" class="hidden">
    @csrf @method('PUT')
    <input type="hidden" name="name" value="{{ $app->name }}">
    <input type="hidden" name="slug" value="{{ $app->slug }}">
    <input type="hidden" name="category" value="{{ $app->category }}">
    <input type="hidden" name="description" value="{{ $app->description }}">
    <input type="hidden" name="icon" value="{{ $app->icon }}">
    <input type="hidden" name="is_active" value="{{ $app->is_active ? '1' : '0' }}">
    <input type="hidden" id="form_schema_input" name="form_schema" value="{{ json_encode($app->form_schema) }}">
    <input type="hidden" name="flow_schema" value="{{ json_encode($app->flow_schema) }}">
</form>
@endsection

@push('scripts')
<script>
function formDesigner(initialSchema, optionSets) {
    return {
        schema: initialSchema || { fields: [] },
        editingIdx: null,
        fieldCounter: (initialSchema?.fields?.length || 0) + 1,
        optionSets: optionSets || [],

        init() {
            this.$nextTick(() => {
                Sortable.create(this.$refs.canvas, {
                    animation: 150,
                    ghostClass: 'opacity-40',
                    handle: '.cursor-move',
                    onEnd: (evt) => {
                        const moved = this.schema.fields.splice(evt.oldIndex, 1)[0];
                        this.schema.fields.splice(evt.newIndex, 0, moved);
                        if (this.editingIdx !== null) this.editingIdx = null;
                    }
                });
            });
        },

        addField(type) {
            this.schema.fields.push({
                id: 'f' + (this.fieldCounter++),
                type,
                label_th: '',
                label_en: '',
                required: false,
                width: 'full',
                data_source: 'manual',
                option_set_code: '',
                options: ['select','radio','checkbox'].includes(type) ? [] : undefined,
            });
            this.editingIdx = this.schema.fields.length - 1;
        },

        removeField(idx) {
            this.schema.fields.splice(idx, 1);
            this.editingIdx = null;
        },

        editField(idx) {
            this.editingIdx = idx;
        },

        toggleWidth(idx) {
            this.schema.fields[idx].width = this.schema.fields[idx].width === 'full' ? 'half' : 'full';
        },

        updateOptions(event, idx) {
            const lines = event.target.value.split('\n').filter(l => l.trim());
            this.schema.fields[idx].options = lines.map(l => {
                const parts = l.split('|');
                return { value: parts[0]?.trim(), label_th: parts[1]?.trim(), label_en: parts[2]?.trim() };
            });
        },

        saveForm() {
            document.getElementById('form_schema_input').value = JSON.stringify(this.schema);
            document.getElementById('save-form').submit();
        }
    }
}
</script>
@endpush
