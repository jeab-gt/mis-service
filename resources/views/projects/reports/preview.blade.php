<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $report->title }} — Preview</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { background:#0a0a0f; color:#fff; font-family:sans-serif; overflow:hidden; }

#preview-wrapper { width:100vw; height:100vh; display:flex; flex-direction:column; overflow:hidden; }
#slide-display   { flex:1; position:relative; overflow:hidden; }
#controls { flex-shrink:0; height:52px; display:flex; align-items:center; padding:0 20px;
    background:rgba(255,255,255,.04); border-top:1px solid rgba(255,255,255,.08); gap:16px; }

/* Canvas outer frame — absolute centred, scaled without affecting layout */
#slide-frame {
    position:absolute; top:50%; left:50%;
    overflow:hidden; background:#fff;
    box-shadow:0 8px 48px rgba(0,0,0,.7);
}

/* Content inside frame */
#slide-content {
    width:100%; height:100%; overflow:hidden;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    font-size:14px; line-height:1.65; color:#1a1a1a;
    box-sizing:border-box;
}
#slide-content p    { margin:0 0 6px; min-height:1.4em; }
#slide-content h1   { font-size:2em; font-weight:700; margin:16px 0 10px; line-height:1.25; }
#slide-content h2   { font-size:1.5em; font-weight:600; margin:14px 0 8px; }
#slide-content h3   { font-size:1.25em; font-weight:600; margin:12px 0 6px; }
#slide-content h4   { font-size:1.1em; font-weight:600; margin:10px 0 5px; }
#slide-content ul, #slide-content ol { padding-left:28px; margin:6px 0; }
#slide-content li   { margin:2px 0; }
#slide-content table { width:100%; border-collapse:collapse; margin:10px 0; }
#slide-content td, #slide-content th { border:1px solid #d1d5db; padding:8px 12px; text-align:left; }
#slide-content th   { background:#f3f4f6; font-weight:600; }
#slide-content hr   { border:none; border-top:2px solid #e5e7eb; margin:18px 0; }
#slide-content img  { max-width:100%; height:auto; border-radius:4px; display:block; margin:6px 0; }
#slide-content blockquote { border-left:4px solid #6366f1; margin:10px 0; padding:6px 16px; background:#f5f3ff; border-radius:0 6px 6px 0; }
#slide-content a    { color:#6366f1; text-decoration:underline; }

/* Widget in preview */
#slide-content .report-widget { display:block; border:none; border-radius:8px; margin:10px 0; background:#fff; overflow:hidden; }
#slide-content .widget-toolbar { display:none !important; }

