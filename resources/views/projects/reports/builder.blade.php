<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $report->title }} — Builder</title>
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
<link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f172a; color: #f1f5f9; }

/* ── Top bar ── */
#topbar {
    height: 48px; background: #1e293b; display: flex; align-items: center;
    padding: 0 12px; gap: 8px; flex-shrink: 0; z-index: 200;
    border-bottom: 1px solid #334155; position: relative;
}
#report-title {
    background: #0f172a; border: 1px solid #334155; color: #f1f5f9;
    padding: 4px 10px; border-radius: 6px; font-size: 13px; flex: 1; max-width: 420px;
    outline: none;
}
#report-title:focus { border-color: #6366f1; }
.top-btn {
    padding: 5px 14px; border-radius: 6px; border: none; cursor: pointer;
    font-size: 12px; font-weight: 500; white-space: nowrap;
}
.top-btn-primary { background: #4f46e5; color: white; }
.top-btn-primary:hover { background: #4338ca; }
.top-btn-cyan { background: #0891b2; color: white; text-decoration: none; display: flex; align-items: center; }
.top-btn-cyan:hover { background: #0e7490; }
#save-status { color: #64748b; font-size: 11px; min-width: 60px; }

/* ── Layout ── */
#main-area { display: flex; flex: 1; overflow: hidden; height: calc(100vh - 48px); }

/* ── Slide list ── */
#slide-list {
    width: 148px; background: #0f172a; overflow-y: auto; flex-shrink: 0;
    padding: 8px; display: flex; flex-direction: column; gap: 6px;
    border-right: 1px solid #1e293b;
}
.slide-thumb {
    background: #1e293b; border-radius: 6px; padding: 6px; cursor: pointer;
    color: #94a3b8; font-size: 10px; text-align: center; position: relative;
    border: 2px solid transparent; user-select: none; transition: border-color .15s;
}
.slide-thumb:hover { border-color: #475569; }
.slide-thumb.active { border-color: #6366f1; background: #1e1b4b; color: #a5b4fc; }
.slide-thumb-preview {
    width: 100%; height: 58px; background: white; border-radius: 3px;
    margin-bottom: 4px; overflow: hidden; font-size: 5px; color: #374151;
    display: flex; align-items: center; justify-content: center;
}
.slide-del-btn {
    position: absolute; top: 2px; right: 2px; background: #dc2626; border: none;
    color: white; border-radius: 3px; width: 14px; height: 14px; cursor: pointer;
    font-size: 8px; line-height: 1; display: flex; align-items: center; justify-content: center;
}
#add-slide-btn {
    background: transparent; color: #475569; border: 1px dashed #334155;
    padding: 7px; border-radius: 6px; cursor: pointer; font-size: 11px;
    transition: border-color .15s, color .15s; margin-top: 2px;
}
#add-slide-btn:hover { border-color: #6366f1; color: #818cf8; }

/* ── Canvas area ── */
#canvas-wrapper {
    flex: 1; overflow: auto; background: #1e293b; display: flex;
    justify-content: center; padding: 24px;
}
#page-container {
    position: relative; width: 1280px; height: 720px; min-height: unset; background: white;
    box-shadow: 0 8px 40px rgba(0,0,0,.6); flex-shrink: 0; overflow: hidden;
}
#widget-overlay {
    position: absolute; inset: 0; pointer-events: none; z-index: 10;
}

/* ── CKEditor overrides ── */
.ck.ck-editor { display: flex; flex-direction: column; }
.ck.ck-editor__top { position: sticky; top: 0; z-index: 100; }
.ck.ck-editor__editable {
    height: 672px; min-height: unset; padding: 40px 56px !important; border: none !important;
    box-shadow: none !important; outline: none !important; overflow: hidden;
    font-size: 14px; line-height: 1.7; color: #1a1a1a; background: white;
}
.ck.ck-editor__editable:focus { outline: none !important; box-shadow: none !important; }
.ck-powered-by { display: none !important; }

/* ── Insert panel ── */
#insert-panel {
    width: 188px; background: #0f172a; overflow-y: auto; flex-shrink: 0;
    padding: 10px 8px; border-left: 1px solid #1e293b;
}
.panel-label {
    color: #475569; font-size: 9px; font-weight: 700; letter-spacing: .8px;
    text-transform: uppercase; margin: 10px 0 5px; padding: 0 2px;
}
.panel-label:first-child { margin-top: 0; }
.ins-btn {
    display: flex; align-items: center; gap: 7px; width: 100%;
    background: #1e293b; color: #cbd5e1; border: none; padding: 8px 10px;
    border-radius: 6px; cursor: pointer; font-size: 12px; text-align: left;
    margin-bottom: 3px; transition: background .12s, color .12s;
}
.ins-btn:hover { background: #334155; color: #f1f5f9; }
.ins-btn i { font-size: 15px; color: #6366f1; flex-shrink: 0; }

/* ── Shape icon grid ── */
.shape-icon-btn {
    aspect-ratio: 1; background: #1e293b; color: #94a3b8; border: none;
    border-radius: 6px; cursor: pointer; display: flex; align-items: center;
    justify-content: center; transition: background .12s, color .12s;
    padding: 0;
}
.shape-icon-btn:hover { background: #4f46e5; color: white; }

/* ── Context menu ── */
.ctx-item {
    display:block; width:100%; text-align:left; background:none; border:none;
    color:#d1d5db; padding:7px 10px; border-radius:4px; cursor:pointer; font-size:12px;
}
.ctx-item:hover { background:#374151; }

/* ── Widget element ── */
.rb-widget {
    position: absolute; border-radius: 8px; background: white;
    overflow: visible; box-sizing: border-box;
    transition: box-shadow .1s;
}
.rb-widget:hover .widget-handle { opacity: 1; }
.widget-toolbar {
    position: absolute; top: -34px; left: 0;
    background: #1e293b; color: white; border-radius: 6px;
    padding: 3px 8px; display: flex; align-items: center;
    gap: 8px; z-index: 30; font-size: 11px; white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,.5); pointer-events: all;
}
.widget-del-btn {
    background: #dc2626; border: none; color: white; padding: 2px 7px;
    border-radius: 4px; cursor: pointer; font-size: 11px;
}
.widget-content {
    width: 100%; height: 100%; overflow: hidden;
    padding: 8px; box-sizing: border-box; pointer-events: none;
}
.widget-handle {
    position: absolute; bottom: -6px; right: -6px;
    width: 13px; height: 13px; background: #2563eb;
    border-radius: 2px; cursor: se-resize; z-index: 20;
    pointer-events: all; opacity: 0; transition: opacity .15s;
}
.widget-el {
    will-change: left, top, width, height;
    user-select: none;
    -webkit-user-select: none;
}
</style>
</head>
<body>

{{-- Top bar --}}
<div id="topbar">
    <a href="{{ route('projects.reports.index', $project) }}"
       style="color:#64748b;text-decoration:none;font-size:12px;white-space:nowrap">
        ← Reports
    </a>
    <input id="report-title" value="{{ $report->title }}" placeholder="Report title…">
    <div style="flex:1"></div>
    <span id="save-status"></span>
    <button class="top-btn top-btn-primary" onclick="saveReport()">💾 Save</button>
    <a href="{{ route('projects.reports.preview', [$project, $report]) }}" target="_blank"
       class="top-btn top-btn-cyan">👁 Preview</a>
</div>

{{-- Main area --}}
<div id="main-area">

    {{-- Slide list --}}
    <div id="slide-list">
        <button id="add-slide-btn" onclick="addSlide()">+ Add Page</button>
    </div>

    {{-- Canvas --}}
    <div id="canvas-wrapper">
        <div id="page-container">
            <div id="editor-mount"></div>
            <div id="widget-overlay"></div>
        </div>
    </div>

    {{-- Insert panel --}}
    <div id="insert-panel">
        <p class="panel-label">Content</p>
        <button class="ins-btn" onclick="insertImage()"><i class="ti ti-photo"></i> Image</button>
        <button class="ins-btn" onclick="insertTextBox()"><i class="ti ti-text-size"></i> Text Box</button>
        <button class="ins-btn" onclick="insertHR()"><i class="ti ti-minus"></i> Divider</button>

        <p class="panel-label">Shapes</p>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-bottom:12px">
            <button class="shape-icon-btn" title="Rectangle" onclick="insertShape('rectangle')">
                <svg width="18" height="14" viewBox="0 0 18 14"><rect x="1" y="1" width="16" height="12" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Rounded Rect" onclick="insertShape('rounded-rectangle')">
                <svg width="18" height="14" viewBox="0 0 18 14"><rect x="1" y="1" width="16" height="12" fill="currentColor" rx="4"/></svg>
            </button>
            <button class="shape-icon-btn" title="Circle" onclick="insertShape('circle')">
                <svg width="14" height="14" viewBox="0 0 14 14"><circle cx="7" cy="7" r="6" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Triangle" onclick="insertShape('triangle')">
                <svg width="16" height="14" viewBox="0 0 16 14"><polygon points="8,1 15,13 1,13" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Diamond" onclick="insertShape('diamond')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="7,1 13,7 7,13 1,7" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Pentagon" onclick="insertShape('pentagon')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="7,1 13,5 11,12 3,12 1,5" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Hexagon" onclick="insertShape('hexagon')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="7,1 12.5,4 12.5,10 7,13 1.5,10 1.5,4" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Star" onclick="insertShape('star')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="7,1 8.5,5.5 13,5.5 9.5,8.5 10.5,13 7,10.5 3.5,13 4.5,8.5 1,5.5 5.5,5.5" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Line" onclick="insertShape('line')">
                <svg width="18" height="14" viewBox="0 0 18 14"><line x1="1" y1="7" x2="17" y2="7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            </button>
            <button class="shape-icon-btn" title="Arrow Right" onclick="insertShape('arrow-right')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="1,5 9,5 9,2 13,7 9,12 9,9 1,9" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Arrow Left" onclick="insertShape('arrow-left')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="13,5 5,5 5,2 1,7 5,12 5,9 13,9" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Arrow Up" onclick="insertShape('arrow-up')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="5,13 5,5 2,5 7,1 12,5 9,5 9,13" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Arrow Down" onclick="insertShape('arrow-down')">
                <svg width="14" height="14" viewBox="0 0 14 14"><polygon points="5,1 5,9 2,9 7,13 12,9 9,9 9,1" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" title="Double Arrow" onclick="insertShape('double-arrow')">
                <svg width="18" height="14" viewBox="0 0 18 14"><polygon points="1,7 5,3 5,5.5 13,5.5 13,3 17,7 13,11 13,8.5 5,8.5 5,11" fill="currentColor"/></svg>
            </button>
            <button class="shape-icon-btn" onclick="insertConnector()" title="Connector">
                <svg viewBox="0 0 24 24" width="18" height="18">
                    <polyline points="4,8 4,18 20,18" fill="none" stroke="currentColor" stroke-width="2"/>
                    <polygon points="17,14 22,18 17,22" fill="currentColor"/>
                </svg>
            </button>
        </div>

        <p class="panel-label">Project Data</p>
        <button class="ins-btn" onclick="insertWidget('kpi')"><i class="ti ti-chart-bar"></i> KPI Summary</button>
        <button class="ins-btn" onclick="insertWidget('chart')"><i class="ti ti-chart-donut"></i> Task Chart</button>
        <button class="ins-btn" onclick="insertWidget('gantt')"><i class="ti ti-calendar-stats"></i> Gantt</button>
        <button class="ins-btn" onclick="insertWidget('milestone')"><i class="ti ti-flag"></i> Milestones</button>
        <button class="ins-btn" onclick="insertWidget('team')"><i class="ti ti-users"></i> Team Members</button>
        <button class="ins-btn" onclick="insertWidget('blocker')"><i class="ti ti-alert-triangle"></i> Blockers</button>
    </div>

</div>

{{-- Settings Panel --}}
<div id="settings-panel" style="position:fixed;top:60px;right:212px;width:220px;
     background:#1f2937;border-radius:8px;padding:14px;display:none;
     box-shadow:0 8px 24px rgba(0,0,0,.4);z-index:200;border:1px solid #374151">
</div>

{{-- Context Menu --}}
<div id="context-menu" style="position:fixed;display:none;background:#1f2937;
     border-radius:6px;padding:4px;box-shadow:0 8px 24px rgba(0,0,0,.4);z-index:300;
     min-width:170px">
    <button class="ctx-item" onclick="bringToFront()">⬆ Bring to Front</button>
    <button class="ctx-item" onclick="bringForward()">↑ Bring Forward</button>
    <button class="ctx-item" onclick="sendBackward()">↓ Send Backward</button>
    <button class="ctx-item" onclick="sendToBack()">⬇ Send to Back</button>
    <div style="height:1px;background:#374151;margin:4px 0"></div>
    <button class="ctx-item" onclick="deleteWidget(selectedWidgetId);hideContextMenu()" style="color:#f87171">✕ Delete</button>
</div>

{{-- Data --}}
@php
$reportJson = [
    'id'     => $report->id,
    'title'  => $report->title,
    'slides' => $report->slides->sortBy('slide_order')->map(fn($s) => [
        'id'           => $s->id,
        'slide_order'  => $s->slide_order,
        'bg_color'     => $s->bg_color ?? '#ffffff',
        'notes'        => $s->notes ?? '',
        'html_content' => $s->html_content ?? '',
        'widgets_data' => $s->widgets_data ?? [],
    ])->values(),
];
@endphp
<script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script>
<script>
const CSRF        = document.querySelector('meta[name=csrf-token]').content;
const SAVE_URL    = "{{ route('projects.reports.save', [$project, $report]) }}";
const UPLOAD_URL  = "{{ route('projects.reports.upload-image', [$project, $report]) }}";
const PROJECT_KPI  = @json($kpi);
const CHART_DATA   = @json($chartData);
const PROJECT_DATA = @json($projectData);
const REPORT_DATA  = @json($reportJson);

// ── State ──────────────────────────────────────────────────────────────────
let editor           = null;
let slides           = [];
let currentSlideIndex = 0;
let widgets          = [];
let selectedWidgetId = null;

// ── CKEditor init ──────────────────────────────────────────────────────────
const {
    ClassicEditor, Essentials, Bold, Italic, Underline, Strikethrough,
    Paragraph, Heading, Alignment, FontFamily, FontSize,
    FontColor, FontBackgroundColor, List, Table, TableToolbar,
    TableProperties, TableCellProperties, TableColumnResize,
    Image: CKImage, ImageResize, ImageStyle, ImageToolbar, Link,
    HorizontalLine, Indent, IndentBlock, BlockQuote, Undo,
    GeneralHtmlSupport
} = CKEDITOR;

async function initEditor(htmlContent = '') {
    if (editor) { try { await editor.destroy(); } catch(e) {} editor = null; }

    editor = await ClassicEditor.create(document.getElementById('editor-mount'), {
        licenseKey: 'GPL',
        plugins: [
            Essentials, Bold, Italic, Underline, Strikethrough,
            Paragraph, Heading, Alignment, FontFamily, FontSize,
            FontColor, FontBackgroundColor, List, Table, TableToolbar,
            TableProperties, TableCellProperties, TableColumnResize,
            CKImage, ImageResize, ImageStyle, ImageToolbar, Link,
            HorizontalLine, Indent, IndentBlock, BlockQuote, Undo,
            GeneralHtmlSupport,
        ],
        toolbar: {
            items: [
                'undo', 'redo', '|', 'heading', '|',
                'fontFamily', 'fontSize', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'fontColor', 'fontBackgroundColor', '|',
                'alignment', '|',
                'bulletedList', 'numberedList', 'outdent', 'indent', '|',
                'link', 'insertTable', 'horizontalLine', 'blockQuote',
            ],
            shouldNotGroupWhenFull: false,
        },
        table: {
            contentToolbar: ['tableColumn','tableRow','mergeTableCells',
                             'tableProperties','tableCellProperties'],
        },
        image: {
            toolbar: ['imageStyle:inline','imageStyle:block','|',
                      'imageTextAlternative','resizeImage'],
        },
        fontFamily: {
            options: [
                'default',
                'Sarabun, sans-serif',
                'Arial, sans-serif',
                'Helvetica, sans-serif',
                'Times New Roman, serif',
                'Courier New, monospace',
                'Tahoma, sans-serif',
                'Verdana, sans-serif',
            ],
            supportAllValues: true,
        },
        fontSize: {
            options: [8,9,10,11,12,14,16,18,20,24,28,32,36,48],
            supportAllValues: true,
        },
        htmlSupport: {
            allow: [{ name: /.*/, attributes: true, classes: true, styles: true }],
        },
        initialData: htmlContent,
    });
}

// ── Slide management ───────────────────────────────────────────────────────
function flushCurrentSlide() {
    if (!slides[currentSlideIndex]) return;
    slides[currentSlideIndex].html_content = editor ? editor.getData() : '';
    slides[currentSlideIndex].widgets_data = widgets.map(w => ({ ...w }));
}

async function switchSlide(index) {
    flushCurrentSlide();
    currentSlideIndex = index;
    const slide = slides[index];
    await editor.setData(slide.html_content || '');
    widgets = (slide.widgets_data || []).map(w => ({ ...w }));
    selectedWidgetId = null;
    renderAllWidgets();
    renderSlideList();
}

function addSlide() {
    flushCurrentSlide();
    slides.push({
        id:          'new_' + Date.now(),
        slide_order: slides.length,
        bg_color:    '#ffffff',
        notes:       '',
        html_content:'',
        widgets_data:[],
    });
    switchSlide(slides.length - 1);
}

function deleteSlide(index) {
    if (slides.length <= 1) return;
    if (!confirm('Delete this page?')) return;
    flushCurrentSlide();
    slides.splice(index, 1);
    const newIndex = Math.min(index, slides.length - 1);
    currentSlideIndex = -1; // force reload
    switchSlide(newIndex);
}

function renderSlideList() {
    const list = document.getElementById('slide-list');
    list.querySelectorAll('.slide-thumb').forEach(el => el.remove());
    const addBtn = document.getElementById('add-slide-btn');

    slides.forEach((s, i) => {
        const div = document.createElement('div');
        div.className = 'slide-thumb' + (i === currentSlideIndex ? ' active' : '');
        div.innerHTML = `
            <div class="slide-thumb-preview">
                <span style="color:#9ca3af;font-size:8px">Page ${i + 1}</span>
            </div>
            <span>Page ${i + 1}</span>
            ${slides.length > 1
                ? `<button class="slide-del-btn" title="Delete">✕</button>`
                : ''}
        `;
        div.addEventListener('click', () => switchSlide(i));
        if (slides.length > 1) {
            div.querySelector('.slide-del-btn').addEventListener('click', e => {
                e.stopPropagation(); deleteSlide(i);
            });
        }
        list.insertBefore(div, addBtn);
    });
}

// ── Widget manager ─────────────────────────────────────────────────────────
const WIDGET_DEFAULTS = {
    kpi:               { w: 560, h: 180 },
    chart:             { w: 460, h: 300 },
    gantt:             { w: 800, h: 260 },
    milestone:         { w: 380, h: 240 },
    team:              { w: 420, h: 260 },
    blocker:           { w: 400, h: 220 },
    image:             { w: 400, h: 300 },
    textbox:           { w: 240, h: 80  },
    rectangle:         { w: 200, h: 120 },
    'rounded-rectangle': { w: 200, h: 120 },
    circle:            { w: 150, h: 150 },
    triangle:          { w: 160, h: 140 },
    diamond:           { w: 160, h: 160 },
    pentagon:          { w: 160, h: 160 },
    hexagon:           { w: 180, h: 160 },
    star:              { w: 160, h: 160 },
    line:              { w: 200, h: 4   },
    arrow:             { w: 200, h: 40  },
    'arrow-right':     { w: 180, h: 60  },
    'arrow-left':      { w: 180, h: 60  },
    'arrow-up':        { w: 60,  h: 180 },
    'arrow-down':      { w: 60,  h: 180 },
    'double-arrow':    { w: 200, h: 60  },
};

function insertWidget(type) {
    const d = WIDGET_DEFAULTS[type] || { w: 320, h: 200 };
    const widget = {
        id: 'w_' + Date.now(),
        type,
        x: 40, y: 40,
        w: d.w, h: d.h,
        rotation: 0,
        style: type === 'gantt'
            ? { shadow:false, borderColor:'#e5e7eb', borderRadius:8,
                showStart:true, showEnd:true, showPct:true, showStatus:true, viewMode:'month' }
            : { shadow:false, borderColor:'#e5e7eb', borderRadius:8 },
    };
    widgets.push(widget);
    renderAllWidgets();
    selectWidget(widget.id);
}

function insertShape(type) {
    const d = WIDGET_DEFAULTS[type] || { w: 200, h: 120 };
    const isLine      = type === 'line';
    const isArrowLike = type === 'arrow' || type.startsWith('arrow-') || type === 'double-arrow';
    const widget = {
        id: 'w_' + Date.now(),
        type,
        x: 40, y: 40,
        w: d.w, h: d.h,
        rotation: 0,
        style: {
            fill:            (isLine || isArrowLike) ? '#374151' : '#6366f1',
            fillTransparent: false,
            borderColor:     '#4f46e5',
            borderWidth:     (isLine || isArrowLike) ? 0 : 2,
            borderRadius:    type === 'circle' ? 999 : 4,
            shadow:          false,
        },
    };
    widgets.push(widget);
    renderAllWidgets();
    selectWidget(widget.id);
}

function insertConnector() {
    const widget = {
        id: 'w_' + Date.now(),
        type: 'connector',
        startX: 80,  startY: 120,
        endX:   300, endY:   240,
        startAnchor: null,
        endAnchor:   null,
        midRatio: 0.5,
        x: 80, y: 120, w: 220, h: 120,
        rotation: 0,
        style: {
            color: '#374151',
            strokeWidth: 2,
            arrowHead: 'end',
            lineStyle: 'solid',
            lineType: 'elbow',
            lineJump: false,
            shadow: false,
        },
    };
    widgets.push(widget);
    renderAllWidgets();
    selectWidget(widget.id);
}

function insertImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/gif,image/webp';
    input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;
        try {
            const compressedFile = await compressImage(file, 1280, 0.82);
            const url = await uploadImageFile(compressedFile);
            insertImageWidget(url, compressedFile);
        } catch (e) {
            console.error('Image processing error:', e);
            alert('ไม่สามารถประมวลผลรูปได้: ' + e.message);
        }
    };
    input.click();
}

function compressImage(file, maxWidth = 1280, quality = 0.82) {
    return new Promise((resolve, reject) => {
        const img = new window.Image();
        const url = URL.createObjectURL(file);

        img.onload = () => {
            URL.revokeObjectURL(url);

            let { width, height } = img;
            if (width > maxWidth) {
                height = Math.round(height * (maxWidth / width));
                width = maxWidth;
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);

            const outputType = file.type === 'image/gif' ? 'image/png' : 'image/jpeg';

            canvas.toBlob((blob) => {
                if (!blob) return reject(new Error('Canvas compress failed'));
                const compressedFile = new File(
                    [blob],
                    file.name.replace(/\.\w+$/, outputType === 'image/png' ? '.png' : '.jpg'),
                    { type: outputType }
                );
                resolve(compressedFile);
            }, outputType, quality);
        };

        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('ไม่สามารถโหลดรูปได้'));
        };

        img.src = url;
    });
}

