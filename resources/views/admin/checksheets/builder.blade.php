@extends('layouts.app')
@section('title', 'Builder: ' . $template->name)
@section('breadcrumb')
<span>Admin</span>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<a href="{{ route('admin.checksheets.index') }}" class="hover:text-indigo-500">Checksheet Templates</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>Builder</span>
@endsection

@section('content')
<div x-data="checksheetBuilder(
    @js($template->toArray()),
    @js($template->parameters->toArray()),
    @js($template->timeSlots->toArray()),
    @js($flows->toArray())
)" class="space-y-4">

    {{-- Top Toolbar --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 flex items-center space-x-4 mis-card">
        <div class="flex-1 min-w-0">
            <input type="text" x-model="templateName"
                   class="form-input text-lg font-bold w-full max-w-md"
                   placeholder="ชื่อ Template...">
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
            <span x-show="saveStatus" x-text="saveStatus" class="text-sm text-green-600" x-cloak></span>
            <button @click="saveTemplate()"
                    :disabled="saving"
                    class="btn-primary flex items-center space-x-2">
                <i class="ti" :class="saving ? 'ti-loader-2 animate-spin' : 'ti-device-floppy'"></i>
                <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก'"></span>
            </button>
            <a href="{{ route('admin.checksheets.index') }}" class="btn-secondary text-sm">
                <i class="ti ti-arrow-left mr-1"></i>กลับ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
        {{-- LEFT PANEL: Parameters --}}
        <div class="col-span-12 lg:col-span-4 space-y-3">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 mis-card">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-sm flex items-center space-x-2">
                        <i class="ti ti-sliders text-indigo-500"></i>
                        <span>Parameters (<span x-text="parameters.length"></span>)</span>
                    </h3>
                    <button @click="openAddParam()" class="text-xs btn-primary flex items-center space-x-1 py-1">
                        <i class="ti ti-plus"></i><span>Add</span>
                    </button>
                </div>

                <div id="param-list" class="space-y-2">
                    <template x-for="(param, index) in parameters" :key="index">
                        <div class="flex items-center space-x-2 p-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-750 cursor-move group">
                            <i class="ti ti-grip-vertical text-gray-300 cursor-move flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate" x-text="param.name"></p>
                                <div class="flex items-center space-x-1 mt-0.5">
                                    <span class="text-xs text-gray-400" x-text="param.unit || '-'"></span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full"
                                          :class="typeColor(param.type)"
                                          x-text="param.type"></span>
                                    <template x-if="param.spec_min !== null || param.spec_max !== null">
                                        <span class="text-xs text-gray-400">
                                            [<span x-text="param.spec_min ?? '∞'"></span>–<span x-text="param.spec_max ?? '∞'"></span>]
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editParam(index)" class="p-1 text-indigo-400 hover:text-indigo-600">
                                    <i class="ti ti-edit text-xs"></i>
                                </button>
                                <button @click="removeParam(index)" class="p-1 text-red-400 hover:text-red-600">
                                    <i class="ti ti-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="parameters.length === 0" class="text-center py-8 text-gray-400">
                        <i class="ti ti-sliders text-3xl block mb-2"></i>
                        <p class="text-xs">กด + Add เพื่อเพิ่ม Parameter</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CENTER PANEL: Preview --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 mis-card">
                <h3 class="font-semibold text-sm flex items-center space-x-2 mb-3">
                    <i class="ti ti-eye text-indigo-500"></i>
                    <span>Preview</span>
                </h3>
                <div class="overflow-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-left font-semibold min-w-[120px]">Parameter</th>
                                <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center w-12">Unit</th>
                                <template x-for="slot in timeSlots" :key="slot.id || slot.label">
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center min-w-[80px]" x-text="slot.label"></th>
                                </template>
                                <template x-if="timeSlots.length === 0">
                                    <th class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center">Value</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="param in parameters" :key="param.id || param.name">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5">
                                        <div x-text="param.name" class="font-medium"></div>
                                    </td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-gray-400" x-text="param.unit || '-'"></td>
                                    <template x-for="slot in (timeSlots.length > 0 ? timeSlots : [{}])" :key="slot.id || '_'">
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center">
                                            <template x-if="param.type === 'number'">
                                                <div class="w-full h-5 rounded bg-gray-100 dark:bg-gray-600"></div>
                                            </template>
                                            <template x-if="param.type === 'boolean'">
                                                <i class="ti ti-checkbox text-gray-300"></i>
                                            </template>
                                            <template x-if="param.type === 'pass_fail'">
                                                <span class="text-gray-300">P/F</span>
                                            </template>
                                            <template x-if="param.type === 'text'">
                                                <div class="w-full h-5 rounded bg-gray-100 dark:bg-gray-600"></div>
                                            </template>
                                            <template x-if="param.type === 'enum'">
                                                <div class="w-full h-5 rounded bg-gray-100 dark:bg-gray-600"></div>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                            <tr x-show="parameters.length === 0">
                                <td colspan="10" class="border border-gray-300 dark:border-gray-600 px-4 py-8 text-center text-gray-400">
                                    <i class="ti ti-table text-3xl block mb-2"></i>Preview จะแสดงหลังเพิ่ม Parameter
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Time Slots + Settings --}}
        <div class="col-span-12 lg:col-span-3 space-y-3">
            {{-- Time Slots --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 mis-card">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-sm flex items-center space-x-2">
                        <i class="ti ti-clock text-indigo-500"></i>
                        <span>Time Slots</span>
                    </h3>
                    <button @click="addSlot()" class="text-xs btn-primary flex items-center space-x-1 py-1">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
                <div class="space-y-1.5">
                    <template x-for="(slot, i) in timeSlots" :key="i">
                        <div class="flex items-center space-x-2">
                            <input type="text" x-model="slot.label"
                                   class="form-input text-sm flex-1 py-1"
                                   placeholder="เช่น 08:00">
                            <button @click="removeSlot(i)" class="text-red-400 hover:text-red-600">
                                <i class="ti ti-x text-xs"></i>
                            </button>
                        </div>
                    </template>
                    <div x-show="timeSlots.length === 0" class="text-xs text-gray-400 text-center py-2">
                        ไม่มี Time Slot
                    </div>
                </div>
            </div>

            {{-- Template Settings --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 mis-card">
                <h3 class="font-semibold text-sm flex items-center space-x-2 mb-3">
                    <i class="ti ti-settings text-indigo-500"></i>
                    <span>Settings</span>
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="form-label text-xs">Frequency</label>
                        <select x-model="templateFrequency" class="form-select text-sm">
                            <option value="realtime">Real-time</option>
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Factory Scope</label>
                        <select x-model="templateScope" class="form-select text-sm">
                            <option value="own_factory">Own Factory</option>
                            <option value="all_factories">All Factories</option>
                        </select>
                    </div>
                    <div x-show="flows.length > 0">
                        <label class="form-label text-xs">Approval Flow</label>
                        <select x-model="templateFlowId" class="form-select text-sm">
                            <option value="">— ไม่มี —</option>
                            <template x-for="flow in flows" :key="flow.id">
                                <option :value="flow.id" x-text="flow.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Parameter Modal --}}
    <div x-show="showParamModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         x-cloak>
        <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
            <h2 class="font-bold text-lg mb-4" x-text="editingIndex === null ? 'เพิ่ม Parameter' : 'แก้ไข Parameter'"></h2>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="form-label">ชื่อ Parameter <span class="text-red-500">*</span></label>
                        <input type="text" x-model="currentParam.name" class="form-input" placeholder="เช่น อุณหภูมิ Motor">
                    </div>
                    <div>
                        <label class="form-label">หน่วย</label>
                        <input type="text" x-model="currentParam.unit" class="form-input" placeholder="เช่น °C, mm">
                    </div>
                    <div>
                        <label class="form-label">Type <span class="text-red-500">*</span></label>
                        <select x-model="currentParam.type" class="form-select">
                            <option value="number">Number</option>
                            <option value="text">Text</option>
                            <option value="boolean">Boolean</option>
                            <option value="enum">Enum</option>
                            <option value="pass_fail">Pass/Fail</option>
                        </select>
                    </div>
                </div>

                {{-- Number specific --}}
                <template x-if="currentParam.type === 'number'">
                    <div class="space-y-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">Spec Limits</p>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="form-label text-xs">Min</label>
                                <input type="number" step="any" x-model="currentParam.spec_min" class="form-input text-sm" placeholder="Min">
                            </div>
                            <div>
                                <label class="form-label text-xs">Target</label>
                                <input type="number" step="any" x-model="currentParam.spec_target" class="form-input text-sm" placeholder="Target">
                            </div>
                            <div>
                                <label class="form-label text-xs">Max</label>
                                <input type="number" step="any" x-model="currentParam.spec_max" class="form-input text-sm" placeholder="Max">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label text-xs">Alert On</label>
                                <select x-model="currentParam.alert_on" class="form-select text-sm">
                                    <option value="above_max">Above Max</option>
                                    <option value="below_min">Below Min</option>
                                    <option value="both">Both</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">Alert Level</label>
                                <select x-model="currentParam.alert_level" class="form-select text-sm">
                                    <option value="warning">Warning</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Enum specific --}}
                <template x-if="currentParam.type === 'enum'">
                    <div class="space-y-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-yellow-700 dark:text-yellow-400">Options</p>
                            <button type="button" @click="currentParam.options = [...(currentParam.options || []), '']"
                                    class="text-xs text-yellow-600 hover:text-yellow-800">
                                <i class="ti ti-plus mr-1"></i>Add
                            </button>
                        </div>
                        <template x-for="(opt, oi) in (currentParam.options || [])" :key="oi">
                            <div class="flex items-center space-x-2">
                                <input type="text" :value="opt"
                                       @input="currentParam.options[oi] = $event.target.value"
                                       class="form-input text-sm flex-1" placeholder="Option value">
                                <button type="button" @click="currentParam.options.splice(oi, 1)"
                                        class="text-red-400 hover:text-red-600">
                                    <i class="ti ti-x text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" x-model="currentParam.is_active" id="param_is_active" class="rounded text-indigo-600">
                    <label for="param_is_active" class="text-sm cursor-pointer">Active</label>
                </div>
            </div>

            <div class="flex space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="button" @click="saveParam()" class="btn-primary flex-1">
                    <i class="ti ti-check mr-1"></i>บันทึก Parameter
                </button>
                <button type="button" @click="closeParamModal()" class="btn-secondary flex-1">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function checksheetBuilder(template, initialParams, initialSlots, flows) {
    return {
        templateName:      template.name || '',
        templateFrequency: template.frequency || 'daily',
        templateScope:     template.factory_scope || 'own_factory',
        templateFlowId:    template.flow_id || '',
        parameters:        initialParams || [],
        timeSlots:         initialSlots || [],
        flows:             flows || [],
        showParamModal:    false,
        editingIndex:      null,
        saving:            false,
        saveStatus:        '',
        currentParam: {},

        defaultParam() {
            return {
                name: '', unit: '', type: 'number', options: [],
                spec_min: null, spec_max: null, spec_target: null,
                alert_on: 'both', alert_level: 'warning',
                sort_order: 0, is_active: true,
            };
        },

        openAddParam() {
            this.editingIndex = null;
            this.currentParam = this.defaultParam();
            this.showParamModal = true;
        },

        editParam(index) {
            this.editingIndex = index;
            this.currentParam = JSON.parse(JSON.stringify(this.parameters[index]));
            this.showParamModal = true;
        },

        saveParam() {
            if (!this.currentParam.name.trim()) {
                alert('กรุณาระบุชื่อ Parameter');
                return;
            }
            if (this.editingIndex === null) {
                this.currentParam.sort_order = this.parameters.length;
                this.parameters.push(JSON.parse(JSON.stringify(this.currentParam)));
            } else {
                this.parameters[this.editingIndex] = JSON.parse(JSON.stringify(this.currentParam));
            }
            this.closeParamModal();
        },

        removeParam(index) {
            const p = this.parameters[index];
            const hasId = p && p.id;
            const msg = hasId
                ? `ลบ Parameter "${p.name}"?\n\nคำเตือน: ข้อมูลที่บันทึกไว้ (record values และ daily summaries) ของ parameter นี้จะถูกลบด้วยทันทีเมื่อกด บันทึก`
                : `ลบ Parameter "${p.name}"?`;
            if (confirm(msg)) {
                this.parameters.splice(index, 1);
            }
        },

        closeParamModal() {
            this.showParamModal = false;
            this.editingIndex = null;
        },

        addSlot() {
            this.timeSlots.push({ label: '', sort_order: this.timeSlots.length });
        },

        removeSlot(index) {
            this.timeSlots.splice(index, 1);
        },

        typeColor(type) {
            return {
                'number':    'bg-blue-100 text-blue-600',
                'text':      'bg-gray-100 text-gray-600',
                'boolean':   'bg-green-100 text-green-600',
                'enum':      'bg-yellow-100 text-yellow-700',
                'pass_fail': 'bg-purple-100 text-purple-600',
            }[type] || 'bg-gray-100 text-gray-600';
        },

        async saveTemplate() {
            this.saving = true;
            this.saveStatus = '';
            try {
                const params = this.parameters.map((p, i) => ({ ...p, sort_order: i }));
                const slots  = this.timeSlots.map((s, i) => ({ ...s, sort_order: i }));

                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res  = await fetch('{{ route('admin.checksheets.save', $template) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name:          this.templateName,
                        frequency:     this.templateFrequency,
                        factory_scope: this.templateScope,
                        flow_id:       this.templateFlowId || null,
                        parameters:    params,
                        time_slots:    slots,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    this.saveStatus = 'บันทึกสำเร็จ ✓';
                    if (data.template && data.template.parameters) {
                        this.parameters = data.template.parameters;
                    }
                    if (data.template && data.template.time_slots) {
                        this.timeSlots = data.template.time_slots;
                    }
                    setTimeout(() => this.saveStatus = '', 3000);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                alert('เกิดข้อผิดพลาดในการบันทึก: ' + e.message);
            } finally {
                this.saving = false;
            }
        },

        initSortable() {
            const el = document.getElementById('param-list');
            if (el && typeof Sortable !== 'undefined') {
                Sortable.create(el, {
                    animation: 150,
                    handle: '.ti-grip-vertical',
                    onEnd: (evt) => {
                        const moved = this.parameters.splice(evt.oldIndex, 1)[0];
                        this.parameters.splice(evt.newIndex, 0, moved);
                    },
                });
            }
        },

        init() {
            this.$nextTick(() => this.initSortable());
        },
    };
}
</script>
@endpush
