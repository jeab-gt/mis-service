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

const FRAME_W = 960, FRAME_H = 540;
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
    (slide.widgets_data || []).forEach(w => {
        const el = document.createElement('div');
        el.style.cssText = `position:absolute;left:${w.x||0}px;top:${w.y||0}px;`
            + `width:${w.w||200}px;height:${w.h||150}px;`
            + `background:white;border-radius:8px;overflow:hidden;`
            + `border:1px solid #e5e7eb;box-sizing:border-box;`;
        el.innerHTML = renderWidgetContent(w);
        overlay.appendChild(el);
    });
}

// ── Widget content (same logic as builder) ─────────────────────────────────
function renderWidgetContent(widget) {
    switch (widget.type) {

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
            const tasks = (PROJECT_DATA.tasks || []).filter(t => t.start_date && t.due_date);
            if (!tasks.length) return '<div style="padding:20px;color:#9ca3af;font-size:11px;text-align:center">No tasks with dates</div>';
            const dates = tasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
            const minD = new Date(Math.min(...dates));
            const maxD = new Date(Math.max(...dates));
            const totalMs = Math.max(maxD - minD, 86400000);
            const lW = 130, rowH = 26, hdrH = 22, chartW = 700 - lW;
            const svgH = hdrH + tasks.length * rowH;
            const sc = { todo:'#6b7280',in_progress:'#2563eb',review:'#d97706',done:'#16a34a',cancelled:'#dc2626' };
            let s = `<svg width="100%" height="${svgH}" viewBox="0 0 700 ${svgH}"
                         xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"
                         style="font-family:sans-serif;display:block">`;
            s += `<rect width="700" height="${svgH}" fill="#f8fafc" rx="2"/>`;
            s += `<rect width="700" height="${hdrH}" fill="#e2e8f0"/>`;
            const cur = new Date(minD.getFullYear(), minD.getMonth(), 1);
            while (cur <= maxD) {
                const x = lW + (cur - minD) / totalMs * chartW;
                s += `<line x1="${x}" y1="${hdrH}" x2="${x}" y2="${svgH}" stroke="#e2e8f0" stroke-width="1"/>`;
                s += `<text x="${x+3}" y="16" font-size="8" fill="#64748b">${cur.toLocaleString('default',{month:'short'})} ${cur.getFullYear()}</text>`;
                cur.setMonth(cur.getMonth() + 1);
            }
            s += `<line x1="${lW}" y1="0" x2="${lW}" y2="${svgH}" stroke="#cbd5e1" stroke-width="1"/>`;
            tasks.forEach((t, i) => {
                const y = hdrH + i * rowH;
                const color = sc[t.status] || '#6366f1';
                s += `<rect x="0" y="${y}" width="${lW}" height="${rowH}" fill="${i%2?'#f9fafb':'white'}"/>`;
                const lbl = t.title.length > 18 ? t.title.slice(0,17)+'…' : t.title;
                s += `<text x="5" y="${y+rowH/2+3}" font-size="8" fill="#374151">${lbl}</text>`;
                const bx = lW + (new Date(t.start_date) - minD) / totalMs * chartW;
                const bw = Math.max(4, (new Date(t.due_date) - new Date(t.start_date)) / totalMs * chartW);
                s += `<rect x="${bx}" y="${y+4}" width="${bw}" height="${rowH-8}" rx="3" fill="${color}" opacity=".7"/>`;
                if (t.progress_pct > 0)
                    s += `<rect x="${bx}" y="${y+4}" width="${bw*t.progress_pct/100}" height="${rowH-8}" rx="3" fill="${color}"/>`;
            });
            s += '</svg>';
            return s;
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