async function uploadImageFile(file) {
    const form = new FormData();
    form.append('upload', file);

    const res = await fetch(UPLOAD_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: form,
    });

    if (!res.ok) {
        const errJson = await res.json().catch(() => ({}));
        throw new Error(errJson.error?.message || `Server error ${res.status}`);
    }

    const json = await res.json();
    const url  = json.urls?.default || json.url;
    if (!url) throw new Error('ไม่ได้รับ URL จาก server');

    return url;
}

function insertImageWidget(url, file) {
    const img = new window.Image();
    img.onload = () => {
        const maxW = 400;
        let w = img.naturalWidth;
        let h = img.naturalHeight;
        if (w > maxW) {
            h = Math.round(h * (maxW / w));
            w = maxW;
        }
        const widget = {
            id: 'w_' + Date.now(),
            type: 'image',
            x: 40, y: 40,
            w: w, h: h,
            rotation: 0,
            imageUrl: url,
            style: { borderColor: 'transparent', borderWidth: 0, borderRadius: 0, shadow: false },
        };
        widgets.push(widget);
        renderAllWidgets();
        selectWidget(widget.id);
    };
    img.src = url;
}

function insertTextBox() {
    const widget = {
        id: 'w_' + Date.now(),
        type: 'textbox',
        x: 40, y: 40,
        w: 240, h: 80,
        rotation: 0,
        text: 'Type here...',
        style: {
            fontSize:        16,
            fontColor:       '#1f2937',
            fontWeight:      'normal',
            textAlign:       'left',
            fill:            'transparent',
            fillTransparent: true,
            borderColor:     '#d1d5db',
            borderWidth:     0,
            borderRadius:    4,
            shadow:          false,
        },
    };
    widgets.push(widget);
    renderAllWidgets();
    selectWidget(widget.id);
}

