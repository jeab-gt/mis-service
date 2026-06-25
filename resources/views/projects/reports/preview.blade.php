<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $report->title }} — Preview</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0a0a0f; color: #fff; font-family: sans-serif; overflow: hidden; }

#preview-wrapper { width: 100vw; height: 100vh; display: flex; flex-direction: column; }
#slide-display { flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; }
#slide-canvas { position: relative; width: 960px; height: 540px; overflow: hidden; flex-shrink: 0; }
#controls { flex-shrink: 0; height: 52px; display: flex; align-items: center; justify-between;
    padding: 0 20px; background: rgba(255,255,255,0.04); border-top: 1px solid rgba(255,255,255,0.08); gap: 16px; }

.el { position: absolute; box-sizing: border-box; }
.el-text { display: flex; align-items: flex-start; overflow: hidden; }
.el-kpi { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }

.nav-btn { padding: 6px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.08); color: #ddd; cursor: pointer; font-size: 13px;
    transition: background 0.15s; }
.nav-btn:hover { background: rgba(255,255,255,0.15); }
.nav-btn:disabled { opacity: 0.3; cursor: default; }
.progress-track { flex: 1; height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
.progress-fill  { height: 100%; background: #6366f1; transition: width 0.3s; }
</style>
</head>
<body>
<div id="preview-wrapper" x-data="previewApp()" x-init="init()" @keydown.window="onKey($event)">

    <div id="slide-display" @click="next()">
        <div id="slide-canvas" x-ref="canvas"
             :style="`background:${currentSlide.bg_color};transform:scale(${scale});transform-origin:center center;`">

            <template x-for="el in currentSlide.elements" :key="el.id">
                <div class="el"
                     :style="`left:${el.x}px;top:${el.y}px;width:${el.w}px;height:${el.h}px;z-index:${el.z_index}`">

                    {{-- TEXT --}}
                    <div x-show="el.type === 'text'" class="el-text w-full h-full"
                         :style="`font-size:${el.props.font_size??20}px;font-weight:${el.props.font_weight??'normal'};color:${el.props.color??'#1a1a1a'};text-align:${el.props.align??'left'};background:${el.props.bg_color??'transparent'};padding:8px;font-style:${el.props.italic?'italic':'normal'};line-height:${el.props.line_height??1.4};`">
                        <div x-html="el.props.content ?? ''"></div>
                    </div>

                    {{-- TABLE --}}
                    <div x-show="el.type === 'table'"
                         style="width:100%;height:100%;overflow:hidden;background:#fff;border-radius:4px;padding:4px;font-size:11px">
                        <div x-html="el.props.content ?? ''"></div>
                    </div>

                    {{-- KPI --}}
                    <div x-show="el.type === 'kpi'" class="el-kpi w-full h-full rounded-lg"
                         :style="`background:${el.props.bg??'#f5f3ff'};border:2px solid ${el.props.accent??'#4f46e5'};`">
                        <div style="font-size:2em;font-weight:800" :style="`color:${el.props.accent??'#4f46e5'}`"
                             x-text="(el.props.prefix??'') + kpiValue(el.props.data_source) + (el.props.suffix??'')"></div>
                        <div style="font-size:0.8em;color:#666;margin-top:4px" x-text="el.props.label??''"></div>
                    </div>

                    {{-- CHART --}}
                    <div x-show="el.type === 'chart'"
                         style="width:100%;height:100%;background:rgba(255,255,255,0.9);border-radius:8px;padding:8px;display:flex;flex-direction:column">
                        <div style="font-size:12px;font-weight:600;color:#333;margin-bottom:4px" x-text="el.props.title??'Chart'"></div>
                        <canvas :id="'prev-chart-'+el.id" style="flex:1;min-height:0"></canvas>
                    </div>

                    {{-- IMAGE --}}
                    <div x-show="el.type === 'image'"
                         style="width:100%;height:100%;overflow:hidden">
                        <img x-show="el.props.url" :src="el.props.url"
                             :style="`width:100%;height:100%;object-fit:${el.props.fit??'cover'}`">
                    </div>

                    {{-- SHAPE --}}
                    <div x-show="el.type === 'shape'" style="width:100%;height:100%"
                         :style="`background:${el.props.fill??'#4f46e5'};opacity:${el.props.opacity??1};border-radius:${el.props.border_radius??0}px;border:${el.props.border_width??0}px solid ${el.props.border_color??'#000'}`">
                    </div>

                    {{-- GANTT MINI --}}
                    <div x-show="el.type === 'gantt_mini'"
                         style="width:100%;height:100%;overflow:hidden;border-radius:4px;background:#f8fafc"
                         x-html="buildGanttSvg(el)">
                    </div>

                    {{-- MILESTONE LIST --}}
                    <div x-show="el.type === 'milestone_list'"
                         style="width:100%;height:100%;overflow:hidden;border-radius:4px;display:flex;flex-direction:column;background:#fff">
                        <div style="font-size:11px;font-weight:600;color:#374151;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0"
                             x-text="el.props.title ?? 'Milestones'"></div>
                        <div style="overflow-y:auto;flex:1">
                            <template x-if="PROJECT_DATA.milestones.length === 0">
                                <div style="text-align:center;color:#9ca3af;font-size:10px;padding:12px">No milestones</div>
                            </template>
                            <template x-for="m in PROJECT_DATA.milestones" :key="m.id">
                                <div style="display:flex;align-items:center;gap:6px;padding:5px 10px;border-bottom:1px solid #f1f5f9;font-size:10px">
                                    <span x-text="m.is_completed ? '✅' : '⭕'"></span>
                                    <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="m.name"></span>
                                    <span style="color:#9ca3af;flex-shrink:0" x-text="m.due_date ?? '—'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- TEAM LIST --}}
                    <div x-show="el.type === 'team_list'"
                         style="width:100%;height:100%;overflow:hidden;border-radius:4px;display:flex;flex-direction:column;background:#fff">
                        <div style="font-size:11px;font-weight:600;color:#374151;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0"
                             x-text="el.props.title ?? 'Team Members'"></div>
                        <div style="overflow-y:auto;flex:1">
                            <template x-for="m in PROJECT_DATA.members" :key="m.id">
                                <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-bottom:1px solid #f1f5f9">
                                    <div style="width:22px;height:22px;border-radius:50%;background:#4f46e5;display:flex;align-items:center;justify-content:center;color:#fff;font-size:9px;font-weight:700;flex-shrink:0"
                                         x-text="m.name.charAt(0).toUpperCase()"></div>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:10px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="m.name"></div>
                                        <div style="font-size:9px;color:#9ca3af;text-transform:capitalize" x-text="m.role"></div>
                                    </div>
                                    <span style="font-size:9px;color:#9ca3af;flex-shrink:0" x-text="m.tasks_count + ' tasks'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- BLOCKER LIST --}}
                    <div x-show="el.type === 'blocker_list'"
                         style="width:100%;height:100%;overflow:hidden;border-radius:4px;display:flex;flex-direction:column"
                         :style="`background:${PROJECT_DATA.active_blockers_list.length ? '#fff5f5' : '#f0fdf4'}`">
                        <div style="font-size:11px;font-weight:600;padding:6px 10px;flex-shrink:0"
                             :class="PROJECT_DATA.active_blockers_list.length ? 'bg-red-50 text-red-700 border-b border-red-100' : 'bg-green-50 text-green-700 border-b border-green-100'"
                             x-text="el.props.title ?? 'Active Blockers'"></div>
                        <div style="overflow-y:auto;flex:1">
                            <template x-if="!PROJECT_DATA.active_blockers_list.length">
                                <div style="text-align:center;color:#16a34a;font-size:10px;padding:12px">✅ No active blockers</div>
                            </template>
                            <template x-for="(b, i) in PROJECT_DATA.active_blockers_list" :key="i">
                                <div style="display:flex;align-items:flex-start;gap:6px;padding:6px 10px;border-bottom:1px solid #fee2e2">
                                    <span style="flex-shrink:0;font-size:12px">🚨</span>
                                    <div style="min-width:0">
                                        <div style="font-size:10px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="b.task_title"></div>
                                        <div style="font-size:9px;color:#dc2626;margin-top:1px" x-text="b.description"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- DIVIDER --}}
                    <div x-show="el.type === 'divider'" style="width:100%;height:100%;display:flex;align-items:center">
                        <div style="width:100%" :style="`border-top:${el.props.thickness??2}px solid ${el.props.color??'#e5e7eb'}`"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Controls --}}
    <div id="controls">
        <a href="{{ route('projects.reports.builder', [$project, $report]) }}"
           style="color:#888;font-size:12px;text-decoration:none">← Edit</a>
        <button class="nav-btn" @click="prev()" :disabled="current === 0">← Prev</button>
        <div class="progress-track">
            <div class="progress-fill" :style="`width:${((current+1)/total)*100}%`"></div>
        </div>
        <span style="font-size:12px;color:#888;white-space:nowrap" x-text="`${current+1} / ${total}`"></span>
        <button class="nav-btn" @click="next()" :disabled="current >= total-1">Next →</button>
        <a href="{{ route('projects.reports.export', [$project, $report]) }}" target="_blank"
           class="nav-btn" style="text-decoration:none">Print</a>
    </div>
