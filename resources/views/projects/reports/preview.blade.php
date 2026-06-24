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

                    <div x-show="el.type === 'text'" class="el-text w-full h-full"
                         :style="`font-size:${el.props.font_size??20}px;font-weight:${el.props.font_weight??'normal'};color:${el.props.color??'#1a1a1a'};text-align:${el.props.align??'left'};background:${el.props.bg_color??'transparent'};padding:8px;font-style:${el.props.italic?'italic':'normal'};line-height:${el.props.line_height??1.4};`">
                        <div x-html="el.props.content ?? ''"></div>
                    </div>

                    <div x-show="el.type === 'kpi'" class="el-kpi w-full h-full rounded-lg"
                         :style="`background:${el.props.bg??'#f5f3ff'};border:2px solid ${el.props.accent??'#4f46e5'};`">
                        <div style="font-size:2em;font-weight:800" :style="`color:${el.props.accent??'#4f46e5'}`"
                             x-text="(el.props.prefix??'') + kpiValue(el.props.data_source) + (el.props.suffix??'')"></div>
                        <div style="font-size:0.8em;color:#666;margin-top:4px" x-text="el.props.label??''"></div>
                    </div>

                    <div x-show="el.type === 'chart'"
                         style="width:100%;height:100%;background:rgba(255,255,255,0.9);border-radius:8px;padding:8px;display:flex;flex-direction:column">
                        <div style="font-size:12px;font-weight:600;color:#333;margin-bottom:4px" x-text="el.props.title??'Chart'"></div>
                        <canvas :id="'prev-chart-'+el.id" style="flex:1;min-height:0"></canvas>
                    </div>

                    <div x-show="el.type === 'image'"
                         style="width:100%;height:100%;overflow:hidden">
                        <img x-show="el.props.url" :src="el.props.url"
                             :style="`width:100%;height:100%;object-fit:${el.props.fit??'cover'}`">
                    </div>

                    <div x-show="el.type === 'shape'" style="width:100%;height:100%"
                         :style="`background:${el.props.fill??'#4f46e5'};opacity:${el.props.opacity??1};border-radius:${el.props.border_radius??0}px;border:${el.props.border_width??0}px solid ${el.props.border_color??'#000'}`">
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
const SLIDES    = @json($slidesJson);
const PROJECT_KPI  = @json($kpi);
const CHART_DATA   = @json($chartData);

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