function updateWidgetText(id, text) {
    const widget = widgets.find(w => w.id === id);
    if (widget) widget.text = text;
}

function insertHR() {
    if (editor) editor.execute('horizontalLine');
}

function renderAllWidgets() {
    const overlay = document.getElementById('widget-overlay');
    overlay.innerHTML = '';
    overlay.style.pointerEvents = 'none';
    widgets.forEach(w => {
        const el = w.type === 'connector' ? createConnectorEl(w) : buildWidgetEl(w);
        overlay.appendChild(el);
    });
}

function buildWidgetEl(widget) {
    const el = document.createElement('div');
    el.id = 'widget-' + widget.id;
    el.className = 'rb-widget widget-el';

    const ALL_OVERLAY_TYPES = [
        'image','rectangle','circle','line','arrow',
        'rounded-rectangle','triangle','diamond','pentagon',
        'hexagon','star','arrow-right','arrow-left',
        'arrow-up','arrow-down','double-arrow','textbox',
    ];
    const DATA_WIDGET_TYPES = ['kpi','chart','gantt','milestone','team','blocker'];
    const isShape      = ALL_OVERLAY_TYPES.includes(widget.type);
    const isDataWidget = DATA_WIDGET_TYPES.includes(widget.type);
    const dwBorder     = `1px solid ${widget.style?.borderColor || '#e5e7eb'}`;
    const dwRadius     = (widget.style?.borderRadius ?? 8) + 'px';

    el.style.cssText = `
        left:${widget.x}px; top:${widget.y}px;
        width:${widget.w}px; height:${widget.h}px;
        background:${isShape ? 'transparent' : 'white'};
        border:${isShape ? '1px solid transparent' : (isDataWidget ? dwBorder : '2px dashed #6366f1')};
        border-radius:${isDataWidget ? dwRadius : '8px'};
        box-shadow:none;
        transform:rotate(${widget.rotation || 0}deg);
        transform-origin:center center;
        z-index:1; pointer-events:all; cursor:move;
    `;

    // Content
    const content = document.createElement('div');
    content.className = 'widget-content';
    content.style.padding = isShape ? '0' : '8px';
    if (isShape) content.style.overflow = 'visible';
    if (widget.style?.shadow) content.style.filter = 'drop-shadow(0 6px 10px rgba(0,0,0,.3))';
    if (widget.type === 'textbox') {
        content.style.pointerEvents = 'auto';
        content.style.cursor = 'text';
    }
    content.innerHTML = renderWidgetContent(widget);
    el.appendChild(content);

    // Resize handle
    const handle = document.createElement('div');
    handle.className = 'widget-handle';
    el.appendChild(handle);

    // Rotate stem
    const rotateLine = document.createElement('div');
    rotateLine.className = 'rotate-line';
    rotateLine.style.cssText = `
        position:absolute; top:-18px; left:50%; transform:translateX(-50%);
        width:1px; height:18px; background:#9ca3af; z-index:19; display:none; pointer-events:none;
    `;
    el.appendChild(rotateLine);

    // Rotate handle
    const rotateHandle = document.createElement('div');
    rotateHandle.className = 'rotate-handle';
    rotateHandle.style.cssText = `
        position:absolute; top:-32px; left:50%; transform:translateX(-50%);
        width:14px; height:14px; background:#2563eb; border-radius:50%;
        cursor:grab; z-index:21; pointer-events:all;
        border:2px solid white; box-shadow:0 1px 4px rgba(0,0,0,.4); display:none;
    `;
    el.appendChild(rotateHandle);

    // ── Drag ──
    el.addEventListener('mousedown', e => {
        if (e.target === handle || e.target.closest('.rotate-handle')) return;
        if (widget.type === 'textbox' && e.target.closest('.widget-content')) {
            selectWidget(widget.id);
            return;
        }
        e.preventDefault();
        selectWidget(widget.id);
        const startX = e.clientX - widget.x;
        const startY = e.clientY - widget.y;
        let rafId = null;
        const onMove = e => {
            const nx = Math.max(0, e.clientX - startX);
            const ny = Math.max(0, e.clientY - startY);
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(() => {
                widget.x = nx; widget.y = ny;
                el.style.left = nx + 'px'; el.style.top = ny + 'px';
                updateConnectedConnectors(widget.id);
            });
        };
        const onUp = () => {
            if (rafId) cancelAnimationFrame(rafId);
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });

    // ── Resize ──
    handle.addEventListener('mousedown', e => {
        e.preventDefault(); e.stopPropagation();
        const startX = e.clientX, startY = e.clientY;
        const startW = widget.w, startH = widget.h;
        let rafId = null;
        const onMove = e => {
            const nw = Math.max(40, startW + e.clientX - startX);
            const nh = Math.max(20, startH + e.clientY - startY);
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(() => {
                widget.w = nw; widget.h = nh;
                el.style.width = nw + 'px'; el.style.height = nh + 'px';
            });
        };
        const onUp = () => {
            if (rafId) cancelAnimationFrame(rafId);
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            content.innerHTML = renderWidgetContent(widget);
            updateConnectedConnectors(widget.id);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });

    // ── Rotate ──
    rotateHandle.addEventListener('mousedown', e => {
        e.preventDefault(); e.stopPropagation();
        rotateHandle.style.cursor = 'grabbing';
        const rect = el.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const startRotation = widget.rotation || 0;
        const startAngle = Math.atan2(e.clientY - centerY, e.clientX - centerX) * 180 / Math.PI;
        let rafId = null;
        const onMove = e => {
            const currentAngle = Math.atan2(e.clientY - centerY, e.clientX - centerX) * 180 / Math.PI;
            let newRotation = startRotation + (currentAngle - startAngle);
            if (e.shiftKey) newRotation = Math.round(newRotation / 15) * 15;
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(() => {
                widget.rotation = newRotation;
                el.style.transform = `rotate(${newRotation}deg)`;
            });
        };
        const onUp = () => {
            if (rafId) cancelAnimationFrame(rafId);
            rotateHandle.style.cursor = 'grab';
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });

    // ── Context menu ──
    el.addEventListener('contextmenu', e => {
        e.preventDefault();
        e.stopPropagation();
        selectWidget(widget.id);
        renderSettingsPanel();
        showContextMenu(e.clientX, e.clientY);
    });

    return el;
}

function getAnchorPoint(widgetId, side) {
    const w = widgets.find(x => x.id === widgetId);
    if (!w || w.type === 'connector') return null;
    switch (side) {
        case 'top':    return { x: w.x + w.w/2, y: w.y };
        case 'bottom': return { x: w.x + w.w/2, y: w.y + w.h };
        case 'left':   return { x: w.x,          y: w.y + w.h/2 };
        case 'right':  return { x: w.x + w.w,    y: w.y + w.h/2 };
    }
    return null;
}

function resolveConnectorPoints(connector) {
    let sx = connector.startX, sy = connector.startY;
    let ex = connector.endX,   ey = connector.endY;
    if (connector.startAnchor) {
        const pt = getAnchorPoint(connector.startAnchor.widgetId, connector.startAnchor.side);
        if (pt) { sx = pt.x; sy = pt.y; }
    }
    if (connector.endAnchor) {
        const pt = getAnchorPoint(connector.endAnchor.widgetId, connector.endAnchor.side);
        if (pt) { ex = pt.x; ey = pt.y; }
    }
    return { sx, sy, ex, ey };
}

function showAnchorDots(excludeConnectorId) {
    hideAnchorDots();
    widgets.forEach(w => {
        if (w.type === 'connector') return;
        ['top','bottom','left','right'].forEach(side => {
            const pt = getAnchorPoint(w.id, side);
            if (!pt) return;
            const dot = document.createElement('div');
            dot.className = 'anchor-dot';
            dot.dataset.widgetId = w.id;
            dot.dataset.side = side;
            dot.style.cssText = `
                position:absolute;
                left:${pt.x - 7}px; top:${pt.y - 7}px;
                width:14px; height:14px;
                background:#2563eb; border:2px solid white;
                border-radius:50%; pointer-events:none;
                z-index:50; opacity:0.7;
                transition:transform .1s, opacity .1s, background .1s;
                box-shadow:0 0 0 3px rgba(37,99,235,.3);
            `;
            document.getElementById('widget-overlay').appendChild(dot);
        });
    });
}

function hideAnchorDots() {
    document.querySelectorAll('.anchor-dot').forEach(d => d.remove());
}

function findNearestAnchor(x, y, excludeConnectorId, snapDist = 18) {
    let best = null, bestDist = snapDist;
    widgets.forEach(w => {
        if (w.type === 'connector') return;
        ['top','bottom','left','right'].forEach(side => {
            const pt = getAnchorPoint(w.id, side);
            if (!pt) return;
            const dist = Math.hypot(x - pt.x, y - pt.y);
            if (dist < bestDist) { bestDist = dist; best = { widgetId: w.id, side, pt }; }
        });
    });
    return best;
}

function highlightAnchor(anchor) {
    document.querySelectorAll('.anchor-dot').forEach(d => {
        const match = anchor && d.dataset.widgetId === anchor.widgetId && d.dataset.side === anchor.side;
        d.style.background = match ? '#dc2626' : '#2563eb';
        d.style.opacity    = match ? '1' : '0.7';
        d.style.transform  = match ? 'scale(1.4)' : 'scale(1)';
    });
}

function updateConnectedConnectors(movedWidgetId) {
    widgets.forEach(w => {
        if (w.type !== 'connector') return;
        const startLinked = w.startAnchor?.widgetId === movedWidgetId;
        const endLinked   = w.endAnchor?.widgetId   === movedWidgetId;
        if (!startLinked && !endLinked) return;
        if (startLinked) {
            const pt = getAnchorPoint(w.startAnchor.widgetId, w.startAnchor.side);
            if (pt) { w.startX = pt.x; w.startY = pt.y; }
        }
        if (endLinked) {
            const pt = getAnchorPoint(w.endAnchor.widgetId, w.endAnchor.side);
            if (pt) { w.endX = pt.x; w.endY = pt.y; }
        }
        w.x = Math.min(w.startX, w.endX);
        w.y = Math.min(w.startY, w.endY);
        w.w = Math.abs(w.endX - w.startX);
        w.h = Math.abs(w.endY - w.startY);
        updateConnectorEl(w.id);
    });
}

function buildConnectorPath(lx1, ly1, lx2, ly2, lineType, midRatio) {
    switch (lineType) {
        case 'straight':
            return `M ${lx1} ${ly1} L ${lx2} ${ly2}`;
        case 'curved': {
            const absDx = Math.abs(lx2 - lx1), absDy = Math.abs(ly2 - ly1);
            if (absDx >= absDy) {
                const off = Math.max(absDx * 0.5, 40);
                return `M ${lx1} ${ly1} C ${lx1+off} ${ly1}, ${lx2-off} ${ly2}, ${lx2} ${ly2}`;
            } else {
                const off = Math.max(absDy * 0.5, 40);
                return `M ${lx1} ${ly1} C ${lx1} ${ly1+off}, ${lx2} ${ly2-off}, ${lx2} ${ly2}`;
            }
        }
        case 'elbow':
        default: {
            const dx = lx2 - lx1, dy = ly2 - ly1;
            const ratio = (typeof midRatio === 'number') ? midRatio : 0.5;
            if (Math.abs(dy) > Math.abs(dx)) {
                const midY = ly1 + dy * ratio;
                return `M ${lx1} ${ly1} L ${lx1} ${midY} L ${lx2} ${midY} L ${lx2} ${ly2}`;
            } else {
                const midX = lx1 + dx * ratio;
                return `M ${lx1} ${ly1} L ${midX} ${ly1} L ${midX} ${ly2} L ${lx2} ${ly2}`;
            }
        }
    }
}

function getElbowSegments(lx1, ly1, lx2, ly2, midRatio) {
    const dx = lx2 - lx1, dy = ly2 - ly1;
    const ratio = (typeof midRatio === 'number') ? midRatio : 0.5;
    if (Math.abs(dy) > Math.abs(dx)) {
        const midY = ly1 + dy * ratio;
        return [
            { x1: lx1, y1: ly1,  x2: lx1, y2: midY, dir: 'v' },
            { x1: lx1, y1: midY, x2: lx2, y2: midY, dir: 'h' },
            { x1: lx2, y1: midY, x2: lx2, y2: ly2,  dir: 'v' },
        ].filter(s => Math.hypot(s.x2 - s.x1, s.y2 - s.y1) > 2);
    } else {
        const midX = lx1 + dx * ratio;
        return [
            { x1: lx1,  y1: ly1, x2: midX, y2: ly1, dir: 'h' },
            { x1: midX, y1: ly1, x2: midX, y2: ly2, dir: 'v' },
            { x1: midX, y1: ly2, x2: lx2,  y2: ly2, dir: 'h' },
        ].filter(s => Math.hypot(s.x2 - s.x1, s.y2 - s.y1) > 2);
    }
}

function getConnectorSegments(lx1, ly1, lx2, ly2, lineType, midRatio) {
    if (lineType === 'straight') {
        return [{ x1: lx1, y1: ly1, x2: lx2, y2: ly2 }];
    }
    return getElbowSegments(lx1, ly1, lx2, ly2, midRatio);
}

function segmentIntersection(s1, s2) {
    const dx1 = s1.x2-s1.x1, dy1 = s1.y2-s1.y1;
    const dx2 = s2.x2-s2.x1, dy2 = s2.y2-s2.y1;
    const denom = dx1*dy2 - dy1*dx2;
    if (Math.abs(denom) < 0.001) return null;
    const t1 = ((s2.x1-s1.x1)*dy2 - (s2.y1-s1.y1)*dx2) / denom;
    const t2 = ((s2.x1-s1.x1)*dy1 - (s2.y1-s1.y1)*dx1) / denom;
    if (t1 > 0.05 && t1 < 0.95 && t2 > 0.05 && t2 < 0.95) {
        return { x: s1.x1 + t1*dx1, y: s1.y1 + t1*dy1, t1 };
    }
    return null;
}

function buildPathWithJumps(connectorWidget, lx1, ly1, lx2, ly2, svgOffX, svgOffY) {
    const s = connectorWidget.style || {};
    const lineType = s.lineType || 'elbow';
    const lineJump = s.lineJump || false;
    const midRatio = (typeof connectorWidget.midRatio === 'number') ? connectorWidget.midRatio : 0.5;

    if (!lineJump || lineType === 'curved') {
        return buildConnectorPath(lx1, ly1, lx2, ly2, lineType, midRatio);
    }

    const JUMP_R = 8;
    const segments = getConnectorSegments(lx1, ly1, lx2, ly2, lineType, midRatio);
    const jumpPoints = [];

    widgets.forEach(other => {
        if (other.id === connectorWidget.id || other.type !== 'connector') return;
        const { sx: ox1, sy: oy1, ex: ox2, ey: oy2 } = resolveConnectorPoints(other);
        const otherLx1 = ox1 - svgOffX, otherLy1 = oy1 - svgOffY;
        const otherLx2 = ox2 - svgOffX, otherLy2 = oy2 - svgOffY;
        const otherType = other.style?.lineType || 'elbow';
        const otherMidRatio = (typeof other.midRatio === 'number') ? other.midRatio : 0.5;
        const otherSegs = getConnectorSegments(otherLx1, otherLy1, otherLx2, otherLy2, otherType, otherMidRatio);

        segments.forEach((seg, si) => {
            otherSegs.forEach(otherSeg => {
                const pt = segmentIntersection(seg, otherSeg);
                if (pt) jumpPoints.push({ t: pt.t1, segIdx: si, px: pt.x, py: pt.y });
            });
        });
    });

    if (!jumpPoints.length) return buildConnectorPath(lx1, ly1, lx2, ly2, lineType, midRatio);

    let path = '';
    segments.forEach((seg, si) => {
        const segJumps = jumpPoints.filter(j => j.segIdx === si).sort((a, b) => a.t - b.t);
        const segLen = Math.hypot(seg.x2-seg.x1, seg.y2-seg.y1) || 1;
        const ux = (seg.x2-seg.x1)/segLen, uy = (seg.y2-seg.y1)/segLen;

        if (si === 0) path += `M ${seg.x1} ${seg.y1}`;

        segJumps.forEach(j => {
            const beforeX = j.px - ux * JUMP_R, beforeY = j.py - uy * JUMP_R;
            const afterX  = j.px + ux * JUMP_R, afterY  = j.py + uy * JUMP_R;
            path += ` L ${beforeX} ${beforeY} A ${JUMP_R} ${JUMP_R} 0 0 1 ${afterX} ${afterY}`;
        });

        path += ` L ${seg.x2} ${seg.y2}`;
    });

    return path;
}

function createConnectorEl(widget) {
    const { sx: x1, sy: y1, ex: x2, ey: y2 } = resolveConnectorPoints(widget);
    const s = widget.style || {};
    const lineType = s.lineType || 'elbow';

    const minX = Math.min(x1, x2) - 10;
    const minY = Math.min(y1, y2) - 10;
    const svgW = Math.max(x1, x2) - minX + 10;
    const svgH = Math.max(y1, y2) - minY + 10;
    const lx1 = x1 - minX, ly1 = y1 - minY;
    const lx2 = x2 - minX, ly2 = y2 - minY;
    const midRatio = (typeof widget.midRatio === 'number') ? widget.midRatio : 0.5;

    const color = s.color || '#374151';
    const strokeW = s.strokeWidth || 2;
    const dashArr = s.lineStyle === 'dashed' ? 'stroke-dasharray="6,3"' : '';
    const markerId = `arrow-${widget.id}`;
    const markerStart = s.arrowHead === 'both' ? `marker-start="url(#${markerId}-s)"` : '';
    const markerEnd   = s.arrowHead !== 'none' ? `marker-end="url(#${markerId})"` : '';
    const isSelected  = selectedWidgetId === widget.id;
    const pathD = buildPathWithJumps(widget, lx1, ly1, lx2, ly2, minX, minY);

    const el = document.createElement('div');
    el.id = 'widget-' + widget.id;
    el.style.cssText = `
        position:absolute;
        left:${minX}px; top:${minY}px;
        width:${svgW}px; height:${svgH}px;
        pointer-events:none;
        z-index:${isSelected ? 10 : 1};
    `;

    el.innerHTML = `
        <svg width="${svgW}" height="${svgH}" style="overflow:visible;position:absolute;top:0;left:0">
            <defs>
                <marker id="${markerId}" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto">
                    <polygon points="0,0 0,6 8,3" fill="${color}"/>
                </marker>
                <marker id="${markerId}-s" markerWidth="8" markerHeight="8" refX="2" refY="3" orient="auto-start-reverse">
                    <polygon points="0,0 0,6 8,3" fill="${color}"/>
                </marker>
            </defs>
            <path d="${pathD}" fill="none"
                  stroke="${color}" stroke-width="${strokeW}"
                  stroke-linejoin="round" stroke-linecap="round" ${dashArr}
                  ${markerStart} ${markerEnd}
                  style="${s.shadow ? 'filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))' : ''}"/>
        </svg>
    `;

    // Segment hit areas — only the middle (draggable) segment, only when selected
    if (lineType === 'elbow' && isSelected) {
        const segs = getElbowSegments(lx1, ly1, lx2, ly2, midRatio);
        const midSeg = segs[1]; // index 1 is always the draggable middle segment
        if (midSeg) {
            const isH = midSeg.dir === 'h';
            const segLen = Math.hypot(midSeg.x2 - midSeg.x1, midSeg.y2 - midSeg.y1);
            if (segLen >= 4) {
                const HIT = 10;
                const segEl = document.createElement('div');
                if (isH) {
                    segEl.style.cssText = `
                        position:absolute;
                        left:${Math.min(midSeg.x1, midSeg.x2)}px;
                        top:${midSeg.y1 - HIT}px;
                        width:${Math.abs(midSeg.x2 - midSeg.x1)}px;
                        height:${HIT * 2}px;
                        cursor:ns-resize; pointer-events:all;
                        background:transparent; z-index:8;
                    `;
                } else {
                    segEl.style.cssText = `
                        position:absolute;
                        left:${midSeg.x1 - HIT}px;
                        top:${Math.min(midSeg.y1, midSeg.y2)}px;
                        width:${HIT * 2}px;
                        height:${Math.abs(midSeg.y2 - midSeg.y1)}px;
                        cursor:ew-resize; pointer-events:all;
                        background:transparent; z-index:8;
                    `;
                }

                segEl.addEventListener('mouseenter', () => { segEl.style.background = 'rgba(59,130,246,0.15)'; });
                segEl.addEventListener('mouseleave', () => { segEl.style.background = 'transparent'; });

                segEl.addEventListener('mousedown', (e) => {
                    if (e.button === 2) return;
                    e.preventDefault();
                    e.stopPropagation();

                    const { sx: p1x, sy: p1y, ex: p2x, ey: p2y } = resolveConnectorPoints(widget);
                    const totalDist = isH ? Math.abs(p2y - p1y) : Math.abs(p2x - p1x);
                    const origRatio = widget.midRatio ?? 0.5;
                    const startCX = e.clientX, startCY = e.clientY;
                    let rafId = null;
                    const onMove = (e) => {
                        if (rafId) cancelAnimationFrame(rafId);
                        rafId = requestAnimationFrame(() => {
                            const delta = isH ? (e.clientY - startCY) : (e.clientX - startCX);
                            widget.midRatio = Math.max(0.05, Math.min(0.95,
                                origRatio + delta / (totalDist || 1)
                            ));
                            updateConnectorEl(widget.id);
                        });
                    };
                    const onUp = () => {
                        if (rafId) cancelAnimationFrame(rafId);
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('mouseup', onUp);
                    };
                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                });

                el.appendChild(segEl);
            }
        }
    }

    // SVG hit path — follows the actual line so it never blocks other widgets
    const hitSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    hitSvg.setAttribute('width', svgW);
    hitSvg.setAttribute('height', svgH);
    hitSvg.style.cssText = `position:absolute;top:0;left:0;overflow:visible;pointer-events:none;z-index:6;`;

    const hitPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    hitPath.setAttribute('d', pathD);
    hitPath.setAttribute('fill', 'none');
    hitPath.setAttribute('stroke', 'transparent');
    hitPath.setAttribute('stroke-width', '16');
    hitPath.setAttribute('stroke-linecap', 'round');
    hitPath.setAttribute('stroke-linejoin', 'round');
    hitPath.style.cssText = 'cursor:move; pointer-events:stroke;';

    hitSvg.appendChild(hitPath);
    el.appendChild(hitSvg);

    hitPath.addEventListener('mousedown', (e) => {
        if (e.button === 2) return;
        e.preventDefault();
        e.stopPropagation();
        selectWidget(widget.id);
        renderSettingsPanel();

        const startClientX = e.clientX, startClientY = e.clientY;
        const origStartX = widget.startX, origStartY = widget.startY;
        const origEndX   = widget.endX,   origEndY   = widget.endY;
        let rafId = null;
        const onMove = (e) => {
            const ddx = e.clientX - startClientX, ddy = e.clientY - startClientY;
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(() => {
                if (!widget.startAnchor) { widget.startX = origStartX + ddx; widget.startY = origStartY + ddy; }
                if (!widget.endAnchor)   { widget.endX   = origEndX   + ddx; widget.endY   = origEndY   + ddy; }
                widget.x = Math.min(widget.startX, widget.endX);
                widget.y = Math.min(widget.startY, widget.endY);
                updateConnectorEl(widget.id);
            });
        };
        const onUp = () => {
            if (rafId) cancelAnimationFrame(rafId);
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });

    hitPath.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        e.stopPropagation();
        selectWidget(widget.id);
        renderSettingsPanel();
        showContextMenu(e.clientX, e.clientY);
    });

    // Midpoint handle — yellow square, drag to adjust midRatio (elbow only, when selected)
    if (isSelected && lineType === 'elbow') {
        const { sx: rx1, sy: ry1, ex: rx2, ey: ry2 } = resolveConnectorPoints(widget);
        const rdx = rx2 - rx1, rdy = ry2 - ry1;
        const vertDom = Math.abs(rdy) > Math.abs(rdx);
        let handleCanvasX, handleCanvasY;
        if (vertDom) {
            const midY = ry1 + rdy * midRatio;
            handleCanvasX = (rx1 + rx2) / 2;
            handleCanvasY = midY;
        } else {
            const midX = rx1 + rdx * midRatio;
            handleCanvasX = midX;
            handleCanvasY = (ry1 + ry2) / 2;
        }
        const handleLocalX = handleCanvasX - minX;
        const handleLocalY = handleCanvasY - minY;

        const midHandle = document.createElement('div');
        midHandle.style.cssText = `
            position:absolute;
            left:${handleLocalX - 6}px; top:${handleLocalY - 6}px;
            width:12px; height:12px;
            background:#f59e0b; border:2px solid white;
            border-radius:2px; cursor:${vertDom ? 'ns-resize' : 'ew-resize'};
            pointer-events:all; z-index:15;
            box-shadow:0 1px 3px rgba(0,0,0,.3);
        `;

        midHandle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const { sx: p1x, sy: p1y, ex: p2x, ey: p2y } = resolveConnectorPoints(widget);
            const totalDist = vertDom ? Math.abs(p2y - p1y) : Math.abs(p2x - p1x);
            const origRatio = widget.midRatio ?? 0.5;
            const startCX = e.clientX, startCY = e.clientY;
            let rafId = null;
            const onMove = (e) => {
                if (rafId) cancelAnimationFrame(rafId);
                rafId = requestAnimationFrame(() => {
                    const delta = vertDom ? (e.clientY - startCY) : (e.clientX - startCX);
                    widget.midRatio = Math.max(0.05, Math.min(0.95,
                        origRatio + delta / (totalDist || 1)
                    ));
                    updateConnectorEl(widget.id);
                });
            };
            const onUp = () => {
                if (rafId) cancelAnimationFrame(rafId);
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });

        el.appendChild(midHandle);
    }

    if (isSelected) {
        // Endpoint handles
        ['start', 'end'].forEach(point => {
            const hx = point === 'start' ? lx1 : lx2;
            const hy = point === 'start' ? ly1 : ly2;
            const isAnchored = point === 'start' ? !!widget.startAnchor : !!widget.endAnchor;
            const handle = document.createElement('div');
            handle.style.cssText = `
                position:absolute;
                left:${hx - 7}px; top:${hy - 7}px;
                width:14px; height:14px;
                background:${isAnchored ? '#dc2626' : (point === 'start' ? '#16a34a' : '#f59e0b')};
                border-radius:50%;
                border:${isAnchored ? '3px solid white' : '2px solid white'};
                cursor:crosshair; pointer-events:all;
                box-shadow:${isAnchored ? '0 0 0 2px #dc2626' : '0 1px 3px rgba(0,0,0,.3)'};
                z-index:20;
            `;
            if (isAnchored) {
                const anchor = point === 'start' ? widget.startAnchor : widget.endAnchor;
                handle.title = `Anchored (${anchor.side})`;
            }
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (point === 'start') widget.startAnchor = null;
                else                   widget.endAnchor   = null;

                const overlay = document.getElementById('widget-overlay');
                const overlayRect = overlay.getBoundingClientRect();
                showAnchorDots(widget.id);
                let snapAnchor = null;
                let rafId = null;

                const onMove = (e) => {
                    const nx = e.clientX - overlayRect.left;
                    const ny = e.clientY - overlayRect.top;
                    snapAnchor = findNearestAnchor(nx, ny, widget.id);
                    highlightAnchor(snapAnchor);
                    const finalX = snapAnchor ? snapAnchor.pt.x : nx;
                    const finalY = snapAnchor ? snapAnchor.pt.y : ny;
                    if (rafId) cancelAnimationFrame(rafId);
                    rafId = requestAnimationFrame(() => {
                        if (point === 'start') { widget.startX = finalX; widget.startY = finalY; }
                        else                   { widget.endX   = finalX; widget.endY   = finalY; }
                        widget.x = Math.min(widget.startX, widget.endX);
                        widget.y = Math.min(widget.startY, widget.endY);
                        widget.w = Math.abs(widget.endX - widget.startX);
                        widget.h = Math.abs(widget.endY - widget.startY);
                        updateConnectorEl(widget.id);
                    });
                };

                const onUp = (e) => {
                    if (rafId) cancelAnimationFrame(rafId);
                    hideAnchorDots();
                    const nx = e.clientX - overlayRect.left;
                    const ny = e.clientY - overlayRect.top;
                    const finalSnap = findNearestAnchor(nx, ny, widget.id);
                    if (point === 'start') {
                        widget.startAnchor = finalSnap ? { widgetId: finalSnap.widgetId, side: finalSnap.side } : null;
                        if (finalSnap) { widget.startX = finalSnap.pt.x; widget.startY = finalSnap.pt.y; }
                    } else {
                        widget.endAnchor = finalSnap ? { widgetId: finalSnap.widgetId, side: finalSnap.side } : null;
                        if (finalSnap) { widget.endX = finalSnap.pt.x; widget.endY = finalSnap.pt.y; }
                    }
                    updateConnectorEl(widget.id);
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                };

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
            el.appendChild(handle);
        });

        // Mini toolbar
        const tb = document.createElement('div');
        tb.className = 'widget-tb';
        const tbX = (lx1 + lx2) / 2 - 50;
        const tbY = Math.min(ly1, ly2) - 38;
        tb.style.cssText = `
            position:absolute; left:${tbX}px; top:${tbY}px;
            background:#1f2937; color:white; border-radius:6px;
            padding:3px 8px; display:flex; align-items:center; gap:8px;
            z-index:20; font-size:11px; white-space:nowrap;
            box-shadow:0 2px 8px rgba(0,0,0,.4); pointer-events:all;
        `;
        tb.innerHTML = `
            <span style="color:#9ca3af;font-size:10px">CONNECTOR</span>
            <button style="background:#dc2626;border:none;color:white;padding:2px 8px;
                           border-radius:4px;cursor:pointer;font-size:11px">✕ Delete</button>
        `;
        tb.querySelector('button').addEventListener('mousedown', e => {
            e.preventDefault(); e.stopPropagation();
            deleteWidget(widget.id);
        });
        el.appendChild(tb);
    }

    return el;
}

function updateConnectorEl(id) {
    const widget = widgets.find(w => w.id === id);
    if (!widget) return;
    const old = document.getElementById('widget-' + id);
    if (!old) return;
    old.parentNode.replaceChild(createConnectorEl(widget), old);
    // Re-render other connectors that have lineJump and may intersect this one
    widgets.forEach(w => {
        if (w.id === id || w.type !== 'connector' || !w.style?.lineJump) return;
        const el = document.getElementById('widget-' + w.id);
        if (el) el.parentNode.replaceChild(createConnectorEl(w), el);
    });
}

function deselectCurrent() {
    if (!selectedWidgetId) return;
    const prevW = widgets.find(w => w.id === selectedWidgetId);
    if (prevW && prevW.type === 'connector') {
        selectedWidgetId = null;
        updateConnectorEl(prevW.id);
        return;
    }
    const prev = document.getElementById('widget-' + selectedWidgetId);
    if (prev) {
        const ALL_OVERLAY_TYPES = [
            'image','rectangle','circle','line','arrow',
            'rounded-rectangle','triangle','diamond','pentagon',
            'hexagon','star','arrow-right','arrow-left',
            'arrow-up','arrow-down','double-arrow','textbox',
        ];
        const DATA_WIDGET_TYPES = ['kpi','chart','gantt','milestone','team','blocker'];
        const isPrevShape      = prevW && ALL_OVERLAY_TYPES.includes(prevW.type);
        const isPrevDataWidget = prevW && DATA_WIDGET_TYPES.includes(prevW.type);
        prev.style.border = isPrevShape
            ? '1px solid transparent'
            : isPrevDataWidget
                ? `1px solid ${prevW.style?.borderColor || '#e5e7eb'}`
                : '2px dashed #6366f1';
        if (isPrevDataWidget) prev.style.borderRadius = (prevW.style?.borderRadius ?? 8) + 'px';
        prev.style.boxShadow = 'none';
        prev.style.zIndex = '1';
        const tb = prev.querySelector('.widget-tb');
        if (tb) tb.remove();
        const rh = prev.querySelector('.rotate-handle');
        const rl = prev.querySelector('.rotate-line');
        if (rh) rh.style.display = 'none';
        if (rl) rl.style.display = 'none';
    }
    selectedWidgetId = null;
}

function selectWidget(id) {
    if (selectedWidgetId === id) return;
    deselectCurrent();
    selectedWidgetId = id;

    const widget = widgets.find(w => w.id === id);
    if (!widget) return;

    if (widget.type === 'connector') {
        updateConnectorEl(id);
        renderSettingsPanel();
        return;
    }

    const el = document.getElementById('widget-' + id);
    if (!el) return;
    el.style.border = '2px solid #2563eb';
    el.style.zIndex = '10';
    el.style.boxShadow = '0 0 0 3px rgba(37,99,235,.15)';

    const rh = el.querySelector('.rotate-handle');
    const rl = el.querySelector('.rotate-line');
    if (rh) rh.style.display = 'block';
    if (rl) rl.style.display = 'block';

    const tb = document.createElement('div');
    tb.className = 'widget-tb';
    tb.style.cssText = `
        position:absolute; bottom:-34px; left:0; background:#1e293b; color:white;
        border-radius:6px; padding:3px 8px; display:flex; align-items:center;
        gap:8px; z-index:20; font-size:11px; white-space:nowrap;
        box-shadow:0 2px 8px rgba(0,0,0,.5); pointer-events:all;
    `;
    tb.innerHTML = `
        <span style="color:#64748b;font-size:9px;text-transform:uppercase;letter-spacing:.5px">${widget.type}</span>
        <button style="background:#dc2626;border:none;color:white;padding:2px 7px;
                       border-radius:4px;cursor:pointer;font-size:11px">✕ Delete</button>
    `;
    tb.querySelector('button').addEventListener('mousedown', e => {
        e.preventDefault(); e.stopPropagation();
        deleteWidget(id);
    });
    el.appendChild(tb);
    renderSettingsPanel();
}

function deleteWidget(id) {
    widgets.forEach(w => {
        if (w.type !== 'connector') return;
        if (w.startAnchor?.widgetId === id) w.startAnchor = null;
        if (w.endAnchor?.widgetId   === id) w.endAnchor   = null;
    });
    widgets = widgets.filter(w => w.id !== id);
    selectedWidgetId = null;
    renderAllWidgets();
    document.getElementById('settings-panel').style.display = 'none';
    document.getElementById('context-menu').style.display = 'none';
}

function showContextMenu(x, y) {
    const menu = document.getElementById('context-menu');
    menu.style.display = 'block';
    const menuRect = menu.getBoundingClientRect();
    const maxX = window.innerWidth  - menuRect.width  - 10;
    const maxY = window.innerHeight - menuRect.height - 10;
    menu.style.left = Math.min(x, maxX) + 'px';
    menu.style.top  = Math.min(y, maxY) + 'px';
}

function hideContextMenu() {
    document.getElementById('context-menu').style.display = 'none';
}

function getWidgetIndex(id) {
    return widgets.findIndex(w => w.id === id);
}

function bringToFront() {
    if (!selectedWidgetId) return;
    const idx = getWidgetIndex(selectedWidgetId);
    if (idx === -1) return;
    const [w] = widgets.splice(idx, 1);
    widgets.push(w);
    renderAllWidgets();
    selectWidget(selectedWidgetId);
    hideContextMenu();
}

function sendToBack() {
    if (!selectedWidgetId) return;
    const idx = getWidgetIndex(selectedWidgetId);
    if (idx === -1) return;
    const [w] = widgets.splice(idx, 1);
    widgets.unshift(w);
    renderAllWidgets();
    selectWidget(selectedWidgetId);
    hideContextMenu();
}

function bringForward() {
    if (!selectedWidgetId) return;
    const idx = getWidgetIndex(selectedWidgetId);
    if (idx === -1 || idx === widgets.length - 1) return;
    [widgets[idx], widgets[idx+1]] = [widgets[idx+1], widgets[idx]];
    renderAllWidgets();
    selectWidget(selectedWidgetId);
    hideContextMenu();
}

function sendBackward() {
    if (!selectedWidgetId) return;
    const idx = getWidgetIndex(selectedWidgetId);
    if (idx <= 0) return;
    [widgets[idx], widgets[idx-1]] = [widgets[idx-1], widgets[idx]];
    renderAllWidgets();
    selectWidget(selectedWidgetId);
    hideContextMenu();
}

// Deselect on outside click
document.addEventListener('mousedown', e => {
    if (e.button === 2) return;
    if (!e.target.closest('#widget-overlay') && !e.target.closest('#settings-panel') && !e.target.closest('#context-menu')) {
        if (selectedWidgetId) {
            deselectCurrent();
            document.getElementById('settings-panel').style.display = 'none';
        }
    }
});

document.addEventListener('click', e => {
    if (!e.target.closest('#context-menu')) hideContextMenu();
});

function formatGanttDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return `${d.getDate()}/${d.getMonth()+1}`;
}

