<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ $report->title }} — Export</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #fff; font-family: sans-serif; }

.slide-page {
    width: 297mm;
    height: 167mm;
    position: relative;
    overflow: hidden;
    page-break-after: always;
    break-after: page;
    background: #fff;
}
.slide-page:last-child { page-break-after: auto; break-after: auto; }

.el { position: absolute; box-sizing: border-box; }
.el-text { display: flex; align-items: flex-start; overflow: hidden; }
.el-kpi { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }

@media print {
    @page { size: 297mm 167mm; margin: 0; }
    body { background: #fff; }
    .no-print { display: none !important; }
}
</style>
</head>
<body>

{{-- Print button (hidden on print) --}}
<div class="no-print" style="padding:12px 16px;background:#f1f5f9;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:12px">
    <a href="{{ route('projects.reports.builder', [$project, $report]) }}"
       style="font-size:13px;color:#64748b;text-decoration:none">← Back to Editor</a>
    <span style="flex:1;font-size:14px;font-weight:600;color:#1e293b">{{ $report->title }}</span>
    <button onclick="window.print()"
            style="padding:6px 16px;background:#4f46e5;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer">
        Print / Save PDF
    </button>
</div>

@php
// Slides are 960x540. For 297mm x 167mm (A4 landscape equiv), scale = 297/960 * (1/0.264583mm-per-px) = ?
// Let's use CSS transform scale on the inner canvas
// 297mm = 1122.5px at 96dpi, 167mm = 631.5px
// But for simplicity, scale 960->297mm: each px = 297/960 mm = 0.309375mm
// We'll use CSS transform on a 960x540 box scaled inside a fixed mm box
@endphp

@foreach($report->slides as $slide)
<div class="slide-page" style="background:{{ $slide->bg_color }}">
    {{-- Inner canvas at 960x540 scaled to fit 297x167mm --}}
    <div style="position:absolute;inset:0;overflow:hidden">
        <div id="slide-{{ $slide->id }}"
             style="position:absolute;top:0;left:0;width:960px;height:540px;
                    transform:scale(0.310);transform-origin:top left;">
            @foreach($slide->elements->sortBy('z_index') as $el)
            <div class="el" style="left:{{ $el->x }}px;top:{{ $el->y }}px;width:{{ $el->w }}px;height:{{ $el->h }}px;z-index:{{ $el->z_index }}">
                @if($el->type === 'text')
                <div class="el-text w-full h-full"
                     style="font-size:{{ $el->props['font_size'] ?? 20 }}px;font-weight:{{ $el->props['font_weight'] ?? 'normal' }};color:{{ $el->props['color'] ?? '#1a1a1a' }};text-align:{{ $el->props['align'] ?? 'left' }};background:{{ $el->props['bg_color'] ?? 'transparent' }};padding:8px;font-style:{{ ($el->props['italic'] ?? false) ? 'italic' : 'normal' }};line-height:{{ $el->props['line_height'] ?? 1.4 }};">
                    {!! $el->props['content'] ?? '' !!}
                </div>

                @elseif($el->type === 'kpi')
                <div class="el-kpi w-full h-full"
                     style="background:{{ $el->props['bg'] ?? '#f5f3ff' }};border:2px solid {{ $el->props['accent'] ?? '#4f46e5' }};border-radius:8px;">
                    @php
                        $kpiMap = ['total'=>$kpi['total'],'done'=>$kpi['done'],'in_progress'=>$kpi['in_progress'],'overdue'=>$kpi['overdue'],'progress_pct'=>$kpi['progress_pct'].'%','members'=>$kpi['members']];
                        $kpiVal = $kpiMap[$el->props['data_source'] ?? 'total'] ?? '--';
                    @endphp
                    <div style="font-size:2.5em;font-weight:800;color:{{ $el->props['accent'] ?? '#4f46e5' }}">
                        {{ ($el->props['prefix'] ?? '') . $kpiVal . ($el->props['suffix'] ?? '') }}
                    </div>
                    <div style="font-size:0.85em;color:#666;margin-top:4px">{{ $el->props['label'] ?? '' }}</div>
                </div>

                @elseif($el->type === 'chart')
                <div style="width:100%;height:100%;background:rgba(255,255,255,0.9);border-radius:8px;padding:8px;display:flex;flex-direction:column">
                    <div style="font-size:13px;font-weight:600;color:#333;margin-bottom:4px">{{ $el->props['title'] ?? 'Chart' }}</div>
                    <canvas id="exp-chart-{{ $el->id }}" style="flex:1;min-height:0"></canvas>
                </div>

                @elseif($el->type === 'image')
                @if(!empty($el->props['url']))
                <img src="{{ $el->props['url'] }}"
                     style="width:100%;height:100%;object-fit:{{ $el->props['fit'] ?? 'cover' }}">
                @endif

                @elseif($el->type === 'shape')
                <div style="width:100%;height:100%;background:{{ $el->props['fill'] ?? '#4f46e5' }};opacity:{{ $el->props['opacity'] ?? 1 }};border-radius:{{ $el->props['border_radius'] ?? 0 }}px;border:{{ $el->props['border_width'] ?? 0 }}px solid {{ $el->props['border_color'] ?? '#000' }}"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

<script>
const CHART_DATA = @json($chartData);
const KPI        = @json($kpi);

function buildExportChart(canvas, el) {
    const ds = el.dataSource;
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
        colors = ['#6366f1','#8b5cf6','#a78bfa','#4f46e5','#818cf8'];
    }
    new Chart(canvas, {
        type: el.chartType || 'doughnut',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1, borderColor: '#fff' }] },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { position: 'right', labels: { font: { size: 10 }, boxWidth: 10 } } },
            scales: (['bar','line'].includes(el.chartType)) ? { y: { beginAtZero: true } } : {},
        },
    });
}

const ELEMENTS = @json($report->slides->flatMap(fn($s) => $s->elements->map(fn($e) => [
    'id'         => $e->id,
    'type'       => $e->type,
    'chartType'  => $e->props['chart_type'] ?? 'doughnut',
    'dataSource' => $e->props['data_source'] ?? 'status',
]))->values());

document.addEventListener('DOMContentLoaded', () => {
    ELEMENTS.filter(e => e.type === 'chart').forEach(e => {
        const canvas = document.getElementById('exp-chart-' + e.id);
        if (canvas) buildExportChart(canvas, e);
    });
    setTimeout(() => window.print(), 800);
});
</script>
</body>
</html>