</div>

@php
$slidesJson = $report->slides->map(function($s) {
    return [
        'id'       => $s->id,
        'bg_color' => $s->bg_color,
        'elements' => $s->elements->map(function($e) {
            return [
                'id' => $e->id, 'type' => $e->type,
                'x' => $e->x, 'y' => $e->y, 'w' => $e->w, 'h' => $e->h,
                'z_index' => $e->z_index, 'props' => $e->props,
            ];
        })->values(),
    ];
})->values();
@endphp
<script>
const SLIDES       = @json($slidesJson);
const PROJECT_KPI  = @json($kpi);
const CHART_DATA   = @json($chartData);
const PROJECT_DATA = @json($projectData);

function previewApp() {
    return {
        slides: SLIDES,
        current: 0,
        scale: 1,
        _charts: {},

        get total() { return this.slides.length; },
        get currentSlide() { return this.slides[this.current]; },

        init() {
            this.recalc();
            window.addEventListener('resize', () => this.recalc());
            this.$nextTick(() => this.renderCharts());
        },

        recalc() {
            const w = window.innerWidth, h = window.innerHeight - 52;
            this.scale = Math.min(w / 960, h / 540);
        },

        prev() { if (this.current > 0) { this.current--; this.$nextTick(() => this.renderCharts()); } },
        next() { if (this.current < this.total - 1) { this.current++; this.$nextTick(() => this.renderCharts()); } },

        onKey(e) {
            if (e.key === 'ArrowRight' || e.key === 'Space' || e.key === 'PageDown') this.next();
            if (e.key === 'ArrowLeft'  || e.key === 'PageUp') this.prev();
            if (e.key === 'Escape') window.close();
        },

        kpiValue(src) {
            const m = { total: PROJECT_KPI.total, done: PROJECT_KPI.done, in_progress: PROJECT_KPI.in_progress,
                        overdue: PROJECT_KPI.overdue, progress_pct: PROJECT_KPI.progress_pct + '%', members: PROJECT_KPI.members };
            return m[src] ?? '--';
        },

        buildGanttSvg(el) {
            const tasks = PROJECT_DATA.tasks.filter(t => t.start_date && t.due_date);
            const W = el.w - 4;
            if (!tasks.length) {
                return `<svg width="${W}" height="60" xmlns="http://www.w3.org/2000/svg"><text x="${W/2}" y="35" text-anchor="middle" fill="#9ca3af" font-size="11" font-family="sans-serif">No tasks with scheduled dates</text></svg>`;
            }
            const allDates = tasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
            const minDate = new Date(Math.min(...allDates));
            const maxDate = new Date(Math.max(...allDates));
            const totalDays = Math.max(1, (maxDate - minDate) / 86400000 + 1);
            const labelW = 130, chartW = W - labelW;
            const rowH = Math.max(16, Math.min(26, (el.h - 36) / Math.max(tasks.length, 1)));
            const headerH = 28;
            const H = headerH + tasks.length * rowH + 2;
            const sc = { done:'#16a34a', in_progress:'#4f46e5', review:'#f59e0b', todo:'#94a3b8', cancelled:'#ef4444' };
            let s = `<svg width="${W}" height="${H}" xmlns="http://www.w3.org/2000/svg" style="font-family:sans-serif">`;
            s += `<rect width="${W}" height="${H}" fill="#f8fafc" rx="3"/>`;
            s += `<rect width="${W}" height="${headerH}" fill="#e2e8f0" rx="3"/>`;
            const cur = new Date(minDate.getFullYear(), minDate.getMonth(), 1);
            while (cur <= maxDate) {
                const xOff = Math.max(0, ((cur - minDate) / 86400000) / totalDays * chartW);
                const lx = labelW + xOff;
                s += `<line x1="${lx}" y1="${headerH}" x2="${lx}" y2="${H}" stroke="#e2e8f0" stroke-width="1"/>`;
                s += `<text x="${lx+2}" y="19" font-size="9" fill="#64748b">${cur.toLocaleString('default',{month:'short'})} ${String(cur.getFullYear()).slice(-2)}</text>`;
                cur.setMonth(cur.getMonth() + 1);
            }
            s += `<line x1="${labelW}" y1="${headerH}" x2="${labelW}" y2="${H}" stroke="#cbd5e1" stroke-width="1"/>`;
            tasks.forEach((t, i) => {
                const y = headerH + i * rowH;
                s += `<rect x="0" y="${y}" width="${W}" height="${rowH}" fill="${i%2===0?'#fff':'#f8fafc'}"/>`;
                const maxC = Math.floor(labelW / 6.2);
                const lbl = t.title.length > maxC ? t.title.slice(0, maxC-1)+'…' : t.title;
                s += `<text x="4" y="${y+rowH/2+4}" font-size="9" fill="#374151">${lbl}</text>`;
                const bx = labelW + ((new Date(t.start_date)-minDate)/86400000)/totalDays*chartW;
                const bw = Math.max(4, ((new Date(t.due_date)-new Date(t.start_date))/86400000+1)/totalDays*chartW);
                const color = sc[t.status] || '#94a3b8';
                s += `<rect x="${bx}" y="${y+3}" width="${bw}" height="${rowH-6}" rx="2" fill="${color}" opacity="0.85"/>`;
                if (t.progress_pct > 0) {
                    s += `<rect x="${bx}" y="${y+3}" width="${bw*t.progress_pct/100}" height="${rowH-6}" rx="2" fill="rgba(255,255,255,.3)"/>`;
                }
            });
            const today = new Date();
            if (today >= minDate && today <= maxDate) {
                const tx = labelW + ((today-minDate)/86400000)/totalDays*chartW;
                s += `<line x1="${tx}" y1="${headerH}" x2="${tx}" y2="${H}" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="4,2"/>`;
                s += `<text x="${tx+2}" y="${headerH-2}" font-size="8" fill="#ef4444">Today</text>`;
            }
            s += '</svg>';
            return s;
        },

        renderCharts() {
            this.currentSlide.elements.filter(e => e.type === 'chart').forEach(el => {
                const canvas = document.getElementById('prev-chart-' + el.id);
                if (!canvas) return;
                if (this._charts[el.id]) { this._charts[el.id].destroy(); }
                this._charts[el.id] = buildChart(canvas, el);
            });
        },
    };
}