// ── Widget content ─────────────────────────────────────────────────────────
function renderWidgetContent(widget) {
    switch (widget.type) {

        case 'image': {
            const s = widget.style || {};
            return `<img src="${widget.imageUrl}"
                         style="width:100%;height:100%;object-fit:contain;
                                border:${s.borderWidth || 0}px solid ${s.borderColor || 'transparent'};
                                border-radius:${s.borderRadius || 0}px;
                                display:block;box-sizing:border-box;pointer-events:none" />`;
        }

        case 'rectangle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || '#6366f1');
            return `<div style="width:100%;height:100%;
                        background:${fillColor};
                        border:${s.borderWidth ?? 2}px solid ${s.borderColor || '#4f46e5'};
                        border-radius:${s.borderRadius ?? 4}px;
                        box-sizing:border-box"></div>`;
        }

        case 'circle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || '#6366f1');
            return `<div style="width:100%;height:100%;
                        background:${fillColor};
                        border:${s.borderWidth ?? 2}px solid ${s.borderColor || '#4f46e5'};
                        border-radius:50%;
                        box-sizing:border-box"></div>`;
        }

        case 'line': {
            const s = widget.style || {};
            return `<div style="width:100%;height:100%;display:flex;align-items:center">
                        <div style="width:100%;height:${Math.max(2, s.borderWidth || 4)}px;
                             background:${s.fill || '#374151'};
                             border-radius:${s.borderRadius ?? 0}px"></div>
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

        case 'rounded-rectangle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'transparent' : (s.fill || '#6366f1');
            return `<div style="width:100%;height:100%;background:${fillColor};
                        border:${s.borderWidth ?? 2}px solid ${s.borderColor || '#4f46e5'};
                        border-radius:16px;box-sizing:border-box"></div>`;
        }

        case 'triangle': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 140" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 155,135 5,135" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}"
                            stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'diamond': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 155,80 80,155 5,80" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}"
                            stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'pentagon': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 160 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="80,5 155,62 127,155 33,155 5,62" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}"
                            stroke-linejoin="round"/>
                    </svg>`;
        }

        case 'hexagon': {
            const s = widget.style || {};
            const fillColor = s.fillTransparent ? 'none' : (s.fill || '#6366f1');
            return `<svg width="100%" height="100%" viewBox="0 0 180 160" preserveAspectRatio="none" style="display:block">
                        <polygon points="45,5 135,5 175,80 135,155 45,155 5,80" fill="${fillColor}"
                            stroke="${s.borderColor || '#4f46e5'}" stroke-width="${s.borderWidth ?? 2}"
                            stroke-linejoin="round"/>
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
            return `<div contenteditable="true"
                        onblur="updateWidgetText('${widget.id}', this.innerText)"
                        style="width:100%;height:100%;background:${fillColor};
                            border:${s.borderWidth ?? 0}px solid ${s.borderColor || 'transparent'};
                            border-radius:${s.borderRadius ?? 4}px;
                            font-size:${s.fontSize ?? 16}px;
                            color:${s.fontColor || '#1f2937'};
                            font-weight:${s.fontWeight || 'normal'};
                            text-align:${s.textAlign || 'left'};
                            padding:8px;box-sizing:border-box;outline:none;
                            overflow:auto;white-space:pre-wrap">${widget.text || ''}</div>`;
        }

        case 'kpi': {
            const k = PROJECT_KPI;
            const baseW = 560, baseH = 180;
            const scale = Math.min((widget.w||baseW)/baseW, (widget.h||baseH)/baseH);
            const cards = [
                { label:'Total Tasks', value:k.total,            color:'#4f46e5', bg:'#f5f3ff' },
                { label:'Done',        value:k.done,             color:'#16a34a', bg:'#f0fdf4' },
                { label:'In Progress', value:k.in_progress,      color:'#2563eb', bg:'#eff6ff' },
                { label:'Overdue',     value:k.overdue,          color:'#dc2626', bg:'#fef2f2' },
                { label:'Progress',    value:k.progress_pct+'%', color:'#0891b2', bg:'#ecfeff' },
                { label:'Members',     value:k.members,          color:'#7c3aed', bg:'#faf5ff' },
            ];
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:${6*scale}px;height:100%;padding:${4*scale}px">` +
                cards.map(c => `
                    <div style="background:${c.bg};border-radius:${6*scale}px;padding:${8*scale}px;text-align:center;
                                border:1px solid ${c.color}22;display:flex;flex-direction:column;
                                align-items:center;justify-content:center;min-height:0">
                        <div style="font-size:${1.4*scale}em;font-weight:800;color:${c.color};line-height:1">${c.value}</div>
                        <div style="font-size:${9*scale}px;color:#6b7280;margin-top:${3*scale}px">${c.label}</div>
                    </div>`).join('') + '</div>';
        }

        case 'chart': {
            const d = CHART_DATA.tasksByStatus;
            const baseW = 460, baseH = 300;
            const scale = Math.min((widget.w||baseW)/baseW, (widget.h||baseH)/baseH);
            const bars = [
                { label:'Todo',        value: d.todo,        color:'#6b7280' },
                { label:'In Progress', value: d.in_progress, color:'#2563eb' },
                { label:'Review',      value: d.review,      color:'#d97706' },
                { label:'Done',        value: d.done,        color:'#16a34a' },
                { label:'Cancelled',   value: d.cancelled,   color:'#dc2626' },
            ];
            const maxV = Math.max(...bars.map(b => b.value), 1);
            return `<div style="padding:${8*scale}px;height:100%;display:flex;flex-direction:column">
                <div style="font-size:${11*scale}px;font-weight:600;color:#374151;margin-bottom:${8*scale}px">Tasks by Status</div>
                <div style="flex:1;display:flex;align-items:flex-end;gap:${6*scale}px;min-height:0">
                    ${bars.map(b => `
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:${3*scale}px;height:100%;justify-content:flex-end">
                            <span style="font-size:${10*scale}px;font-weight:600;color:${b.color}">${b.value}</span>
                            <div style="width:100%;background:${b.color};border-radius:${4*scale}px ${4*scale}px 0 0;
                                        height:${Math.max(4,Math.round((b.value/maxV)*80))}%;min-height:4px"></div>
                            <span style="font-size:${8*scale}px;color:#9ca3af;text-align:center;line-height:1.2">${b.label}</span>
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
            const baseW = 380, baseH = 240;
            const scale = Math.min((widget.w||baseW)/baseW, (widget.h||baseH)/baseH);
            return `<div style="height:100%;overflow:auto;padding:${4*scale}px">
                <div style="font-size:${10*scale}px;font-weight:700;color:#374151;margin-bottom:${8*scale}px">🎯 Milestones</div>
                ${ms.map(m => `
                    <div style="display:flex;align-items:center;gap:${7*scale}px;padding:${5*scale}px 0;
                                border-bottom:1px solid #f1f5f9">
                        <span style="font-size:${12*scale}px">${m.is_completed ? '✅' : '⭕'}</span>
                        <span style="flex:1;color:#1e293b;font-size:${11*scale}px">${m.name}</span>
                        <span style="color:#94a3b8;font-size:${9*scale}px;white-space:nowrap">${m.due_date||''}</span>
                    </div>`).join('')}
            </div>`;
        }

        case 'team': {
            const members = PROJECT_DATA.members || [];
            if (!members.length) return '<div style="padding:20px;color:#9ca3af;font-size:11px;text-align:center">No members</div>';
            const baseW = 420, baseH = 260;
            const scale = Math.min((widget.w||baseW)/baseW, (widget.h||baseH)/baseH);
            const colors = ['#4f46e5','#0891b2','#16a34a','#d97706','#dc2626','#7c3aed'];
            return `<div style="height:100%;overflow:auto;padding:${4*scale}px">
                <div style="font-size:${10*scale}px;font-weight:700;color:#374151;margin-bottom:${8*scale}px">👥 Team Members</div>
                ${members.map((m,i) => `
                    <div style="display:flex;align-items:center;gap:${8*scale}px;padding:${5*scale}px 0;border-bottom:1px solid #f1f5f9">
                        <div style="width:${26*scale}px;height:${26*scale}px;border-radius:50%;background:${colors[i%colors.length]};
                                    display:flex;align-items:center;justify-content:center;
                                    color:white;font-size:${10*scale}px;font-weight:700;flex-shrink:0">
                            ${(m.name||'?')[0].toUpperCase()}
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:${11*scale}px;font-weight:500;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${m.name}</div>
                            <div style="font-size:${9*scale}px;color:#94a3b8">${m.role}</div>
                        </div>
                        <span style="font-size:${9*scale}px;color:#64748b;white-space:nowrap">${m.tasks_count} tasks</span>
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
            const baseW = 400, baseH = 220;
            const scale = Math.min((widget.w||baseW)/baseW, (widget.h||baseH)/baseH);
            return `<div style="height:100%;overflow:auto;padding:${4*scale}px">
                <div style="font-size:${10*scale}px;font-weight:700;color:#dc2626;margin-bottom:${8*scale}px">🚨 Active Blockers</div>
                ${bl.map(b => `
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:${5*scale}px;padding:${7*scale}px;margin-bottom:${5*scale}px">
                        <div style="font-size:${10*scale}px;font-weight:600;color:#dc2626">${b.task_title}</div>
                        <div style="font-size:${9*scale}px;color:#6b7280;margin-top:2px">${b.description||''}</div>
                        <div style="font-size:${9*scale}px;color:#9ca3af;margin-top:2px">by ${b.reporter}</div>
                    </div>`).join('')}
            </div>`;
        }

        default:
            return `<div style="padding:16px;color:#9ca3af;font-size:11px;text-align:center">${widget.type}</div>`;
    }
}