.nav-btn { padding:6px 14px; border-radius:6px; border:1px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.08); color:#ddd; cursor:pointer; font-size:13px; transition:background .15s; }
.nav-btn:hover    { background:rgba(255,255,255,.15); }
.nav-btn:disabled { opacity:.3; cursor:default; }
.progress-track { flex:1; height:3px; background:rgba(255,255,255,.1); border-radius:2px; overflow:hidden; }
.progress-fill  { height:100%; background:#6366f1; transition:width .3s; }
</style>
</head>
<body>
<div id="preview-wrapper" x-data="previewApp()" x-init="init()">

    <div id="slide-display" @click="next()">
        <div id="slide-frame"
             :style="`background:${currentSlide?.bg_color||'#fff'};
                      width:${frameW}px;height:${frameH}px;
                      transform:translate(-50%,-50%) scale(${scale});
                      transform-origin:center center;`">
            <div id="slide-content" :style="`padding:${framePad}px;width:100%;height:100%;box-sizing:border-box`"></div>
            <div id="widget-overlay" style="position:absolute;inset:0;pointer-events:none;z-index:2"></div>
        </div>
    </div>

    <div id="controls">
        <a href="{{ route('projects.reports.builder', [$project, $report]) }}"
           style="color:#888;font-size:12px;text-decoration:none">← Edit</a>
        <button class="nav-btn" @click.stop="prev()" :disabled="current===0">← Prev</button>
        <div class="progress-track">
            <div class="progress-fill" :style="`width:${((current+1)/total)*100}%`"></div>
        </div>
        <span style="font-size:12px;color:#888;white-space:nowrap" x-text="`${current+1} / ${total}`"></span>
        <button class="nav-btn" @click.stop="next()" :disabled="current>=total-1">Next →</button>
        <a href="{{ route('projects.reports.export', [$project, $report]) }}" target="_blank"
           class="nav-btn" style="text-decoration:none" @click.stop>Print</a>
    </div>
</div>

@php
$slidesJson = $report->slides->map(function($s) {
    return [
        'id'           => $s->id,
        'bg_color'     => $s->bg_color ?? '#ffffff',
        'html_content' => $s->html_content ?? '',
        'widgets_data' => $s->widgets_data ?? [],
        'elements'     => [],
    ];
})->values();
@endphp
<script>
/* No-ops so widget onclick attrs don't throw in read-only preview */
window.__selectWidget  = function() {};
window.__deleteWidget  = function() {};
window.__refreshWidget = function() {};

const SLIDES       = @json($slidesJson);
const PROJECT_KPI  = @json($kpi);
const CHART_DATA   = @json($chartData);
const PROJECT_DATA = @json($projectData);

function previewApp() {
    return {
        slides: SLIDES,
        current: 0,
        scale: 1,
        frameW: 960,
        frameH: 540,
        framePad: 48,
        _charts: {},

        get total() { return this.slides.length; },
        get currentSlide() { return this.slides[this.current] || null; },

        init() {
            this.recalc();
            window.addEventListener('resize', () => this.recalc());
            this.$nextTick(() => this.loadSlide());
            window.addEventListener('keydown', e => this.onKey(e));
        },

        recalc() {
            const avW = window.innerWidth  - 48;
            const avH = window.innerHeight - 52 - 48;
            this.scale = Math.min(avW / this.frameW, avH / this.frameH);
        },

        prev() { if (this.current > 0) { this.current--; this.$nextTick(() => this.loadSlide()); } },
        next() { if (this.current < this.total - 1) { this.current++; this.$nextTick(() => this.loadSlide()); } },

        onKey(e) {
            if (e.key === 'ArrowRight' || e.key === 'Space' || e.key === 'PageDown') this.next();
            if (e.key === 'ArrowLeft'  || e.key === 'PageUp') this.prev();
            if (e.key === 'Escape') window.close();
        },

        // ── Load slide into DOM ──
        loadSlide() {
            const slide = this.currentSlide;
            if (!slide) return;
            const el = document.getElementById('slide-content');
            const overlay = document.getElementById('widget-overlay');
            if (!el) return;

            // Destroy old charts
            Object.values(this._charts).forEach(c => { try { c.destroy(); } catch(e) {} });
            this._charts = {};

            // Render text/image/table content
            el.innerHTML = slide.html_content || '';

            // Render widgets from widgets_data as absolute overlay
            if (overlay) {
                overlay.innerHTML = '';
                const widgets = Array.isArray(slide.widgets_data) ? slide.widgets_data : [];
                widgets.forEach(w => {
                    const div = document.createElement('div');
                    div.id = 'wc-' + w.id;
                    div.style.cssText = `position:absolute;left:${w.x||0}px;top:${w.y||0}px;` +
                        `width:${w.w||200}px;height:${w.h||150}px;` +
                        `background:white;border-radius:8px;overflow:hidden;`;
                    overlay.appendChild(div);
                });
                this.$nextTick(() => widgets.forEach(w => this.renderWidget(w.id, w.type)));
            }

            // Fallback empty message
            if (!slide.html_content && (!Array.isArray(slide.widgets_data) || !slide.widgets_data.length)) {
                el.innerHTML = '<p style="color:#ccc;text-align:center;padding:60px;font-size:14px">Empty slide</p>';
            }
        },

        renderWidget(id, type) {
            const el = document.getElementById('wc-' + id);
            if (!el) return;
            if (this._charts[id]) { try { this._charts[id].destroy(); } catch(e) {} delete this._charts[id]; }
            switch (type) {
                case 'kpi':       el.innerHTML = this._renderKPI(); break;
                case 'chart':
                    el.innerHTML = `<div style="padding:12px"><canvas id="pv-chart-${id}" width="660" height="260"></canvas></div>`;
                    this.$nextTick(() => this._initChart(id));
                    break;
                case 'gantt':
                    el.style.minHeight = '240px';
                    el.innerHTML = this._renderGantt(700);
                    break;
                case 'milestone': el.innerHTML = this._renderMilestones(); break;
                case 'team':      el.innerHTML = this._renderTeam(); break;
                case 'blocker':   el.innerHTML = this._renderBlockers(); break;
                case 'image': {
                    const widgets = Array.isArray(this.currentSlide?.widgets_data) ? this.currentSlide.widgets_data : [];
                    const w = widgets.find(x => x.id === id);
                    const src = w?.config?.src || '';
                    const fit = w?.config?.objectFit || 'contain';
                    el.innerHTML = src
                        ? `<img src="${src}" style="width:100%;height:100%;object-fit:${fit};display:block;border-radius:4px" draggable="false">`
                        : '';
                    break;
                }
                case 'table': {
                    const widgets = Array.isArray(this.currentSlide?.widgets_data) ? this.currentSlide.widgets_data : [];
                    const w = widgets.find(x => x.id === id);
                    const cfg = w?.config || {};
                    const headers = cfg.headers || ['Header 1', 'Header 2'];
                    const rows    = cfg.data    || [];
                    const fs  = cfg.fontSize    || 13;
                    const bc  = cfg.borderColor || '#d1d5db';
                    const hbg = cfg.headerBg    || '#f3f4f6';
                    const hc  = cfg.headerColor || '#111827';
                    let tbl = `<table style="width:100%;height:100%;border-collapse:collapse;font-size:${fs}px;table-layout:fixed;">`;
                    tbl += '<thead><tr>';
                    headers.forEach(h => {
                        tbl += `<th style="border:1px solid ${bc};padding:6px 8px;background:${hbg};color:${hc};font-weight:600;text-align:left;overflow:hidden;word-break:break-word;">${h}</th>`;
                    });
                    tbl += '</tr></thead><tbody>';
                    rows.forEach((row, ri) => {
                        const bg = ri % 2 === 0 ? '#ffffff' : '#f9fafb';
                        tbl += `<tr style="background:${bg};">`;
                        row.forEach(cell => {
                            tbl += `<td style="border:1px solid ${bc};padding:6px 8px;text-align:left;overflow:hidden;word-break:break-word;">${cell}</td>`;
                        });
                        tbl += '</tr>';
                    });
                    tbl += '</tbody></table>';
                    el.innerHTML = tbl;
                    break;
                }
            }
        },

        _renderKPI() {
            const k = PROJECT_KPI;
            const cards = [
                { label:'Total Tasks', value:k.total,            color:'#4f46e5', bg:'#f5f3ff' },
                { label:'Done',        value:k.done,             color:'#16a34a', bg:'#f0fdf4' },
                { label:'In Progress', value:k.in_progress,      color:'#2563eb', bg:'#eff6ff' },
                { label:'Overdue',     value:k.overdue,          color:'#dc2626', bg:'#fef2f2' },
                { label:'Progress',    value:k.progress_pct+'%', color:'#0891b2', bg:'#ecfeff' },
                { label:'Members',     value:k.members,          color:'#7c3aed', bg:'#faf5ff' },
            ];
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:14px">` +
                cards.map(c =>
                    `<div style="background:${c.bg};border-radius:8px;padding:14px;text-align:center;border:1px solid ${c.color}22">
                        <div style="font-size:2em;font-weight:800;color:${c.color};line-height:1">${c.value}</div>
                        <div style="font-size:11px;color:#6b7280;margin-top:4px">${c.label}</div>
                    </div>`
                ).join('') + '</div>';
        },

        _initChart(id) {
            const canvas = document.getElementById('pv-chart-' + id);
            if (!canvas || typeof Chart === 'undefined') return;
            const d = CHART_DATA.tasksByStatus;
            this._charts[id] = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: ['Todo','In Progress','Review','Done','Cancelled'],
                    datasets: [{ data:[d.todo,d.in_progress,d.review,d.done,d.cancelled],
                        backgroundColor:['#94a3b8','#6366f1','#f59e0b','#22c55e','#ef4444'],
                        borderWidth:1, borderColor:'#fff' }]
                },
                options: { responsive:false, plugins:{ legend:{ position:'right', labels:{ font:{size:11} } } } },
            });
        },

        _renderGantt(W) {
            const tasks = PROJECT_DATA.tasks.filter(t => t.start_date && t.due_date);
            if (!tasks.length) return '<div style="padding:24px;text-align:center;color:#9ca3af;font-size:13px">No tasks with scheduled dates</div>';
            const all = tasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
            const minD = new Date(Math.min(...all)), maxD = new Date(Math.max(...all));
            const totalDays = Math.max(1, (maxD - minD) / 86400000 + 1);
            const lW = 150, cW = Math.max(300, W - lW - 4), rH = 24, hH = 28;
            const H = hH + tasks.length * rH + 2;
            const sc = { done:'#16a34a', in_progress:'#4f46e5', review:'#f59e0b', todo:'#94a3b8', cancelled:'#ef4444' };
            let s = `<svg width="${lW+cW}" height="${H}" xmlns="http://www.w3.org/2000/svg" style="font-family:sans-serif;display:block">`;
            s += `<rect width="${lW+cW}" height="${H}" fill="#f8fafc" rx="2"/>`;
            s += `<rect width="${lW+cW}" height="${hH}" fill="#e2e8f0" rx="2"/>`;
            const cur = new Date(minD.getFullYear(), minD.getMonth(), 1);
            while (cur <= maxD) {
                const x = lW + ((cur - minD) / 86400000) / totalDays * cW;
                s += `<line x1="${x}" y1="${hH}" x2="${x}" y2="${H}" stroke="#e2e8f0" stroke-width="1"/>`;
                s += `<text x="${x+3}" y="20" font-size="9" fill="#64748b">${cur.toLocaleString('default',{month:'short'})} ${cur.getFullYear()}</text>`;
                cur.setMonth(cur.getMonth() + 1);
            }
            s += `<line x1="${lW}" y1="0" x2="${lW}" y2="${H}" stroke="#cbd5e1" stroke-width="1"/>`;
            tasks.forEach((t, i) => {
                const y = hH + i * rH;
                s += `<rect x="0" y="${y}" width="${lW+cW}" height="${rH}" fill="${i%2?'#f8fafc':'#fff'}"/>`;
                const maxCh = Math.floor(lW / 6.5);
                const lbl = t.title.length > maxCh ? t.title.slice(0, maxCh-1)+'…' : t.title;
                s += `<text x="4" y="${y+rH/2+4}" font-size="9" fill="#374151">${lbl}</text>`;
                const bx = lW + ((new Date(t.start_date) - minD) / 86400000) / totalDays * cW;
                const bw = Math.max(4, ((new Date(t.due_date) - new Date(t.start_date)) / 86400000 + 1) / totalDays * cW);
                s += `<rect x="${bx}" y="${y+4}" width="${bw}" height="${rH-8}" rx="3" fill="${sc[t.status]||'#94a3b8'}" opacity="0.85"/>`;
                if (t.progress_pct > 0)
                    s += `<rect x="${bx}" y="${y+4}" width="${bw * t.progress_pct / 100}" height="${rH-8}" rx="3" fill="rgba(255,255,255,.3)"/>`;
            });
            const today = new Date();
            if (today >= minD && today <= maxD) {
                const tx = lW + ((today - minD) / 86400000) / totalDays * cW;
                s += `<line x1="${tx}" y1="${hH}" x2="${tx}" y2="${H}" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="4,2"/>`;
                s += `<text x="${tx+2}" y="${hH-2}" font-size="8" fill="#ef4444">Today</text>`;
            }
            return s + '</svg>';
        },

        _renderMilestones() {
            const ms = PROJECT_DATA.milestones;
            if (!ms.length) return '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px">No milestones</div>';
            return '<div>' + ms.map(m =>
                `<div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid #f1f5f9">
                    <span style="font-size:16px">${m.is_completed?'✅':'⭕'}</span>
                    <span style="flex:1;font-size:13px;color:#374151">${m.name}</span>
                    <span style="font-size:12px;color:#9ca3af;flex-shrink:0">${m.due_date||'—'}</span>
                </div>`
            ).join('') + '</div>';
        },

        _renderTeam() {
            const m = PROJECT_DATA.members;
            if (!m.length) return '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px">No members</div>';
            return `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;padding:14px">` +
                m.map(mb =>
                    `<div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
                        <div style="width:32px;height:32px;border-radius:50%;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">${mb.name.charAt(0).toUpperCase()}</div>
                        <div style="min-width:0">
                            <div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${mb.name}</div>
                            <div style="font-size:10px;color:#9ca3af;text-transform:capitalize">${mb.role}</div>
                        </div>
                    </div>`
                ).join('') + '</div>';
        },

        _renderBlockers() {
            const bl = PROJECT_DATA.active_blockers_list;
            if (!bl.length) return '<div style="padding:20px;text-align:center;color:#16a34a;font-size:13px;background:#f0fdf4;border-radius:8px">✅ No active blockers</div>';
            return '<div style="background:#fef2f2;border-radius:8px;overflow:hidden">' + bl.map(b =>
                `<div style="display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid #fee2e2">
                    <span style="flex-shrink:0;font-size:16px">🚨</span>
                    <div>
                        <div style="font-size:12px;font-weight:600;color:#dc2626">${b.task_title}</div>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px">${b.description}</div>
                    </div>
                </div>`
            ).join('') + '</div>';
        },

        // ── Legacy element rendering (backward compat) ──
        _renderElements(slide) {
            const elMap = slide.elements.reduce((acc, e) => { acc[e.type] = acc[e.type] || []; acc[e.type].push(e); return acc; }, {});
            const els = slide.elements.sort((a,b) => a.z_index - b.z_index);
            const kpiMap = {
                total:PROJECT_KPI.total, done:PROJECT_KPI.done, in_progress:PROJECT_KPI.in_progress,
                overdue:PROJECT_KPI.overdue, progress_pct:PROJECT_KPI.progress_pct+'%', members:PROJECT_KPI.members,
            };
            let html = `<div style="position:relative;width:${this.frameW}px;height:${this.frameH}px;overflow:hidden">`;
            els.forEach(el => {
                const style = `position:absolute;left:${el.x}px;top:${el.y}px;width:${el.w}px;height:${el.h}px;z-index:${el.z_index};box-sizing:border-box;`;
                const p = el.props || {};
                switch (el.type) {
                    case 'text':
                        html += `<div style="${style}font-size:${p.font_size||20}px;font-weight:${p.font_weight||'normal'};color:${p.color||'#1a1a1a'};text-align:${p.align||'left'};background:${p.bg_color||'transparent'};padding:8px;overflow:hidden">${p.content||''}</div>`;
                        break;
                    case 'kpi':
                        html += `<div style="${style}background:${p.bg||'#f5f3ff'};border:2px solid ${p.accent||'#4f46e5'};border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:12px">
                            <div style="font-size:2em;font-weight:800;color:${p.accent||'#4f46e5'}">${(p.prefix||'')}${kpiMap[p.data_source]||'—'}${(p.suffix||'')}</div>
                            <div style="font-size:0.85em;color:#666;margin-top:4px">${p.label||''}</div>
                        </div>`;
                        break;
                    case 'image':
                        html += `<div style="${style}overflow:hidden"><img src="${p.url||''}" style="width:100%;height:100%;object-fit:${p.fit||'cover'}"></div>`;
                        break;
                    case 'shape':
                        html += `<div style="${style}background:${p.fill||'#4f46e5'};opacity:${p.opacity||1};border-radius:${p.border_radius||0}px;border:${p.border_width||0}px solid ${p.border_color||'#000'}"></div>`;
                        break;
                    case 'divider':
                        html += `<div style="${style}display:flex;align-items:center"><div style="width:100%;border-top:${p.thickness||2}px solid ${p.color||'#e5e7eb'}"></div></div>`;
                        break;
                    case 'chart':
                        html += `<div style="${style}background:rgba(255,255,255,.9);border-radius:8px;padding:8px;display:flex;flex-direction:column">
                            <div style="font-size:12px;font-weight:600;color:#333;margin-bottom:4px">${p.title||'Chart'}</div>
                            <canvas id="leg-chart-${el.id}" style="flex:1;min-height:0"></canvas>
                        </div>`;
                        break;
                }
            });
            return html + '</div>';
        },

        _renderLegacyCharts(slide) {
            slide.elements.filter(e => e.type === 'chart').forEach(el => {
                const canvas = document.getElementById('leg-chart-' + el.id);
                if (!canvas) return;
                const p = el.props || {};
                const ds = p.data_source ?? 'status';
                let labels, values, colors;
                if (ds === 'status') {
                    labels = ['Todo','In Progress','Review','Done','Cancelled'];
                    values = [CHART_DATA.tasksByStatus.todo,CHART_DATA.tasksByStatus.in_progress,CHART_DATA.tasksByStatus.review,CHART_DATA.tasksByStatus.done,CHART_DATA.tasksByStatus.cancelled];
                    colors = ['#94a3b8','#6366f1','#f59e0b','#22c55e','#ef4444'];
                } else {
                    labels = CHART_DATA.tasksByAssignee.map(a => a.name);
                    values = CHART_DATA.tasksByAssignee.map(a => a.count);
                    colors = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#4f46e5'];
                }
                this._charts[el.id] = new Chart(canvas, {
                    type: p.chart_type ?? 'doughnut',
                    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth:1, borderColor:'#fff' }] },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'right', labels:{ font:{size:10} } } } },
                });
            });
        },
    };
}
</script>
</body>
</html>