function buildChart(canvas, el) {
    const ds = el.props.data_source ?? 'status';
    let labels, values, colors;
    if (ds === 'status') {
        labels = ['Todo','In Progress','Review','Done','Cancelled'];
        values = [CHART_DATA.tasksByStatus.todo, CHART_DATA.tasksByStatus.in_progress,
                  CHART_DATA.tasksByStatus.review, CHART_DATA.tasksByStatus.done, CHART_DATA.tasksByStatus.cancelled];
        colors = ['#94a3b8','#6366f1','#f59e0b','#22c55e','#ef4444'];
    } else if (ds === 'priority') {
        labels = ['Critical','High','Medium','Low'];
        values = [CHART_DATA.tasksByPriority.critical, CHART_DATA.tasksByPriority.high,
                  CHART_DATA.tasksByPriority.medium, CHART_DATA.tasksByPriority.low];
        colors = ['#ef4444','#f97316','#eab308','#22c55e'];
    } else {
        labels = CHART_DATA.tasksByAssignee.map(a => a.name);
        values = CHART_DATA.tasksByAssignee.map(a => a.count);
        colors = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#4f46e5','#818cf8'];
    }
    const type = el.props.chart_type ?? 'doughnut';
    return new Chart(canvas, {
        type,
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1, borderColor: '#fff', tension: 0.4 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { font: { size: 10 }, boxWidth: 10 } } },
            scales: (type === 'bar' || type === 'line') ? { y: { beginAtZero: true, ticks: { stepSize: 1 } } } : {},
        },
    });
}
</script>
</body>
</html>