// ── Settings panel ─────────────────────────────────────────────────────────
const SHAPE_STYLE_TYPES = [
    'rectangle', 'circle', 'line', 'arrow', 'image',
    'rounded-rectangle', 'triangle', 'diamond', 'pentagon',
    'hexagon', 'star', 'arrow-right', 'arrow-left',
    'arrow-up', 'arrow-down', 'double-arrow', 'textbox',
];

function renderSettingsPanel() {
    const panel = document.getElementById('settings-panel');
    const widget = widgets.find(w => w.id === selectedWidgetId);

    const DATA_WIDGET_TYPES = ['kpi','chart','gantt','milestone','team','blocker'];
    const isDataWidget  = !!(widget && DATA_WIDGET_TYPES.includes(widget.type));
    const isConnector   = !!(widget && widget.type === 'connector');

    if (!widget || (!SHAPE_STYLE_TYPES.includes(widget.type) && !isDataWidget && !isConnector)) {
        panel.style.display = 'none';
        return;
    }

    if (isConnector) {
        const s = widget.style || {};
        const lt = s.lineType || 'elbow';
        panel.style.display = 'block';
        panel.innerHTML = `
            <p style="color:#9ca3af;font-size:10px;font-weight:600;letter-spacing:.5px;margin:0 0 8px">LINE TYPE</p>
            <div style="display:flex;gap:4px;margin-bottom:12px">
                <button onclick="updateWidgetStyle('lineType','straight'); renderSettingsPanel()"
                    style="flex:1;background:${lt==='straight'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px 2px;border-radius:4px;cursor:pointer;font-size:10px;
                           display:flex;flex-direction:column;align-items:center;gap:2px">
                    <svg viewBox="0 0 28 16" width="28" height="16">
                        <line x1="2" y1="8" x2="22" y2="8" stroke="white" stroke-width="1.5"/>
                        <polygon points="20,5 26,8 20,11" fill="white"/>
                    </svg>
                    <span>Straight</span>
                </button>
                <button onclick="updateWidgetStyle('lineType','elbow'); renderSettingsPanel()"
                    style="flex:1;background:${lt==='elbow'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px 2px;border-radius:4px;cursor:pointer;font-size:10px;
                           display:flex;flex-direction:column;align-items:center;gap:2px">
                    <svg viewBox="0 0 28 16" width="28" height="16">
                        <polyline points="2,4 14,4 14,12 22,12" fill="none" stroke="white" stroke-width="1.5"/>
                        <polygon points="20,9 26,12 20,15" fill="white"/>
                    </svg>
                    <span>Elbow</span>
                </button>
                <button onclick="updateWidgetStyle('lineType','curved'); renderSettingsPanel()"
                    style="flex:1;background:${lt==='curved'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px 2px;border-radius:4px;cursor:pointer;font-size:10px;
                           display:flex;flex-direction:column;align-items:center;gap:2px">
                    <svg viewBox="0 0 28 16" width="28" height="16">
                        <path d="M 2 4 C 14 4, 14 12, 26 12" fill="none" stroke="white" stroke-width="1.5"/>
                        <polygon points="23,9 28,12 23,15" fill="white"/>
                    </svg>
                    <span>Curved</span>
                </button>
            </div>

            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:12px;cursor:pointer">
                <input type="checkbox" ${s.lineJump ? 'checked' : ''}
                       onchange="updateWidgetStyle('lineJump', this.checked)"
                       style="cursor:pointer">
                Line Jump (ข้ามเส้นที่ตัดกัน)
            </label>

            <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">Line Color</label>
            <input type="color" value="${s.color || '#374151'}"
                   oninput="updateWidgetStyle('color', this.value)"
                   style="width:100%;height:28px;border:none;border-radius:4px;margin-bottom:10px;cursor:pointer">

            <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">
                Stroke Width: <span id="sw-val">${s.strokeWidth || 2}</span>px
            </label>
            <input type="range" min="1" max="8" value="${s.strokeWidth || 2}"
                   oninput="document.getElementById('sw-val').textContent=this.value; updateWidgetStyle('strokeWidth', parseInt(this.value))"
                   style="width:100%;margin-bottom:10px">

            <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">Arrow Head</label>
            <div style="display:flex;gap:4px;margin-bottom:10px">
                <button onclick="updateWidgetStyle('arrowHead','end'); renderSettingsPanel()"
                    style="flex:1;background:${(s.arrowHead||'end')==='end'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px;border-radius:4px;cursor:pointer;font-size:13px">→</button>
                <button onclick="updateWidgetStyle('arrowHead','both'); renderSettingsPanel()"
                    style="flex:1;background:${s.arrowHead==='both'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px;border-radius:4px;cursor:pointer;font-size:13px">↔</button>
                <button onclick="updateWidgetStyle('arrowHead','none'); renderSettingsPanel()"
                    style="flex:1;background:${s.arrowHead==='none'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px;border-radius:4px;cursor:pointer;font-size:13px">—</button>
            </div>

            <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">Line Style</label>
            <div style="display:flex;gap:4px;margin-bottom:10px">
                <button onclick="updateWidgetStyle('lineStyle','solid'); renderSettingsPanel()"
                    style="flex:1;background:${(s.lineStyle||'solid')==='solid'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px;border-radius:4px;cursor:pointer;font-size:11px">Solid</button>
                <button onclick="updateWidgetStyle('lineStyle','dashed'); renderSettingsPanel()"
                    style="flex:1;background:${s.lineStyle==='dashed'?'#4f46e5':'#374151'};
                           color:white;border:none;padding:5px;border-radius:4px;cursor:pointer;font-size:11px">Dashed</button>
            </div>

            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;cursor:pointer">
                <input type="checkbox" ${s.shadow ? 'checked' : ''}
                       onchange="updateWidgetStyle('shadow', this.checked)"
                       style="cursor:pointer;width:13px;height:13px">
                Drop Shadow
            </label>
        `;
        return;
    }

    if (isDataWidget) {
        const s = widget.style || {};
        panel.style.display = 'block';
        panel.innerHTML = `
            <p style="color:#9ca3af;font-size:10px;font-weight:600;letter-spacing:.5px;margin:0 0 10px">SETTINGS</p>
            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:12px;cursor:pointer">
                <input type="checkbox" ${s.shadow ? 'checked' : ''}
                       onchange="updateWidgetStyle('shadow', this.checked)"
                       style="cursor:pointer;width:13px;height:13px">
                Drop Shadow
            </label>
            <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">Border Color</label>
            <input type="color" value="${s.borderColor || '#e5e7eb'}"
                   oninput="updateWidgetStyle('borderColor', this.value)"
                   style="width:100%;height:28px;border:none;border-radius:4px;margin-bottom:10px;cursor:pointer">
            <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">
                Border Radius: <span id="sp-dw-br">${s.borderRadius ?? 8}</span>px
            </label>
            <input type="range" min="0" max="24" value="${s.borderRadius ?? 8}"
                   oninput="document.getElementById('sp-dw-br').textContent=this.value;updateWidgetStyle('borderRadius',parseInt(this.value))"
                   style="width:100%;margin-bottom:4px">
            ${widget.type === 'gantt' ? `
            <p style="color:#9ca3af;font-size:10px;font-weight:600;letter-spacing:.5px;margin:12px 0 8px">VIEW MODE</p>
            <div style="display:flex;gap:4px;margin-bottom:14px">
                <button onclick="updateWidgetStyle('viewMode','day');renderSettingsPanel()"
                    style="flex:1;background:${(s.viewMode||'month')==='day'?'#4f46e5':'#374151'};color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px">Day</button>
                <button onclick="updateWidgetStyle('viewMode','week');renderSettingsPanel()"
                    style="flex:1;background:${(s.viewMode||'month')==='week'?'#4f46e5':'#374151'};color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px">Week</button>
                <button onclick="updateWidgetStyle('viewMode','month');renderSettingsPanel()"
                    style="flex:1;background:${(s.viewMode||'month')==='month'?'#4f46e5':'#374151'};color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px">Month</button>
            </div>
            <p style="color:#9ca3af;font-size:10px;font-weight:600;letter-spacing:.5px;margin:0 0 8px">SHOW COLUMNS</p>
            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:6px;cursor:pointer">
                <input type="checkbox" ${s.showStart !== false ? 'checked' : ''}
                       onchange="updateWidgetStyle('showStart', this.checked)" style="cursor:pointer">
                วันเริ่ม
            </label>
            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:6px;cursor:pointer">
                <input type="checkbox" ${s.showEnd !== false ? 'checked' : ''}
                       onchange="updateWidgetStyle('showEnd', this.checked)" style="cursor:pointer">
                วันสิ้นสุด
            </label>
            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:6px;cursor:pointer">
                <input type="checkbox" ${s.showPct !== false ? 'checked' : ''}
                       onchange="updateWidgetStyle('showPct', this.checked)" style="cursor:pointer">
                เปอร์เซ็นต์ (%)
            </label>
            <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:10px;cursor:pointer">
                <input type="checkbox" ${s.showStatus !== false ? 'checked' : ''}
                       onchange="updateWidgetStyle('showStatus', this.checked)" style="cursor:pointer">
                สถานะ
            </label>
            ` : ''}
        `;
        return;
    }

    const s            = widget.style || {};
    const isLine       = widget.type === 'line';
    const isOldArrow   = widget.type === 'arrow';
    const isSolidArrow = ['arrow-right','arrow-left','arrow-up','arrow-down','double-arrow'].includes(widget.type);
    const isArrowLike  = isLine || isOldArrow || isSolidArrow;
    const isImage      = widget.type === 'image';
    const isTextbox    = widget.type === 'textbox';
    const isFillShape  = ['rectangle','circle','rounded-rectangle','triangle','diamond',
                           'pentagon','hexagon','star'].includes(widget.type);
    const isFillTrans  = !!s.fillTransparent;

    const transparentFillRow = isFillShape ? `
        <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:10px;cursor:pointer">
            <input type="checkbox" ${isFillTrans ? 'checked' : ''}
                   onchange="updateWidgetStyle('fillTransparent', this.checked)"
                   style="cursor:pointer;width:13px;height:13px">
            Transparent Fill
        </label>` : '';

    const fillRow = (!isImage && !isTextbox) ? `
        <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">
            ${isArrowLike ? 'Color' : 'Fill Color'}
        </label>
        <input type="color" value="${s.fill || (isArrowLike ? '#374151' : '#6366f1')}"
               oninput="updateWidgetStyle('fill', this.value)"
               ${isFillTrans ? 'disabled' : ''}
               style="width:100%;height:28px;border:none;border-radius:4px;margin-bottom:10px;
                      cursor:${isFillTrans ? 'not-allowed' : 'pointer'};
                      opacity:${isFillTrans ? '0.4' : '1'}">` : '';

    const borderColorRow = (!isArrowLike && !isTextbox) ? `
        <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">Border Color</label>
        <input type="color" value="${s.borderColor || '#4f46e5'}"
               oninput="updateWidgetStyle('borderColor', this.value)"
               style="width:100%;height:28px;border:none;border-radius:4px;margin-bottom:10px;cursor:pointer">` : '';

    const bwLabel = isArrowLike ? 'Thickness' : 'Border Width';
    const bwVal   = s.borderWidth ?? (isArrowLike ? 4 : 2);
    const thicknessRow = !isTextbox ? `
        <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">
            ${bwLabel}: <span id="sp-bw">${bwVal}</span>px
        </label>
        <input type="range" min="0" max="20" value="${bwVal}"
               oninput="document.getElementById('sp-bw').textContent=this.value;updateWidgetStyle('borderWidth',+this.value)"
               style="width:100%;margin-bottom:6px">
        ${!isArrowLike ? `<button onclick="updateWidgetStyle('borderWidth',0);renderSettingsPanel()"
               style="width:100%;background:#374151;color:#d1d5db;border:none;
                      padding:6px;border-radius:4px;cursor:pointer;font-size:11px;
                      margin-bottom:10px">No Border</button>` : ''}` : '';

    const brVal = s.borderRadius ?? 0;
    const showRadius = ['rectangle','circle','rounded-rectangle','image'].includes(widget.type);
    const radiusRow = showRadius ? `
        <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">
            Border Radius: <span id="sp-br">${brVal}</span>px
        </label>
        <input type="range" min="0" max="${widget.type === 'circle' ? 999 : 100}" value="${brVal}"
               oninput="document.getElementById('sp-br').textContent=this.value;updateWidgetStyle('borderRadius',+this.value)"
               style="width:100%;margin-bottom:4px"
               ${widget.type === 'circle' ? 'disabled' : ''}>
        ${widget.type === 'circle'
            ? '<p style="color:#6b7280;font-size:10px;margin:0 0 6px">Circle is always fully rounded</p>'
            : ''}` : '';

    const shadowRow = `
        <label style="display:flex;align-items:center;gap:8px;color:#d1d5db;font-size:11px;margin-bottom:6px;cursor:pointer">
            <input type="checkbox" ${s.shadow ? 'checked' : ''}
                   onchange="updateWidgetStyle('shadow', this.checked)"
                   style="cursor:pointer;width:13px;height:13px">
            Drop Shadow
        </label>`;

    const textboxRow = isTextbox ? `
        <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">
            Font Size: <span id="sp-fs">${s.fontSize ?? 16}</span>px
        </label>
        <input type="range" min="10" max="72" value="${s.fontSize ?? 16}"
               oninput="document.getElementById('sp-fs').textContent=this.value;updateWidgetStyle('fontSize',parseInt(this.value))"
               style="width:100%;margin-bottom:10px">
        <label style="display:block;color:#d1d5db;font-size:11px;margin-bottom:4px">Font Color</label>
        <input type="color" value="${s.fontColor || '#1f2937'}"
               oninput="updateWidgetStyle('fontColor', this.value)"
               style="width:100%;height:28px;border:none;border-radius:4px;margin-bottom:10px;cursor:pointer">
        <div style="display:flex;gap:6px;margin-bottom:10px">
            <button onclick="updateWidgetStyle('fontWeight','${s.fontWeight==='bold'?'normal':'bold'}');renderSettingsPanel()"
                style="flex:1;background:${s.fontWeight==='bold'?'#4f46e5':'#374151'};
                       color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-weight:bold;font-size:12px">B</button>
            <button onclick="updateWidgetStyle('textAlign','left');renderSettingsPanel()"
                style="flex:1;background:${!s.textAlign||s.textAlign==='left'?'#4f46e5':'#374151'};
                       color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px">L</button>
            <button onclick="updateWidgetStyle('textAlign','center');renderSettingsPanel()"
                style="flex:1;background:${s.textAlign==='center'?'#4f46e5':'#374151'};
                       color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px">C</button>
            <button onclick="updateWidgetStyle('textAlign','right');renderSettingsPanel()"
                style="flex:1;background:${s.textAlign==='right'?'#4f46e5':'#374151'};
                       color:white;border:none;padding:6px;border-radius:4px;cursor:pointer;font-size:11px">R</button>
        </div>` : '';

    panel.style.display = 'block';
    panel.innerHTML = `
        <p style="color:#9ca3af;font-size:10px;font-weight:600;letter-spacing:.5px;margin:0 0 10px">SETTINGS</p>
        ${textboxRow}
        ${transparentFillRow}
        ${fillRow}
        ${borderColorRow}
        ${thicknessRow}
        ${radiusRow}
        ${shadowRow}
    `;
}

