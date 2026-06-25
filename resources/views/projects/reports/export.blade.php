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
.el-gantt-js { width: 100%; height: 100%; }

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

@foreach($report->slides as $slide)
<div class="slide-page" style="background:{{ $slide->bg_color }}">
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

                @elseif($el->type === 'table')
                <div style="width:100%;height:100%;overflow:hidden;background:#fff;border-radius:4px;padding:4px;font-size:11px">
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

                @elseif($el->type === 'gantt_mini')
                <div id="exp-gantt-{{ $el->id }}" class="el-gantt-js" style="background:#f8fafc;border-radius:4px;overflow:hidden"></div>

                @elseif($el->type === 'milestone_list')
                <div style="width:100%;height:100%;overflow:hidden;border-radius:4px;display:flex;flex-direction:column;background:#fff">
                    <div style="font-size:11px;font-weight:600;color:#374151;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0">
                        {{ $el->props['title'] ?? 'Milestones' }}
                    </div>
                    @php $milestones = $projectData['milestones'] ?? []; @endphp
                    @if(empty($milestones))
                    <div style="text-align:center;color:#9ca3af;font-size:10px;padding:12px">No milestones</div>
                    @else
                    @foreach($milestones as $m)
                    <div style="display:flex;align-items:center;gap:6px;padding:5px 10px;border-bottom:1px solid #f1f5f9;font-size:10px">
                        <span>{{ $m['is_completed'] ? '✅' : '⭕' }}</span>
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $m['name'] }}</span>
                        <span style="color:#9ca3af;flex-shrink:0">{{ $m['due_date'] ?? '—' }}</span>
                    </div>
                    @endforeach
                    @endif
                </div>

                @elseif($el->type === 'team_list')
                <div style="width:100%;height:100%;overflow:hidden;border-radius:4px;display:flex;flex-direction:column;background:#fff">
                    <div style="font-size:11px;font-weight:600;color:#374151;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0">
                        {{ $el->props['title'] ?? 'Team Members' }}
                    </div>
                    @php $members = $projectData['members'] ?? []; @endphp
                    @foreach($members as $m)
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-bottom:1px solid #f1f5f9">
                        <div style="width:22px;height:22px;border-radius:50%;background:#4f46e5;display:flex;align-items:center;justify-content:center;color:#fff;font-size:9px;font-weight:700;flex-shrink:0">
                            {{ mb_strtoupper(mb_substr($m['name'], 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:10px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $m['name'] }}</div>
                            <div style="font-size:9px;color:#9ca3af">{{ $m['role'] }}</div>
                        </div>
                        <span style="font-size:9px;color:#9ca3af;flex-shrink:0">{{ $m['tasks_count'] }} tasks</span>
                    </div>
                    @endforeach
                </div>

                @elseif($el->type === 'blocker_list')
                @php $blockers = $projectData['active_blockers_list'] ?? []; @endphp
                <div style="width:100%;height:100%;overflow:hidden;border-radius:4px;display:flex;flex-direction:column;background:{{ count($blockers) ? '#fff5f5' : '#f0fdf4' }}">
                    <div style="font-size:11px;font-weight:600;padding:6px 10px;flex-shrink:0;{{ count($blockers) ? 'background:#fef2f2;color:#b91c1c;border-bottom:1px solid #fee2e2' : 'background:#f0fdf4;color:#15803d;border-bottom:1px solid #bbf7d0' }}">
                        {{ $el->props['title'] ?? 'Active Blockers' }}
                    </div>
                    @if(empty($blockers))
                    <div style="text-align:center;color:#16a34a;font-size:10px;padding:12px">✅ No active blockers</div>
                    @else
                    @foreach($blockers as $b)
                    <div style="display:flex;align-items:flex-start;gap:6px;padding:6px 10px;border-bottom:1px solid #fee2e2">
                        <span style="flex-shrink:0;font-size:12px">🚨</span>
                        <div style="min-width:0">
                            <div style="font-size:10px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $b['task_title'] }}</div>
                            <div style="font-size:9px;color:#dc2626;margin-top:1px">{{ $b['description'] }}</div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>

                @elseif($el->type === 'divider')
                <div style="width:100%;height:100%;display:flex;align-items:center">
                    <div style="width:100%;border-top:{{ $el->props['thickness'] ?? 2 }}px solid {{ $el->props['color'] ?? '#e5e7eb' }}"></div>
                </div>

                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

@php
$elementsJson = $report->slides->flatMap(function($s) {
    return $s->elements->map(function($e) {
        return [
            'id'         => $e->id,
            'type'       => $e->type,
            'w'          => $e->w,
            'h'          => $e->h,
            'chartType'  => $e->props['chart_type'] ?? 'doughnut',
            'dataSource' => $e->props['data_source'] ?? 'status',
        ];
    });
})->values();
@endphp
<script>
const CHART_DATA    = @json($chartData);
const PROJECT_DATA  = @json($projectData);
const ELEMENTS      = @json($elementsJson);

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

function buildGanttSvgExport(el) {
    const tasks = PROJECT_DATA.tasks.filter(t => t.start_date && t.due_date);
    const W = el.w - 4, H = el.h - 4;
    if (!tasks.length) {
        return `<svg width="${W}" height="${H}" xmlns="http://www.w3.org/2000/svg"><text x="${W/2}" y="${H/2+4}" text-anchor="middle" fill="#9ca3af" font-size="11" font-family="sans-serif">No tasks with scheduled dates</text></svg>`;
    }
    const allDates = tasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
    const minDate = new Date(Math.min(...allDates));
    const maxDate = new Date(Math.max(...allDates));
    const totalDays = Math.max(1, (maxDate - minDate) / 86400000 + 1);
    const labelW = 130, chartW = W - labelW;
    const rowH = Math.max(16, Math.min(26, (H - 36) / Math.max(tasks.length, 1)));
    const headerH = 28;
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
    });
    const today = new Date();
    if (today >= minDate && today <= maxDate) {
        const tx = labelW + ((today-minDate)/86400000)/totalDays*chartW;
        s += `<line x1="${tx}" y1="${headerH}" x2="${tx}" y2="${H}" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="4,2"/>`;
    }
    s += '</svg>';
    return s;
}

document.addEventListener('DOMContentLoaded', () => {
    ELEMENTS.filter(e => e.type === 'chart').forEach(e => {
        const canvas = document.getElementById('exp-chart-' + e.id);
        if (canvas) buildExportChart(canvas, e);
    });
    ELEMENTS.filter(e => e.type === 'gantt_mini').forEach(e => {
        const div = document.getElementById('exp-gantt-' + e.id);
        if (div) div.innerHTML = buildGanttSvgExport(e);
    });
    setTimeout(() => window.print(), 800);
});
</script>
</body>
</html>
