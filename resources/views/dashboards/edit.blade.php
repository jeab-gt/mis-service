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
<div x-data="dashboardBuilder(@js($dashboard->widgets->toArray()), @js($templates->toArray()))"
     x-init="$nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => {
         computeScale();
         initInteract();
         window.addEventListener('resize', () => computeScale());
         $watch('widgets', () => $nextTick(() => computeScale()));
     })))"
     class="space-y-4">

    {{-- ① STICKY TOOLBAR --}}
    <div class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600
                px-4 py-3 -mx-4 -mt-4 mb-4 flex items-center gap-3 shadow-sm">
        <h1 class="text-lg font-bold flex-1 truncate">{{ $dashboard->name }}</h1>
        <span x-show="saveStatus" x-text="saveStatus" class="text-sm text-green-600 whitespace-nowrap" x-cloak></span>
        <button @click="saveLayout()" :disabled="saving"
                class="btn-primary flex items-center gap-2 whitespace-nowrap">
            <i class="ti" :class="saving ? 'ti-loader-2 animate-spin' : 'ti-device-floppy'"></i>
            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก Layout'"></span>
        </button>
        <a href="{{ route('dashboards.show', $dashboard) }}" class="btn-secondary text-sm whitespace-nowrap">
            <i class="ti ti-eye mr-1"></i>View
        </a>
    </div>

    <div class="flex gap-4">

        {{-- ② LEFT: Widget Palette --}}
        <div class="w-44 flex-shrink-0 space-y-3">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Widget Types</p>
                <div class="space-y-1">
                    @php
                        $widgetTypes = [
                            ['type' => 'line_chart', 'icon' => 'ti-chart-line',      'label' => 'Line Chart', 'color' => 'text-indigo-500'],
                            ['type' => 'bar_chart',  'icon' => 'ti-chart-bar',        'label' => 'Bar Chart',  'color' => 'text-blue-500'],
                            ['type' => 'gauge',      'icon' => 'ti-gauge',            'label' => 'Gauge',      'color' => 'text-green-500'],
                            ['type' => 'heatmap',    'icon' => 'ti-grid-4x4',         'label' => 'Heatmap',    'color' => 'text-yellow-500'],
                            ['type' => 'kpi_card',   'icon' => 'ti-brand-speedtest',  'label' => 'KPI Card',   'color' => 'text-purple-500'],
                            ['type' => 'data_table', 'icon' => 'ti-table',            'label' => 'Data Table', 'color' => 'text-gray-500'],
                        ];
                    @endphp
                    @foreach($widgetTypes as $wt)
                    <button type="button" @click="addWidget('{{ $wt['type'] }}')"
                            class="w-full flex items-center gap-2 px-2.5 py-2 rounded-xl border border-gray-300 dark:border-gray-600
                                   hover:border-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors text-left">
                        <i class="ti {{ $wt['icon'] }} text-lg {{ $wt['color'] }} flex-shrink-0"></i>
                        <span class="text-xs font-medium truncate">{{ $wt['label'] }}</span>
                    </button>
                    @endforeach
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-3 text-xs text-gray-400 space-y-1">
                <p class="font-medium text-gray-500">Tips</p>
                <p>• ลากเพื่อย้าย widget</p>
                <p>• ลาก edge ขวา/ล่างเพื่อ resize</p>
                <p>• เส้นสีฟ้า = align guide</p>
                <p>• Snap ทุก 20px</p>
            </div>
        </div>

        {{-- ③ CENTER: Free-form Canvas --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Canvas — <span x-text="widgets.length"></span> widgets
                    </span>
                    <span class="text-xs text-gray-400">คลิก widget เพื่อแก้ไข config</span>
                </div>

                {{-- Outer wrapper: clips to scaled visual size, no scroll --}}
                <div id="canvas-outer"
                     :style="`overflow:hidden; width:100%; height:${Math.ceil(canvasH * scale)}px;`">

                {{-- Inner canvas: fixed 1160px logical space, scaled to fit --}}
                <div id="widget-canvas"
                     class="relative rounded-xl"
                     :style="`width:${referenceW}px; height:${canvasH}px;
                              transform:scale(${scale}); transform-origin:top left;
                              background-color:#f8fafc;
                              background-image:radial-gradient(circle, #cbd5e1 1px, transparent 1px);
                              background-size:20px 20px;`">

                    {{-- Alignment guides --}}
                    <div x-show="guides.x !== null" class="absolute top-0 bottom-0 pointer-events-none z-30"
                         :style="`left:${guides.x}px; width:1px; background:#6366f1;`" style="display:none;"></div>
                    <div x-show="guides.y !== null" class="absolute left-0 right-0 pointer-events-none z-30"
                         :style="`top:${guides.y}px; height:1px; background:#6366f1;`" style="display:none;"></div>

                    {{-- Widget boxes --}}
                    <template x-for="(widget, idx) in widgets" :key="widget._uid">
                        <div class="widget-box absolute rounded-xl border-2 select-none overflow-hidden shadow-sm"
                             :id="'wb-' + widget._uid"
                             :data-idx="idx"
                             :class="selectedIndex === idx
                                 ? 'border-indigo-500 shadow-indigo-100'
                                 : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300'"
                             :style="`left:${widget.x}px; top:${widget.y}px; width:${widget.pw}px; height:${widget.ph}px; cursor:move;`"
                             @click.stop="selectedIndex = idx">

                            {{-- Header bar --}}
                            <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                                <i class="ti text-sm text-indigo-500 flex-shrink-0"
                                   :class="{
                                       'ti-chart-line':     widget.widget_type === 'line_chart',
                                       'ti-chart-bar':      widget.widget_type === 'bar_chart',
                                       'ti-gauge':          widget.widget_type === 'gauge',
                                       'ti-grid-4x4':       widget.widget_type === 'heatmap',
                                       'ti-brand-speedtest':widget.widget_type === 'kpi_card',
                                       'ti-table':          widget.widget_type === 'data_table',
                                   }"></i>
                                <span class="text-xs font-medium truncate flex-1" x-text="widget.title || 'Untitled'"></span>
                                <span class="text-xs text-gray-300 dark:text-gray-600 font-mono whitespace-nowrap"
                                      x-text="`${widget.pw}×${widget.ph}`"></span>
                                <button @click.stop="removeWidget(idx)"
                                        class="text-gray-300 hover:text-red-500 transition-colors flex-shrink-0 p-0.5">
                                    <i class="ti ti-x text-xs"></i>
                                </button>
                            </div>

                            {{-- Body placeholder --}}
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-center text-gray-200 dark:text-gray-700"
                                 :style="`top:37px;`">
                                <i class="ti text-4xl"
                                   :class="{
                                       'ti-chart-line':     widget.widget_type === 'line_chart',
                                       'ti-chart-bar':      widget.widget_type === 'bar_chart',
                                       'ti-gauge':          widget.widget_type === 'gauge',
                                       'ti-grid-4x4':       widget.widget_type === 'heatmap',
                                       'ti-brand-speedtest':widget.widget_type === 'kpi_card',
                                       'ti-table':          widget.widget_type === 'data_table',
                                   }"></i>
                            </div>

                            {{-- Resize handle (bottom-right) --}}
                            <div class="resize-handle absolute bottom-0 right-0 w-4 h-4 cursor-se-resize z-10"
                                 style="background: linear-gradient(135deg, transparent 50%, #94a3b8 50%);">
                            </div>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <div x-show="widgets.length === 0"
                         class="absolute inset-0 flex flex-col items-center justify-center text-gray-300 pointer-events-none">
                        <i class="ti ti-layout-dashboard text-5xl mb-3"></i>
                        <p class="text-sm">คลิก Widget Type ทางซ้ายเพื่อเพิ่ม Widget</p>
                    </div>
                </div>{{-- /widget-canvas --}}
                </div>{{-- /canvas-outer --}}
            </div>
        </div>

        {{-- ④ RIGHT: Widget Config --}}
        <div class="w-56 flex-shrink-0">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 sticky top-16 space-y-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Widget Config</p>

                <div x-show="selectedIndex === null" class="text-center py-8 text-gray-400">
                    <i class="ti ti-click text-3xl block mb-2"></i>
                    <p class="text-xs">คลิก widget เพื่อตั้งค่า</p>
                </div>

                <template x-if="selectedIndex !== null && widgets[selectedIndex]">
                    <div class="space-y-3">
                        <div>
                            <label class="form-label text-xs">Title (TH)</label>
                            <input type="text" x-model="widgets[selectedIndex].title"
                                   class="form-input text-sm" placeholder="ชื่อ Widget">
                        </div>
                        <div>
                            <label class="form-label text-xs">Title (EN)</label>
                            <input type="text" x-model="widgets[selectedIndex].title_en"
                                   class="form-input text-sm" placeholder="English title">
                        </div>

                        <div>
                            <label class="form-label text-xs">Data Source</label>
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

                        <template x-if="['line_chart','bar_chart'].includes(widgets[selectedIndex].widget_type)">
                            <div class="flex items-center justify-between py-1">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Spec Lines</span>
                                <button type="button"
                                        @click="widgets[selectedIndex].config.show_spec_lines = !widgets[selectedIndex].config.show_spec_lines"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors"
                                        :class="widgets[selectedIndex].config.show_spec_lines ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition"
                                          :class="widgets[selectedIndex].config.show_spec_lines ? 'translate-x-4' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </template>

                        {{-- Position & size (read-only display, updated by drag/resize) --}}
                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-200 dark:border-gray-600">
                            <div>
                                <label class="form-label text-xs text-gray-400">X (px)</label>
                                <input type="number" x-model.number="widgets[selectedIndex].x"
                                       @change="syncWidgetEl(selectedIndex)"
                                       min="0" class="form-input text-xs">
                            </div>
                            <div>
                                <label class="form-label text-xs text-gray-400">Y (px)</label>
                                <input type="number" x-model.number="widgets[selectedIndex].y"
                                       @change="syncWidgetEl(selectedIndex)"
                                       min="0" class="form-input text-xs">
                            </div>
                            <div>
                                <label class="form-label text-xs text-gray-400">W (px)</label>
                                <input type="number" x-model.number="widgets[selectedIndex].pw"
                                       @change="syncWidgetEl(selectedIndex)"
                                       min="200" class="form-input text-xs">
                            </div>
                            <div>
                                <label class="form-label text-xs text-gray-400">H (px)</label>
                                <input type="number" x-model.number="widgets[selectedIndex].ph"
                                       @change="syncWidgetEl(selectedIndex)"
                                       min="150" class="form-input text-xs">
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
    // ── Unit conversion: old grid (width 1-12) → pixels ─────────────────────
    const CANVAS_W = 1160;
    const COL_W    = CANVAS_W / 12;  // ~96.7 px per grid col
    const ROW_H    = 70;             // px per old grid row unit

    function isGridUnit(w) {
        return (w.width ?? 6) <= 12 && (w.pos_x ?? 0) <= 11;
    }

    function toPixels(w) {
        if (!isGridUnit(w)) {
            // Already stored as pixels
            return { x: w.pos_x ?? 0, y: w.pos_y ?? 0, pw: w.width ?? 400, ph: w.height ?? 280 };
        }
        return {
            x:  Math.round((w.pos_x ?? 0) * COL_W),
            y:  Math.round((w.pos_y ?? 0) * ROW_H),
            pw: Math.round((w.width  ?? 6) * COL_W),
            ph: Math.max(150, Math.round((w.height ?? 4) * ROW_H)),
        };
    }

    let _uid = 0;

    return {
        widgets: (initialWidgets || []).map(w => {
            const px = toPixels(w);
            return {
                ...w,
                _uid: ++_uid,
                config: {
                    template_id:     w.config?.template_id  != null ? String(w.config.template_id) : '',
                    parameter_ids:   w.config?.parameter_ids   ?? [],
                    date_range:      w.config?.date_range      ?? 'last_30_days',
                    show_spec_lines: w.config?.show_spec_lines ?? false,
                },
                x: px.x, y: px.y, pw: px.pw, ph: px.ph,
            };
        }),
        templates: templates || [],
        selectedIndex: null,
        saving:     false,
        saveStatus: '',
        guides: { x: null, y: null },
        scale: 1,

        get canvasH() {
            if (!this.widgets.length) return 680;
            return Math.max(680, ...this.widgets.map(w => w.y + w.ph + 40));
        },

        get referenceW() {
            if (!this.widgets.length) return 800;
            return Math.max(800, ...this.widgets.map(w => w.x + w.pw + 20));
        },

        computeScale() {
            const outer = document.getElementById('canvas-outer');
            if (!outer) return;
            const avail = outer.clientWidth;
            if (avail <= 0) return;
            const refW = this.referenceW;
            this.scale = refW > avail ? avail / refW : 1;
        },

        // ── Widget CRUD ──────────────────────────────────────────────────────
        addWidget(type) {
            const defaultTitles = {
                line_chart: 'Line Chart', bar_chart: 'Bar Chart',
                gauge: 'Gauge', heatmap: 'Heatmap',
                kpi_card: 'KPI Card', data_table: 'Data Table',
            };
            const uid = ++_uid;
            // Place new widget below existing ones, left-aligned
            const newY = this.widgets.length
                ? Math.max(...this.widgets.map(w => w.y + w.ph)) + 20
                : 20;
            this.widgets.push({
                id: null, _uid: uid,
                widget_type: type,
                title:    defaultTitles[type] || type,
                title_en: '',
                config: { template_id: '', parameter_ids: [], date_range: 'last_30_days', show_spec_lines: false },
                x: 20, y: newY, pw: 560, ph: 280,
            });
            this.selectedIndex = this.widgets.length - 1;
            this.$nextTick(() => {
                const el = document.getElementById('wb-' + uid);
                if (el) this._attachInteract(el);
            });
        },

        removeWidget(idx) {
            if (!confirm('ลบ Widget นี้?')) return;
            const uid = this.widgets[idx]._uid;
            const el  = document.getElementById('wb-' + uid);
            if (el) try { interact(el).unset(); } catch(e) {}
            this.widgets.splice(idx, 1);
            if (this.selectedIndex >= this.widgets.length) {
                this.selectedIndex = this.widgets.length > 0 ? this.widgets.length - 1 : null;
            } else if (this.selectedIndex > idx) {
                this.selectedIndex--;
            }
        },

        syncWidgetEl(idx) {
            // Called when x/y/pw/ph inputs are edited manually
            const w  = this.widgets[idx];
            const el = document.getElementById('wb-' + w._uid);
            if (!el) return;
            el.style.left   = w.x  + 'px';
            el.style.top    = w.y  + 'px';
            el.style.width  = w.pw + 'px';
            el.style.height = w.ph + 'px';
        },

        // ── interact.js setup ────────────────────────────────────────────────
        initInteract() {
            this.widgets.forEach((w, idx) => {
                const el = document.getElementById('wb-' + w._uid);
                if (el) this._attachInteract(el);
            });
        },

        _attachInteract(el) {
            const self = this;
            interact(el)
                .draggable({
                    ignoreFrom: '.resize-handle',
                    listeners: {
                        move(event) {
                            const idx = parseInt(event.target.dataset.idx);
                            const w   = self.widgets[idx];
                            if (!w) return;
                            // event.dx/dy are screen pixels; divide by scale for logical coords
                            w.x = Math.max(0, w.x + event.dx / self.scale);
                            w.y = Math.max(0, w.y + event.dy / self.scale);
                            event.target.style.left = w.x + 'px';
                            event.target.style.top  = w.y + 'px';
                            self._updateGuides(idx, w.x, w.y, w.pw, w.ph);
                        },
                        end(event) {
                            const idx = parseInt(event.target.dataset.idx);
                            const w   = self.widgets[idx];
                            if (!w) return;
                            // Snap to 20px grid, clamp within canvas
                            w.x = Math.max(0, Math.min(self.referenceW - w.pw, Math.round(w.x / 20) * 20));
                            w.y = Math.max(0, Math.round(w.y / 20) * 20);
                            event.target.style.left = w.x + 'px';
                            event.target.style.top  = w.y + 'px';
                            self.guides = { x: null, y: null };
                        }
                    },
                })
                .resizable({
                    edges: { right: true, bottom: true, left: false, top: false },
                    listeners: {
                        move(event) {
                            const idx = parseInt(event.target.dataset.idx);
                            const w   = self.widgets[idx];
                            if (!w) return;
                            // event.rect.width is screen pixels; divide by scale for logical size
                            w.pw = Math.max(200, Math.min(self.referenceW - w.x, event.rect.width  / self.scale));
                            w.ph = Math.max(150, event.rect.height / self.scale);
                            event.target.style.width  = w.pw + 'px';
                            event.target.style.height = w.ph + 'px';
                        },
                        end(event) {
                            const idx = parseInt(event.target.dataset.idx);
                            const w   = self.widgets[idx];
                            if (!w) return;
                            w.pw = Math.max(200, Math.round(w.pw / 20) * 20);
                            w.ph = Math.max(150, Math.round(w.ph / 20) * 20);
                            event.target.style.width  = w.pw + 'px';
                            event.target.style.height = w.ph + 'px';
                        }
                    },
                });
        },

        // ── Alignment guides ─────────────────────────────────────────────────
        _updateGuides(dragIdx, x, y, pw, ph) {
            const SNAP = 8;
            let gx = null, gy = null;
            const myEdgesX = [x, x + pw, x + Math.round(pw / 2)];
            const myEdgesY = [y, y + ph, y + Math.round(ph / 2)];

            for (let i = 0; i < this.widgets.length; i++) {
                if (i === dragIdx) continue;
                const o  = this.widgets[i];
                const ox = [o.x, o.x + o.pw, o.x + Math.round(o.pw / 2)];
                const oy = [o.y, o.y + o.ph, o.y + Math.round(o.ph / 2)];

                for (const mx of myEdgesX) for (const ex of ox) {
                    if (Math.abs(mx - ex) < SNAP) gx = ex;
                }
                for (const my of myEdgesY) for (const ey of oy) {
                    if (Math.abs(my - ey) < SNAP) gy = ey;
                }
            }
            this.guides = { x: gx, y: gy };
        },

        // ── Save ─────────────────────────────────────────────────────────────
        async saveLayout() {
            this.saving = true;
            this.saveStatus = '';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res  = await fetch('{{ route('dashboards.save-layout', $dashboard) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':  csrf,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        widgets: this.widgets.map(w => ({
                            id:          w.id,
                            widget_type: w.widget_type,
                            title:       w.title,
                            title_en:    w.title_en   || null,
                            config:      w.config,
                            pos_x:       Math.round(w.x),
                            pos_y:       Math.round(w.y),
                            width:       Math.round(w.pw),
                            height:      Math.round(w.ph),
                        })),
                    }),
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
