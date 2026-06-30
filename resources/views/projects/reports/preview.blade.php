<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $report->title }} — Preview</title>
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; background: #0a0a0f; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #fff; }

#preview-wrapper { width: 100vw; height: 100vh; display: flex; flex-direction: column; }
#slide-display   { flex: 1; position: relative; overflow: hidden; cursor: pointer; }
#controls {
    flex-shrink: 0; height: 52px; display: flex; align-items: center;
    padding: 0 20px; gap: 16px;
    background: rgba(255,255,255,.04); border-top: 1px solid rgba(255,255,255,.08);
}

#slide-frame {
    position: absolute; top: 50%; left: 50%;
    background: #fff; overflow: hidden;
    box-shadow: 0 8px 48px rgba(0,0,0,.7);
}

/* CKEditor content reset in preview */
.ck-content { color: #1a1a1a; font-size: 14px; line-height: 1.7; }
.ck-content p    { margin: 0 0 6px; min-height: 1.4em; }
.ck-content h1   { font-size: 2em; font-weight: 700; margin: 14px 0 8px; }
.ck-content h2   { font-size: 1.5em; font-weight: 600; margin: 12px 0 7px; }
.ck-content h3   { font-size: 1.25em; font-weight: 600; margin: 10px 0 6px; }
.ck-content h4   { font-size: 1.1em; font-weight: 600; margin: 8px 0 5px; }
.ck-content ul, .ck-content ol { padding-left: 28px; margin: 6px 0; }
.ck-content li   { margin: 2px 0; }
.ck-content table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.ck-content td, .ck-content th { border: 1px solid #d1d5db; padding: 8px 12px; text-align: left; }
.ck-content th   { background: #f3f4f6; font-weight: 600; }
.ck-content hr   { border: none; border-top: 2px solid #e5e7eb; margin: 18px 0; }
.ck-content img  { max-width: 100%; height: auto; border-radius: 4px; display: block; margin: 6px 0; }
.ck-content blockquote { border-left: 4px solid #6366f1; margin: 10px 0; padding: 6px 16px; background: #f5f3ff; border-radius: 0 6px 6px 0; }
.ck-content a    { color: #6366f1; text-decoration: underline; }
.ck-content figure.image { margin: 6px 0; }
.ck-content figure.image img { margin: 0; }

#slide-content { position: absolute; inset: 0; padding: 60px 72px; overflow: hidden; }
#widget-overlay { position: absolute; inset: 0; pointer-events: none; z-index: 2; }

.nav-btn {
    padding: 6px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.08); color: #ddd; cursor: pointer; font-size: 13px;
}
.nav-btn:hover    { background: rgba(255,255,255,.15); }
.nav-btn:disabled { opacity: .3; cursor: default; }
.progress-track { flex: 1; height: 3px; background: rgba(255,255,255,.1); border-radius: 2px; overflow: hidden; }
.progress-fill  { height: 100%; background: #6366f1; transition: width .3s; }
</style>
</head>
<body>
<div id="preview-wrapper">
    <div id="slide-display" onclick="next()">
        <div id="slide-frame">
            <div id="slide-content" class="ck-content"></div>
            <div id="widget-overlay"></div>
        </div>
    </div>
    <div id="controls">
        <a href="{{ route('projects.reports.builder', [$project, $report]) }}"
           style="color:#888;font-size:12px;text-decoration:none;white-space:nowrap">← Edit</a>
        <button class="nav-btn" id="btn-prev" onclick="event.stopPropagation();prev()">← Prev</button>
        <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
        <span id="slide-counter" style="font-size:12px;color:#888;white-space:nowrap"></span>
        <button class="nav-btn" id="btn-next" onclick="event.stopPropagation();next()">Next →</button>
        <a href="{{ route('projects.reports.export', [$project, $report]) }}" target="_blank"
           class="nav-btn" style="text-decoration:none" onclick="event.stopPropagation()">Print</a>
    </div>
</div>

@php
$slidesJson = $report->slides->sortBy('slide_order')->map(fn($s) => [
    'id'           => $s->id,
    'bg_color'     => $s->bg_color ?? '#ffffff',
    'html_content' => $s->html_content ?? '',
    'widgets_data' => $s->widgets_data ?? [],
])->values();
@endphp
<script>
const SLIDES       = @json($slidesJson);
const PROJECT_KPI  = @json($kpi);
const CHART_DATA   = @json($chartData);
const PROJECT_DATA = @json($projectData);

const FRAME_W = 1280, FRAME_H = 720;
let current = 0;

// ── Layout ────────────────────────────────────────────────────────────────
function recalc() {
    const avW = window.innerWidth  - 48;
    const avH = window.innerHeight - 52 - 48;
    const scale = Math.min(avW / FRAME_W, avH / FRAME_H, 1);
    const frame = document.getElementById('slide-frame');
    frame.style.width  = FRAME_W + 'px';
    frame.style.height = FRAME_H + 'px';
    frame.style.transform = `translate(-50%,-50%) scale(${scale})`;
}
window.addEventListener('resize', recalc);

// ── Navigation ────────────────────────────────────────────────────────────
function prev() { if (current > 0) { current--; loadSlide(); } }
function next() { if (current < SLIDES.length - 1) { current++; loadSlide(); } }

document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight' || e.key === 'Space'    || e.key === 'PageDown') { e.preventDefault(); next(); }
    if (e.key === 'ArrowLeft'  || e.key === 'PageUp')  { e.preventDefault(); prev(); }
    if (e.key === 'Escape') window.close();
});

// ── Load slide ────────────────────────────────────────────────────────────
function loadSlide() {
    const slide = SLIDES[current];
    if (!slide) return;

    document.getElementById('slide-frame').style.background = slide.bg_color || '#fff';
    document.getElementById('slide-content').innerHTML = slide.html_content || '';
    document.getElementById('slide-counter').textContent = `${current + 1} / ${SLIDES.length}`;
    document.getElementById('progress-fill').style.width = `${((current + 1) / SLIDES.length) * 100}%`;
    document.getElementById('btn-prev').disabled = (current === 0);
    document.getElementById('btn-next').disabled = (current >= SLIDES.length - 1);

    const overlay = document.getElementById('widget-overlay');
    overlay.innerHTML = '';
    const SHAPE_TYPES = [
        'image','rectangle','circle','line','arrow',
        'rounded-rectangle','triangle','diamond','pentagon',
        'hexagon','star','arrow-right','arrow-left',
        'arrow-up','arrow-down','double-arrow','textbox',
    ];
    (slide.widgets_data || []).forEach(w => {
        const isShape   = SHAPE_TYPES.includes(w.type);
        const DATA_WIDGET_TYPES = ['kpi','chart','gantt','milestone','team','blocker'];
        const isDataWidget = DATA_WIDGET_TYPES.includes(w.type);
        const dwBorder = `1px solid ${w.style?.borderColor || '#e5e7eb'}`;
        const dwRadius = (w.style?.borderRadius ?? 8) + 'px';
        const el = document.createElement('div');
        el.style.cssText = `position:absolute;left:${w.x||0}px;top:${w.y||0}px;`
            + `width:${w.w||200}px;height:${w.h||150}px;`
            + `background:transparent;overflow:visible;box-sizing:border-box;`
            + `transform:rotate(${w.rotation||0}deg);transform-origin:center center;`;
        const inner = document.createElement('div');
        inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;`
            + (isShape
                ? 'overflow:visible;'
                : `background:white;`
                + `border-radius:${isDataWidget ? dwRadius : '8px'};`
                + `border:${isDataWidget ? dwBorder : '1px solid #e5e7eb'};overflow:hidden;`);
        if (w.style?.shadow) inner.style.filter = 'drop-shadow(0 6px 10px rgba(0,0,0,.3))';
        inner.innerHTML = renderWidgetContent(w);
        el.appendChild(inner);
        overlay.appendChild(el);
    });
}

function formatGanttDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return `${d.getDate()}/${d.getMonth()+1}`;
}

// ── Widget content (same logic as builder) ─────────────────────────────────
function renderWidgetContent(widget) {
    switch (widget.type) {

        case 'image': {
            const s = widget.style || {};
            return `<img src="${widget.imageUrl}"
                         style="width:100%;height:100%;object-fit:contain;
                                border:${s.borderWidth || 0}px solid ${s.borderColor || 'transparent'};
                                border-radius:${s.borderRadius || 0}px;
                                display:block;box-sizing:border-box" />`;
        }

        case 'rectangle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || '#6366f1');
            return `<div style="width:100%;height:100%;background:${fillColor};
                        border:${s.borderWidth ?? 2}px solid ${s.borderColor || '#4f46e5'};
                        border-radius:${s.borderRadius ?? 4}px;box-sizing:border-box"></div>`;
        }

        case 'rounded-rectangle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || '#6366f1');
            return `<div style="width:100%;height:100%;background:${fillColor};
                        border:${s.borderWidth ?? 2}px solid ${s.borderColor || '#4f46e5'};
                        border-radius:16px;box-sizing:border-box"></div>`;
        }

        case 'circle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || '#6366f1');
            return `<div style="width:100%;height:100%;background:${fillColor};
                        border:${s.borderWidth ?? 2}px solid ${s.borderColor || '#4f46e5'};
                        border-radius:50%;box-sizing:border-box"></div>`;
        }

        case 'triangle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 140" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 155,135 5,135" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}" stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'diamond': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 155,80 80,155 5,80" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}" stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'pentagon': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 155,62 127,155 33,155 5,62" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}" stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'hexagon': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 180 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="45,5 135,5 175,80 135,155 45,155 5,80" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}" stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'star': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 98,60 158,60 110,95 128,150 80,118 32,150 50,95 2,60 62,60"
                            fill="${fillColor}" stroke="${s.borderColor || '#4f46e5'}"
                            stroke-width="${s.borderWidth ?? 2}" stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'arrow-right': {
            const s = widget.style || {};
            return `<svg width="100%" height="100%" viewBox="0 0 180 60" preserveAspectRatio="none" style="display:block">
                        <polygon points="0,18 120,18 120,5 175,30 120,55 120,42 0,42" fill="${s.fill || '#374151'}"/>
                    </svg>`;
        }

        case 'arrow-left': {
            const s = widget.style || {};
            return `<svg width="100%" height="100%" viewBox="0 0 180 60" preserveAspectRatio="none" style="display:block">
                        <polygon points="180,18 60,18 60,5 5,30 60,55 60,42 180,42" fill="${s.fill || '#374151'}"/>
                    </svg>`;
        }

        case 'arrow-up': {
            const s = widget.style || {};
            return `<svg width="100%" height="100%" viewBox="0 0 60 180" preserveAspectRatio="none" style="display:block">
                        <polygon points="18,180 18,60 5,60 30,5 55,60 42,60 42,180" fill="${s.fill || '#374151'}"/>
                    </svg>`;
        }

        case 'arrow-down': {
            const s = widget.style || {};
            return `<svg width="100%" height="100%" viewBox="0 0 60 180" preserveAspectRatio="none" style="display:block">
                        <polygon points="18,0 18,120 5,120 30,175 55,120 42,120 42,0" fill="${s.fill || '#374151'}"/>
                    </svg>`;
        }

        case 'double-arrow': {
            const s = widget.style || {};
            return `<svg width="100%" height="100%" viewBox="0 0 200 60" preserveAspectRatio="none" style="display:block">
                        <polygon points="5,30 30,5 30,18 170,18 170,5 195,30 170,55 170,42 30,42 30,55"
                            fill="${s.fill || '#374151'}"/>
                    </svg>`;
        }

        case 'textbox': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || 'transparent');
            return `<div style="width:100%;height:100%;background:${fillColor};
                        border:${s.borderWidth ?? 0}px solid ${s.borderColor || 'transparent'};
                        border-radius:${s.borderRadius ?? 4}px;
                        font-size:${s.fontSize ?? 16}px;color:${s.fontColor || '#1f2937'};
                        font-weight:${s.fontWeight || 'normal'};text-align:${s.textAlign || 'left'};
                        padding:8px;box-sizing:border-box;white-space:pre-wrap;overflow:auto">${widget.text || ''}</div>`;
        }

        case 'line': {
            const s = widget.style || {};
            return `<div style="width:100%;height:100%;display:flex;align-items:center">
                        <div style="width:100%;height:${Math.max(2, s.borderWidth || 4)}px;
                             background:${s.fill || '#374151'}"></div>
                    </div>`;
        }

        case 'arrow': {
            const s = widget.style || {};
            const color = s.fill || '#374151';
            const sw    = Math.max(2, s.borderWidth || 4);
            return `<svg width="100%" height="100%" viewBox="0 0 200 40" preserveAspectRatio="none"
                         style="display:block">
                        <line x1="5" y1="20" x2="180" y2="20"
                              stroke="${color}" stroke-width="${sw}"/>
                        <polygon points="175,10 195,20 175,30" fill="${color}"/>
                    </svg>`;
        }

        case 'kpi': {
            const k = PROJECT_KPI;
            const cards = [
                { label:'Total Tasks', value:k.total,            color:'#4f46e5', bg:'#f5f3ff' },
                { label:'Done',        value:k.done,             color:'#16a34a', bg:'#f0fdf4' },
                { label:'In Progress', value:k.in_progress,      color:'#2563eb', bg:'#eff6ff' },
                { label:'Overdue',     value:k.overdue,          color:'#dc2626', bg:'#fef2f2' },
                { label:'Progress',    value:k.progress_pct+'%', color:'#0891b2', bg:'#ecfeff' },
                { label:'Members',     value:k.members,          color:'#7c3aed', bg:'#faf5ff' },
            ];
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;height:100%;padding:6px">` +
                cards.map(c => `
                    <div style="background:${c.bg};border-radius:6px;padding:8px;text-align:center;
                                border:1px solid ${c.color}22;display:flex;flex-direction:column;
                                align-items:center;justify-content:center;min-height:0">
                        <div style="font-size:1.5em;font-weight:800;color:${c.color};line-height:1">${c.value}</div>
                        <div style="font-size:9px;color:#6b7280;margin-top:3px">${c.label}</div>
                    </div>`).join('') + '</div>';
        }

        case 'chart': {
            const d = CHART_DATA.tasksByStatus;
            const bars = [
                { label:'Todo',        value: d.todo,        color:'#6b7280' },
                { label:'In Progress', value: d.in_progress, color:'#2563eb' },
                { label:'Review',      value: d.review,      color:'#d97706' },
                { label:'Done',        value: d.done,        color:'#16a34a' },
                { label:'Cancelled',   value: d.cancelled,   color:'#dc2626' },
            ];
            const maxV = Math.max(...bars.map(b => b.value), 1);
            return `<div style="height:100%;display:flex;flex-direction:column;padding:8px">
                <div style="font-size:10px;font-weight:600;color:#374151;margin-bottom:6px">Tasks by Status</div>
                <div style="flex:1;display:flex;align-items:flex-end;gap:5px;min-height:0">
                    ${bars.map(b => `
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;height:100%;justify-content:flex-end">
                            <span style="font-size:9px;font-weight:700;color:${b.color}">${b.value}</span>
                            <div style="width:100%;background:${b.color};border-radius:3px 3px 0 0;
                                        height:${Math.max(4,Math.round((b.value/maxV)*80))}%;opacity:.85"></div>
                            <span style="font-size:7px;color:#9ca3af;text-align:center;line-height:1.1">${b.label}</span>
                        </div>`).join('')}
                </div>
            </div>`;
        }

        case 'gantt': {
            const tasks = PROJECT_DATA.tasks || [];
            if (!tasks.length) return '<div style="padding:16px;color:#9ca3af;font-size:12px;text-align:center">No tasks</div>';

            const gs = widget.style || {};
            const showStart  = gs.showStart  !== false;
            const showEnd    = gs.showEnd    !== false;
            const showPct    = gs.showPct    !== false;
            const showStatus = gs.showStatus !== false;
            const viewMode   = gs.viewMode || 'month';

            const dates = tasks.flatMap(t => [t.start_date, t.due_date]).filter(Boolean).map(d => new Date(d));
            const minD = new Date(Math.min(...dates));
            const maxD = new Date(Math.max(...dates));

            const thMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
            function addDays(d, n) { const r = new Date(d); r.setDate(r.getDate()+n); return r; }
            function startOfWeek(d) { const r = new Date(d); return addDays(r, -r.getDay()); }

            let rangeStart, rangeEnd, units = [];
            if (viewMode === 'day') {
                rangeStart = addDays(minD, -1);
                rangeEnd   = addDays(maxD, 1);
                let c = new Date(rangeStart);
                while (c <= rangeEnd) { units.push(new Date(c)); c = addDays(c, 1); }
            } else if (viewMode === 'week') {
                rangeStart = startOfWeek(minD);
                rangeEnd   = addDays(startOfWeek(maxD), 7);
                let c = new Date(rangeStart);
                while (c <= rangeEnd) { units.push(new Date(c)); c = addDays(c, 7); }
            } else {
                rangeStart = new Date(minD.getFullYear(), minD.getMonth(), 1);
                rangeEnd   = new Date(maxD.getFullYear(), maxD.getMonth() + 1, 0);
                let c = new Date(rangeStart);
                while (c <= rangeEnd) { units.push(new Date(c)); c = new Date(c.getFullYear(), c.getMonth()+1, 1); }
            }
            const totalMs = Math.max(rangeEnd - rangeStart, 86400000);

            const statusColor = { todo:'#6b7280', in_progress:'#2563eb', review:'#d97706', done:'#16a34a', cancelled:'#dc2626' };
            const statusLabel = { todo:'รอดำเนินการ', in_progress:'กำลังทำ', review:'รีวิว', done:'เสร็จแล้ว', cancelled:'ยกเลิก' };

            const baseW = 800, baseH = 260;
            const scaleX = (widget.w || baseW) / baseW;
            const scaleY = (widget.h || baseH) / baseH;
            const fontScale = Math.min(scaleX, scaleY);

            const colName   = 110 * scaleX;
            const colStartW = showStart ? 48 * scaleX : 0;
            const colEndW   = showEnd   ? 48 * scaleX : 0;
            const colPctW   = showPct   ? 42 * scaleX : 0;
            const colStatW  = showStatus? 58 * scaleX : 0;
            const labelW = colName + colStartW + colEndW + colPctW + colStatW;

            const rowH = 26 * scaleY, hdrH = 24 * scaleY;
            const fontBase = 10 * fontScale;
            const totalW = (widget.w || baseW);
            const chartW = Math.max(totalW - labelW, 100);
            const svgH = hdrH + tasks.length * rowH;

            let svg = `<svg width="100%" height="100%" viewBox="0 0 ${totalW} ${svgH}"
                            preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"
                            style="font-family:sans-serif;display:block">`;

            // Unit header backgrounds + labels + vertical grid lines
            units.forEach((u, i) => {
                let uStart, uEnd, label;
                if (viewMode === 'day') {
                    uStart = u; uEnd = addDays(u, 1);
                    label = `${u.getDate()}/${u.getMonth()+1}`;
                } else if (viewMode === 'week') {
                    uStart = u; uEnd = addDays(u, 7);
                    label = `${u.getDate()}/${u.getMonth()+1}`;
                } else {
                    uStart = new Date(u.getFullYear(), u.getMonth(), 1);
                    uEnd   = new Date(u.getFullYear(), u.getMonth()+1, 0);
                    label = `${thMonths[u.getMonth()]} ${(u.getFullYear()+543).toString().slice(-2)}`;
                }
                const x0 = labelW + Math.max(0, (uStart - rangeStart)) / totalMs * chartW;
                const x1 = labelW + Math.min(totalMs, (uEnd   - rangeStart)) / totalMs * chartW;
                if (x1 <= labelW) return;
                svg += `<rect x="${x0}" y="0" width="${Math.max(0,x1-x0)}" height="${hdrH}" fill="${i%2 ? '#f9fafb' : '#f3f4f6'}"/>`;
                const minLW = viewMode === 'day' ? 18 : 28;
                if ((x1-x0) >= minLW * fontScale) {
                    svg += `<text x="${(x0+x1)/2}" y="${hdrH*0.68}" font-size="${fontBase*0.8}" fill="#6b7280" text-anchor="middle" font-weight="600">${label}</text>`;
                }
                svg += `<line x1="${x0}" y1="0" x2="${x0}" y2="${svgH}" stroke="#e5e7eb" stroke-width="1"/>`;
            });
            svg += `<line x1="${labelW+chartW}" y1="0" x2="${labelW+chartW}" y2="${svgH}" stroke="#e5e7eb" stroke-width="1"/>`;

            // Header row — task list columns
            svg += `<rect x="0" y="0" width="${labelW}" height="${hdrH}" fill="#f3f4f6"/>`;
            let hx = 0;
            svg += `<text x="6" y="${hdrH*0.68}" font-size="${fontBase*0.9}" fill="#6b7280" font-weight="600">งาน</text>`;
            hx += colName;
            if (showStart)  { svg += `<text x="${hx+4}" y="${hdrH*0.68}" font-size="${fontBase*0.85}" fill="#6b7280" font-weight="600">เริ่ม</text>`;    hx += colStartW; }
            if (showEnd)    { svg += `<text x="${hx+4}" y="${hdrH*0.68}" font-size="${fontBase*0.85}" fill="#6b7280" font-weight="600">สิ้นสุด</text>`;   hx += colEndW;   }
            if (showPct)    { svg += `<text x="${hx+4}" y="${hdrH*0.68}" font-size="${fontBase*0.85}" fill="#6b7280" font-weight="600">%</text>`;          hx += colPctW;   }
            if (showStatus) { svg += `<text x="${hx+4}" y="${hdrH*0.68}" font-size="${fontBase*0.85}" fill="#6b7280" font-weight="600">สถานะ</text>`;     }

            svg += `<line x1="0"       y1="${hdrH}" x2="${totalW}" y2="${hdrH}"  stroke="#d1d5db" stroke-width="1"/>`;
            svg += `<line x1="${labelW}" y1="0"     x2="${labelW}" y2="${svgH}"  stroke="#d1d5db" stroke-width="1.5"/>`;

            tasks.forEach((t, i) => {
                const y = hdrH + i * rowH;
                const color = statusColor[t.status] || '#6366f1';
                const rowBg = i % 2 ? '#f9fafb' : 'white';

                svg += `<rect x="0" y="${y}" width="${totalW}" height="${rowH}" fill="${rowBg}"/>`;
                svg += `<line x1="0" y1="${y}" x2="${totalW}" y2="${y}" stroke="#f3f4f6" stroke-width="1"/>`;

                let cx = 0;
                const maxChars = Math.round(14 / Math.max(fontScale, 0.5));
                svg += `<text x="6" y="${y+rowH*0.65}" font-size="${fontBase}" fill="#374151">${(t.title||'').substring(0, maxChars)}</text>`;
                cx += colName;
                if (showStart)  { svg += `<text x="${cx+4}" y="${y+rowH*0.65}" font-size="${fontBase*0.85}" fill="#6b7280">${formatGanttDate(t.start_date)}</text>`;                              cx += colStartW; }
                if (showEnd)    { svg += `<text x="${cx+4}" y="${y+rowH*0.65}" font-size="${fontBase*0.85}" fill="#6b7280">${formatGanttDate(t.due_date)}</text>`;                                cx += colEndW;   }
                if (showPct)    { svg += `<text x="${cx+4}" y="${y+rowH*0.65}" font-size="${fontBase*0.85}" fill="${color}" font-weight="600">${t.progress_pct ?? 0}%</text>`;                    cx += colPctW;   }
                if (showStatus) { svg += `<text x="${cx+4}" y="${y+rowH*0.65}" font-size="${fontBase*0.8}"  fill="${color}">${statusLabel[t.status] || t.status || ''}</text>`; }

                if (t.start_date && t.due_date) {
                    const bx = labelW + (new Date(t.start_date) - rangeStart) / totalMs * chartW;
                    const bw = Math.max(4, (new Date(t.due_date) - new Date(t.start_date)) / totalMs * chartW);
                    const barH = rowH - 8 * scaleY;
                    svg += `<rect x="${bx}" y="${y+4*scaleY}" width="${bw}" height="${barH}" rx="${3*scaleX}" fill="${color}" opacity=".35"/>`;
                    if (t.progress_pct > 0)
                        svg += `<rect x="${bx}" y="${y+4*scaleY}" width="${bw*t.progress_pct/100}" height="${barH}" rx="${3*scaleX}" fill="${color}"/>`;
                }
            });

            // Today marker
            const today = new Date();
            if (today >= rangeStart && today <= rangeEnd) {
                const tx = labelW + (today - rangeStart) / totalMs * chartW;
                svg += `<line x1="${tx}" y1="0" x2="${tx}" y2="${svgH}" stroke="#dc2626" stroke-width="${Math.max(1.5, 1.5*scaleX)}" stroke-dasharray="4,2"/>`;
                svg += `<rect x="${tx-16}" y="0" width="32" height="${hdrH*0.72}" rx="3" fill="#dc2626"/>`;
                svg += `<text x="${tx}" y="${hdrH*0.52}" font-size="${fontBase*0.75}" fill="white" text-anchor="middle" font-weight="700">วันนี้</text>`;
            }

            svg += `<line x1="0" y1="${svgH}" x2="${totalW}" y2="${svgH}" stroke="#e5e7eb" stroke-width="1"/>`;
            svg += '</svg>';
            return svg;
        }

        case 'milestone': {
            const ms = PROJECT_DATA.milestones || [];
            if (!ms.length) return '<div style="padding:20px;color:#9ca3af;font-size:11px;text-align:center">No milestones</div>';
            return `<div style="height:100%;overflow:auto;padding:6px">
                <div style="font-size:10px;font-weight:700;color:#374151;margin-bottom:8px">🎯 Milestones</div>
                ${ms.map(m => `
                    <div style="display:flex;align-items:center;gap:7px;padding:5px 0;
                                border-bottom:1px solid #f1f5f9;font-size:11px">
                        <span>${m.is_completed ? '✅' : '⭕'}</span>
                        <span style="flex:1;color:#1e293b">${m.name}</span>
                        <span style="color:#94a3b8;font-size:9px;white-space:nowrap">${m.due_date||''}</span>
                    </div>`).join('')}
            </div>`;
        }

        case 'team': {
            const members = PROJECT_DATA.members || [];
            if (!members.length) return '<div style="padding:20px;color:#9ca3af;font-size:11px;text-align:center">No members</div>';
            const colors = ['#4f46e5','#0891b2','#16a34a','#d97706','#dc2626','#7c3aed'];
            return `<div style="height:100%;overflow:auto;padding:6px">
                <div style="font-size:10px;font-weight:700;color:#374151;margin-bottom:8px">👥 Team Members</div>
                ${members.map((m,i) => `
                    <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9">
                        <div style="width:26px;height:26px;border-radius:50%;background:${colors[i%colors.length]};
                                    display:flex;align-items:center;justify-content:center;
                                    color:white;font-size:10px;font-weight:700;flex-shrink:0">
                            ${(m.name||'?')[0].toUpperCase()}
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:11px;font-weight:500;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${m.name}</div>
                            <div style="font-size:9px;color:#94a3b8">${m.role}</div>
                        </div>
                        <span style="font-size:9px;color:#64748b;white-space:nowrap">${m.tasks_count} tasks</span>
                    </div>`).join('')}
            </div>`;
        }

        case 'blocker': {
            const bl = PROJECT_DATA.active_blockers_list || [];
            if (!bl.length) return `<div style="display:flex;flex-direction:column;align-items:center;
                justify-content:center;height:100%;gap:6px">
                <div style="font-size:28px">✅</div>
                <div style="font-size:11px;color:#16a34a;font-weight:600">No active blockers</div>
            </div>`;
            return `<div style="height:100%;overflow:auto;padding:6px">
                <div style="font-size:10px;font-weight:700;color:#dc2626;margin-bottom:8px">🚨 Active Blockers</div>
                ${bl.map(b => `
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:5px;padding:7px;margin-bottom:5px">
                        <div style="font-size:10px;font-weight:600;color:#dc2626">${b.task_title}</div>
                        <div style="font-size:9px;color:#6b7280;margin-top:2px">${b.description||''}</div>
                        <div style="font-size:9px;color:#9ca3af;margin-top:2px">by ${b.reporter}</div>
                    </div>`).join('')}
            </div>`;
        }

        default:
            return `<div style="padding:16px;color:#9ca3af;font-size:11px;text-align:center">${widget.type}</div>`;
    }
}

// ── Init ──────────────────────────────────────────────────────────────────
recalc();
loadSlide();
</script>
</body>
</html>