function updateWidgetStyle(key, value) {
    const widget = widgets.find(w => w.id === selectedWidgetId);
    if (!widget) return;
    if (!widget.style) widget.style = {};
    widget.style[key] = value;

    if (widget.type === 'connector') {
        updateConnectorEl(widget.id);
        return;
    }

    const el = document.getElementById('widget-' + widget.id);
    if (!el) return;

    const DATA_WIDGET_TYPES = ['kpi','chart','gantt','milestone','team','blocker'];
    const isDataWidget = DATA_WIDGET_TYPES.includes(widget.type);

    if (key === 'shadow') {
        const content = el.querySelector('.widget-content');
        if (content) content.style.filter = value ? 'drop-shadow(0 6px 10px rgba(0,0,0,.3))' : 'none';
        return;
    }

    if (isDataWidget && key === 'borderColor') {
        el.style.borderColor = value;
        return;
    }

    if (isDataWidget && key === 'borderRadius') {
        el.style.borderRadius = value + 'px';
        return;
    }

    const content = el.querySelector('.widget-content');
    if (content) content.innerHTML = renderWidgetContent(widget);
}

// ── Save ───────────────────────────────────────────────────────────────────
async function saveReport() {
    flushCurrentSlide();
    const statusEl = document.getElementById('save-status');
    statusEl.textContent = 'Saving…';

    const payload = {
        title: document.getElementById('report-title').value,
        slides: slides.map((s, i) => ({
            id:           s.id,
            slide_order:  i,
            bg_color:     s.bg_color || '#ffffff',
            notes:        s.notes   || '',
            html_content: s.html_content || '',
            widgets:      s.widgets_data || [],
            elements:     [],
        })),
    };

    try {
        const res  = await fetch(SAVE_URL, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.success) {
            json.slides.forEach((s, i) => { if (slides[i]) slides[i].id = s.id; });
            renderSlideList();
            statusEl.textContent = '✓ Saved';
            setTimeout(() => { statusEl.textContent = ''; }, 2000);
        } else {
            statusEl.textContent = '✗ Error';
            console.error(json.error);
        }
    } catch(e) {
        statusEl.textContent = '✗ Error';
        console.error(e);
    }
}

document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); saveReport(); }
});

// ── Boot ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    slides = (REPORT_DATA.slides || []).map(s => ({
        ...s,
        widgets_data: Array.isArray(s.widgets_data) ? s.widgets_data : [],
    }));
    if (!slides.length) {
        slides.push({ id:'new_1', slide_order:0, bg_color:'#ffffff', notes:'', html_content:'', widgets_data:[] });
    }

    await initEditor(slides[0].html_content || '');
    widgets = (slides[0].widgets_data || []).map(w => ({ ...w }));
    renderAllWidgets();
    renderSlideList();
});
</script>
</body>
</html>
