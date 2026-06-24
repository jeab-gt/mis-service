@extends('layouts.app')
@section('title', 'Form Designer — ' . $formTemplate->name)
@section('breadcrumb')
<a href="{{ route('admin.form-templates.index') }}" class="hover:text-indigo-600">Form Library</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $formTemplate->name }}</span>
@endsection

@section('content')
<div x-data="formDesigner(
    {!! htmlspecialchars(json_encode($formTemplate->schema ?? ['fields'=>[]]), ENT_QUOTES, 'UTF-8') !!},
    {!! htmlspecialchars(json_encode($optionSets->map(fn($o)=>['id'=>$o->id,'code'=>$o->code,'name_th'=>$o->name_th,'name_en'=>$o->name_en])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!},
    '{{ route('admin.form-templates.save', $formTemplate) }}'
)" class="flex gap-4 h-[calc(100vh-10rem)]">

    <!-- Left: field palette -->
    <div class="w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto flex-shrink-0 mis-card">
        <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-bold text-sm truncate">{{ $formTemplate->name }}</h3>
            <span class="text-xs text-gray-400">{{ $formTemplate->category }}</span>
        </div>
        <h3 class="font-semibold text-sm mb-3">ประเภทฟิลด์</h3>
        <div class="space-y-2">
            @foreach([
                ['type'=>'text',     'icon'=>'ti-text-size',  'label'=>'Text'],
                ['type'=>'textarea', 'icon'=>'ti-text-wrap',  'label'=>'Textarea'],
                ['type'=>'select',   'icon'=>'ti-selector',   'label'=>'Select'],
                ['type'=>'radio',    'icon'=>'ti-circle-dot', 'label'=>'Radio'],
                ['type'=>'checkbox', 'icon'=>'ti-checkbox',   'label'=>'Checkbox'],
                ['type'=>'date',     'icon'=>'ti-calendar',   'label'=>'Date'],
                ['type'=>'number',   'icon'=>'ti-number',     'label'=>'Number'],
                ['type'=>'file',     'icon'=>'ti-paperclip',  'label'=>'File Upload'],
            ] as $ft)
            <div class="cursor-pointer border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-2.5 flex items-center space-x-2 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
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
            <h3 class="font-semibold">Form Canvas</h3>
            <div class="flex items-center space-x-2">
                <span x-show="saveStatus" x-text="saveStatus"
                      :class="saveStatus === 'Saved!' ? 'text-green-600' : 'text-red-500'"
                      class="text-sm font-medium" x-cloak></span>
                <button @click="saveForm" class="btn-primary text-sm">
                    <i class="ti ti-device-floppy mr-1"></i>Save
                </button>
            </div>
        </div>

        <div id="field-canvas" x-ref="canvas"
             class="grid grid-cols-2 gap-3 min-h-[200px] p-2 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl">
            <template x-for="(field, idx) in schema.fields" :key="field.id">
                <div :class="field.width === 'full' ? 'col-span-2' : 'col-span-1'"
                     class="relative p-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-750 group cursor-pointer">
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
                <p class="text-sm">คลิกประเภทฟิลด์ด้านซ้ายเพื่อเพิ่ม</p>
            </div>
        </div>
    </div>

    <!-- Right: field editor -->
    <div class="w-72 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto flex-shrink-0 mis-card"
         x-show="editingIdx !== null" x-cloak>
        <h3 class="font-semibold text-sm mb-3">แก้ไขฟิลด์</h3>
        <template x-if="editingIdx !== null && schema.fields[editingIdx]">
            <div class="space-y-3">
                <div>
                    <label class="form-label text-xs">Label (TH)</label>
                    <input type="text" x-model="schema.fields[editingIdx].label_th" class="form-input text-sm">
                </div>
                <div>
                    <label class="form-label text-xs">Label (EN)</label>
                    <input type="text" x-model="schema.fields[editingIdx].label_en" class="form-input text-sm">
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" x-model="schema.fields[editingIdx].required" class="rounded text-indigo-600" id="req-chk">
                    <label for="req-chk" class="text-sm">Required</label>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" x-model="schema.fields[editingIdx].readonly" class="rounded text-indigo-600" id="ro-chk">
                    <label for="ro-chk" class="text-sm">Read-only</label>
                </div>

                <!-- Options for select/radio/checkbox -->
                <template x-if="['select','radio','checkbox'].includes(schema.fields[editingIdx].type)">
                    <div class="space-y-2">
                        <div>
                            <label class="form-label text-xs">Data Source</label>
                            <select x-model="schema.fields[editingIdx].data_source" class="form-select text-sm">
                                <option value="manual">Manual</option>
                                <option value="option_set">Option Set</option>
                            </select>
                        </div>
                        <template x-if="schema.fields[editingIdx].data_source === 'option_set'">
                            <div>
                                <label class="form-label text-xs">Option Set Code</label>
                                <select x-model="schema.fields[editingIdx].option_set_code" class="form-select text-sm">
                                    <option value="">-- เลือก --</option>
                                    <template x-for="os in optionSets" :key="os.code">
                                        <option :value="os.code" x-text="os.name_th + ' (' + os.code + ')'"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                        <template x-if="schema.fields[editingIdx].data_source !== 'option_set'">
                            <div>
                                <label class="form-label text-xs">Options</label>
                                <div class="space-y-1">
                                    <template x-for="(opt, oi) in (schema.fields[editingIdx].options || [])" :key="oi">
                                        <div class="flex items-center space-x-1">
                                            <input type="text" x-model="opt.label_th" placeholder="Label TH" class="form-input text-xs flex-1">
                                            <input type="text" x-model="opt.value"    placeholder="Value"    class="form-input text-xs w-20">
                                            <button @click="schema.fields[editingIdx].options.splice(oi,1)" class="text-red-400"><i class="ti ti-x text-xs"></i></button>
                                        </div>
                                    </template>
                                    <button @click="addOption(editingIdx)" class="text-xs text-indigo-500 hover:underline">+ Add Option</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- File accept -->
                <template x-if="schema.fields[editingIdx].type === 'file'">
                    <div>
                        <label class="form-label text-xs">Accept</label>
                        <input type="text" x-model="schema.fields[editingIdx].accept" placeholder="image/*" class="form-input text-sm">
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function formDesigner(initialSchema, optionSets, saveUrl) {
    return {
        schema:     initialSchema,
        optionSets: optionSets,
        editingIdx: null,
        saveStatus: '',

        addField(type) {
            const id = 'f' + (Date.now());
            this.schema.fields.push({
                id, type,
                label_th: '',
                label_en: '',
                required: false,
                readonly: false,
                width: 'full',
                data_source: 'manual',
                options: [],
            });
            this.editingIdx = this.schema.fields.length - 1;
        },

        editField(idx) { this.editingIdx = idx; },

        removeField(idx) {
            this.schema.fields.splice(idx, 1);
            this.editingIdx = null;
        },

        toggleWidth(idx) {
            this.schema.fields[idx].width = this.schema.fields[idx].width === 'full' ? 'half' : 'full';
        },

        addOption(idx) {
            if (!this.schema.fields[idx].options) this.schema.fields[idx].options = [];
            this.schema.fields[idx].options.push({ value: '', label_th: '', label_en: '' });
        },

        async saveForm() {
            this.saveStatus = 'Saving…';
            try {
                const res = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ schema: this.schema }),
                });
                this.saveStatus = res.ok ? 'Saved!' : 'Error!';
            } catch {
                this.saveStatus = 'Error!';
            }
            setTimeout(() => this.saveStatus = '', 3000);
        },
    };
}
</script>
@endpush
