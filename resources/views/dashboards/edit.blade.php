@extends('layouts.app')
@section('title', 'Edit Dashboard: ' . $dashboard->name)
@section('breadcrumb')
<a href="{{ route('dashboards.index') }}" class="hover:text-indigo-500">Dashboards</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<a href="{{ route('dashboards.show', $dashboard) }}" class="hover:text-indigo-500">{{ $dashboard->name }}</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>Edit</span>
@endsection

@section('content')
<div x-data="dashboardBuilder(@js($dashboard->widgets->toArray()), @js($templates->toArray()))" class="space-y-4">
    {{-- Toolbar --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex items-center space-x-4">
        <h1 class="text-lg font-bold flex-1">{{ $dashboard->name }}</h1>
        <span x-show="saveStatus" x-text="saveStatus" class="text-sm text-green-600" x-cloak></span>
        <button @click="saveLayout()"
                :disabled="saving"
                class="btn-primary flex items-center space-x-2">
            <i class="ti" :class="saving ? 'ti-loader-2 animate-spin' : 'ti-device-floppy'"></i>
            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก Layout'"></span>
        </button>
        <a href="{{ route('dashboards.show', $dashboard) }}" class="btn-secondary text-sm">
            <i class="ti ti-eye mr-1"></i>View
        </a>
    </div>

    <div class="grid grid-cols-12 gap-4">
        {{-- LEFT: Widget Palette --}}
        <div class="col-span-12 lg:col-span-3 space-y-3">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <h3 class="font-semibold text-sm mb-3">Widget Types</h3>
                <div class="space-y-2">
                    @php
                        $widgetTypes = [
                            ['type' => 'line_chart', 'icon' => 'ti-chart-line', 'label' => 'Line Chart', 'color' => 'text-indigo-500'],
                            ['type' => 'bar_chart',  'icon' => 'ti-chart-bar',  'label' => 'Bar Chart',  'color' => 'text-blue-500'],
                            ['type' => 'gauge',      'icon' => 'ti-gauge',      'label' => 'Gauge',      'color' => 'text-green-500'],
                            ['type' => 'heatmap',    'icon' => 'ti-grid-4x4',   'label' => 'Heatmap',    'color' => 'text-yellow-500'],
                            ['type' => 'kpi_card',   'icon' => 'ti-brand-speedtest', 'label' => 'KPI Card', 'color' => 'text-purple-500'],
                            ['type' => 'data_table', 'icon' => 'ti-table',      'label' => 'Data Table', 'color' => 'text-gray-500'],
                        ];
                    @endphp
                    @foreach($widgetTypes as $wt)
                    <button type="button"
                            @click="addWidget('{{ $wt['type'] }}')"
                            class="w-full flex items-center space-x-3 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors text-left">
                        <i class="ti {{ $wt['icon'] }} text-xl {{ $wt['color'] }} flex-shrink-0"></i>
                        <span class="text-sm font-medium">{{ $wt['label'] }}</span>
                        <i class="ti ti-plus text-xs text-gray-400 ml-auto"></i>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- CENTER: Widget Canvas --}}
        <div class="col-span-12 lg:col-span-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 min-h-96">
                <h3 class="font-semibold text-sm mb-3 flex items-center justify-between">
                    <span>Canvas (<span x-text="widgets.length"></span> widgets)</span>
                    <span class="text-xs text-gray-400">คลิกที่ Widget เพื่อแก้ไข</span>
                </h3>

                <div class="space-y-2">
                    <template x-for="(widget, idx) in widgets" :key="idx">
                        <div class="p-3 rounded-xl border-2 cursor-pointer transition-colors"
                             :class="selectedIndex === idx ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-100 dark:border-gray-700 hover:border-indigo-200'"
                             @click="selectWidget(idx)">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                                    <i class="ti text-sm text-indigo-500"
                                       :class="{
                                           'ti-chart-line': widget.widget_type === 'line_chart',
                                           'ti-chart-bar': widget.widget_type === 'bar_chart',
                                           'ti-gauge': widget.widget_type === 'gauge',
                                           'ti-grid-4x4': widget.widget_type === 'heatmap',
                                           'ti-brand-speedtest': widget.widget_type === 'kpi_card',
                                           'ti-table': widget.widget_type === 'data_table',
                                       }"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate" x-text="widget.title || 'Untitled'"></p>
                                    <p class="text-xs text-gray-400" x-text="widget.widget_type"></p>
                                </div>
                                <div class="flex items-center space-x-1 text-xs text-gray-400">
                                    <span x-text="'W:' + widget.width"></span>
                                    <span>×</span>
                                    <span x-text="'H:' + widget.height"></span>
                                </div>
                                <button @click.stop="removeWidget(idx)"
                                        class="text-red-400 hover:text-red-600 p-1">
                                    <i class="ti ti-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="widgets.length === 0"
                         class="text-center py-12 text-gray-400">
                        <i class="ti ti-layout-dashboard text-4xl block mb-3"></i>
                        <p class="text-sm">คลิก Widget Type ทางซ้ายเพื่อเพิ่ม Widget</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Widget Config --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sticky top-4">
                <h3 class="font-semibold text-sm mb-3">Widget Config</h3>

                <div x-show="selectedIndex === null" class="text-center py-8 text-gray-400">
                    <i class="ti ti-click text-3xl block mb-2"></i>
                    <p class="text-xs">เลือก Widget เพื่อตั้งค่า</p>
                </div>

                <template x-if="selectedIndex !== null && widgets[selectedIndex]">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label text-xs">Title (TH)</label>
                            <input type="text" x-model="widgets[selectedIndex].title" class="form-input text-sm" placeholder="ชื่อ Widget">
                        </div>
                        <div>
                            <label class="form-label text-xs">Title (EN)</label>
                            <input type="text" x-model="widgets[selectedIndex].title_en" class="form-input text-sm" placeholder="English title">
                        </div>

                        <div>
                            <label class="form-label text-xs">Data Source Template</label>
                            {{-- x-effect + $nextTick: defer value-set until AFTER x-for options render --}}
                            <select class="form-select text-sm"
                                    x-effect="$nextTick(() => { $el.value = String(widgets[selectedIndex]?.config?.template_id ?? '') })"
                                    @change="widgets[selectedIndex].config.template_id = $event.target.value; widgets[selectedIndex].config.parameter_ids = []">
                                <option value="">— เลือก Template —</option>
                                <template x-for="t in templates" :key="t.id">
                                    <option :value="String(t.id)" x-text="t.name"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="widgets[selectedIndex].config.template_id">
                            <label class="form-label text-xs">Parameter</label>
                            <select class="form-select text-sm"
                                    x-effect="$nextTick(() => { $el.value = widgets[selectedIndex]?.config?.parameter_ids?.[0] != null ? String(widgets[selectedIndex].config.parameter_ids[0]) : '' })"
                                    @change="widgets[selectedIndex].config.parameter_ids = $event.target.value ? [parseInt($event.target.value)] : []">
                                <option value="">— ทุก Parameter —</option>
                                <template x-for="t in templates.filter(t => String(t.id) === String(widgets[selectedIndex].config.template_id))" :key="t.id">
                                    <template x-for="p in t.parameters" :key="p.id">
                                        <option :value="String(p.id)" x-text="p.name"></option>
                                    </template>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="form-label text-xs">Date Range</label>
                            <select x-model="widgets[selectedIndex].config.date_range" class="form-select text-sm">
                                <option value="last_7_days">7 วันล่าสุด</option>
                                <option value="last_30_days">30 วันล่าสุด</option>
                                <option value="this_month">เดือนนี้</option>
                                <option value="last_month">เดือนที่แล้ว</option>
                            </select>
                        </div>

                        {{-- Spec lines toggle — only relevant for line/bar charts --}}
                        <template x-if="['line_chart','bar_chart'].includes(widgets[selectedIndex].widget_type)">
                            <div class="flex items-center justify-between py-1">
                                <span class="text-xs text-gray-600 dark:text-gray-400">แสดงเส้น Spec (Min/Max/Target)</span>
                                <button type="button"
                                        @click="widgets[selectedIndex].config.show_spec_lines = !widgets[selectedIndex].config.show_spec_lines"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="widgets[selectedIndex].config.show_spec_lines ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                          :class="widgets[selectedIndex].config.show_spec_lines ? 'translate-x-4' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </template>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label text-xs">Width (1-12)</label>
                                <input type="number" x-model.number="widgets[selectedIndex].width"
                                       min="1" max="12" class="form-input text-sm">
                            </div>
                            <div>
                                <label class="form-label text-xs">Height</label>
                                <input type="number" x-model.number="widgets[selectedIndex].height"
                                       min="1" max="20" class="form-input text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label text-xs">Pos X</label>
                                <input type="number" x-model.number="widgets[selectedIndex].pos_x"
                                       min="0" max="11" class="form-input text-sm">
                            </div>
                            <div>
                                <label class="form-label text-xs">Pos Y</label>
                                <input type="number" x-model.number="widgets[selectedIndex].pos_y"
                                       min="0" class="form-input text-sm">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardBuilder(initialWidgets, templates) {
    return {
        widgets: (initialWidgets || []).map(w => ({
            ...w,
            config: {
                template_id:     w.config?.template_id  != null ? String(w.config.template_id) : '',
                parameter_ids:   w.config?.parameter_ids   ?? [],
                date_range:      w.config?.date_range      ?? 'last_30_days',
                show_spec_lines: w.config?.show_spec_lines ?? false,
            },
        })),
        templates: templates || [],
        selectedIndex: null,
        saving: false,
        saveStatus: '',

        addWidget(type) {
            const defaultTitles = {
                line_chart: 'Line Chart',
                bar_chart: 'Bar Chart',
                gauge: 'Gauge',
                heatmap: 'Heatmap',
                kpi_card: 'KPI',
                data_table: 'Data Table',
            };
            this.widgets.push({
                widget_type: type,
                title: defaultTitles[type] || type,
                title_en: '',
                config: { template_id: '', parameter_ids: [], date_range: 'last_30_days', show_spec_lines: false },
                pos_x: 0,
                pos_y: this.widgets.length,
                width: 6,
                height: 4,
            });
            this.selectedIndex = this.widgets.length - 1;
        },

        selectWidget(idx) {
            this.selectedIndex = idx;
        },

        removeWidget(idx) {
            if (confirm('ลบ Widget นี้?')) {
                this.widgets.splice(idx, 1);
                if (this.selectedIndex >= this.widgets.length) {
                    this.selectedIndex = this.widgets.length > 0 ? this.widgets.length - 1 : null;
                }
            }
        },

        async saveLayout() {
            this.saving = true;
            this.saveStatus = '';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch('{{ route('dashboards.save-layout', $dashboard) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ widgets: this.widgets }),
                });
                const data = await res.json();
                if (data.success) {
                    this.saveStatus = 'บันทึกสำเร็จ ✓';
                    setTimeout(() => this.saveStatus = '', 3000);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
