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
        style: { shadow: false, borderColor: '#e5e7eb', borderRadius: 8 },
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
    widgets.forEach(w => overlay.appendChild(buildWidgetEl(w)));
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

    return el;
}

function deselectCurrent() {
    if (!selectedWidgetId) return;
    const prev = document.getElementById('widget-' + selectedWidgetId);
    if (prev) {
        const prevW = widgets.find(w => w.id === selectedWidgetId);
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

    const el = document.getElementById('widget-' + id);
    if (!el) return;
    el.style.border = '2px solid #2563eb';
    el.style.zIndex = '10';

    const widget = widgets.find(w => w.id === id);
    el.style.boxShadow = '0 0 0 3px rgba(37,99,235,.15)';

    if (!widget) return;

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
    widgets = widgets.filter(w => w.id !== id);
    selectedWidgetId = null;
    const el = document.getElementById('widget-' + id);
    if (el) el.remove();
    document.getElementById('settings-panel').style.display = 'none';
}

// Deselect on outside click
document.addEventListener('mousedown', e => {
    if (!e.target.closest('#widget-overlay') && !e.target.closest('#settings-panel')) {
        if (selectedWidgetId) {
            deselectCurrent();
            document.getElementById('settings-panel').style.display = 'none';
        }
    }
});

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
            const cards = [
                { label:'Total Tasks', value:k.total,            color:'#4f46e5', bg:'#f5f3ff' },
                { label:'Done',        value:k.done,             color:'#16a34a', bg:'#f0fdf4' },
                { label:'In Progress', value:k.in_progress,      color:'#2563eb', bg:'#eff6ff' },
                { label:'Overdue',     value:k.overdue,          color:'#dc2626', bg:'#fef2f2' },
                { label:'Progress',    value:k.progress_pct+'%', color:'#0891b2', bg:'#ecfeff' },
                { label:'Members',     value:k.members,          color:'#7c3aed', bg:'#faf5ff' },
            ];
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;height:100%;padding:2px">` +
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
            return `<div style="height:100%;display:flex;flex-direction:column;padding:4px">
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
            const lW = 130, rowH = 26, hdrH = 22;
            const svgH = hdrH + tasks.length * rowH;
            const chartW = 700 - lW;
            const sc = { todo:'#6b7280',in_progress:'#2563eb',review:'#d97706',done:'#16a34a',cancelled:'#dc2626' };
            let s = `<svg width="100%" height="${svgH}" viewBox="0 0 700 ${svgH}" xmlns="http://www.w3.org/2000/svg"
                          preserveAspectRatio="none" style="font-family:sans-serif;display:block">`;
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
            return `<div style="height:100%;overflow:auto">
                <div style="font-size:10px;font-weight:700;color:#374151;margin-bottom:8px;padding:2px 0">🎯 Milestones</div>
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
            return `<div style="height:100%;overflow:auto">
                <div style="font-size:10px;font-weight:700;color:#374151;margin-bottom:8px;padding:2px 0">👥 Team Members</div>
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
            return `<div style="height:100%;overflow:auto">
                <div style="font-size:10px;font-weight:700;color:#dc2626;margin-bottom:8px;padding:2px 0">🚨 Active Blockers</div>
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
    const isDataWidget = !!(widget && DATA_WIDGET_TYPES.includes(widget.type));

    if (!widget || (!SHAPE_STYLE_TYPES.includes(widget.type) && !isDataWidget)) {
        panel.style.display = 'none';
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
