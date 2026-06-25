@extends('layouts.app')

@section('title', $report->title . ' — Builder')

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* Remove main layout padding so builder is truly full-screen */
main { padding:0 !important; overflow:hidden !important; }

/* ── Layout ── */
#report-builder { display:flex; flex-direction:column; height:100%; overflow:hidden; }
.builder-ribbon { flex-shrink:0; height:50px; display:flex; align-items:center; gap:2px; padding:0 8px;
    background:#1e1e2e; border-bottom:1px solid #2d2d44; overflow-x:auto; overflow-y:hidden; scrollbar-width:none; }
.builder-ribbon::-webkit-scrollbar { display:none; }
.builder-body { flex:1; display:flex; overflow:hidden; }

/* ── Slide Panel ── */
.slide-panel { width:156px; flex-shrink:0; overflow-y:auto; background:#16162a;
    border-right:1px solid #2d2d44; padding:8px; scrollbar-width:thin; scrollbar-color:#333 transparent; }
.slide-thumb { position:relative; width:132px; height:100px; cursor:pointer; border-radius:6px;
    overflow:hidden; border:2px solid transparent; margin-bottom:8px; transition:border-color .15s; background:#fff; }
.slide-thumb.active { border-color:#6366f1; }
.slide-thumb-num { position:absolute; bottom:2px; left:0; right:0; text-align:center;
    font-size:9px; color:#888; pointer-events:none; }

/* ── Canvas Area ── */
.canvas-area { flex:1; overflow:auto; background:#111122; display:flex;
    justify-content:center; padding:32px 32px 80px; }
.canvas-wrap { flex-shrink:0; }

/* ── Slide Canvas ── */
.slide-canvas {
    background:#fff; outline:none; cursor:text;
    box-shadow:0 8px 40px rgba(0,0,0,.55);
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    font-size:14px; line-height:1.65; color:#1a1a1a;
    word-wrap:break-word; overflow-wrap:break-word;
    transition:box-shadow .2s;
}
.slide-canvas:focus { box-shadow:0 8px 40px rgba(99,102,241,.45), 0 0 0 2px rgba(99,102,241,.25); }
.slide-canvas p  { margin:0 0 6px; min-height:1.5em; }
.slide-canvas h1 { font-size:2em; font-weight:700; margin:16px 0 10px; line-height:1.25; }
.slide-canvas h2 { font-size:1.5em; font-weight:600; margin:14px 0 8px; line-height:1.3; }
.slide-canvas h3 { font-size:1.25em; font-weight:600; margin:12px 0 6px; line-height:1.35; }
.slide-canvas h4 { font-size:1.1em; font-weight:600; margin:10px 0 5px; }
.slide-canvas ul, .slide-canvas ol { padding-left:28px; margin:6px 0; }
.slide-canvas li { margin:2px 0; }
.slide-canvas table { width:100%; border-collapse:collapse; margin:10px 0; }
.slide-canvas td, .slide-canvas th { border:1px solid #d1d5db; padding:8px 12px; text-align:left; min-width:60px; }
.slide-canvas th { background:#f3f4f6; font-weight:600; }
.slide-canvas hr { border:none; border-top:2px solid #e5e7eb; margin:18px 0; }
.slide-canvas img { max-width:100%; height:auto; border-radius:4px; display:block; margin:6px 0; }
.slide-canvas blockquote { border-left:4px solid #6366f1; margin:10px 0; padding:6px 16px; background:#f5f3ff; border-radius:0 6px 6px 0; }
.slide-canvas a { color:#6366f1; text-decoration:underline; }

/* ── Widget ── */
.report-widget { display:block; border:2px dashed #6366f1; border-radius:8px; margin:10px 0;
    background:#fff; position:relative; cursor:pointer; }
.report-widget.selected { border-color:#2563eb; border-style:solid; }
.report-widget:hover { border-color:#4f46e5; }
.widget-toolbar { display:none; position:absolute; top:-36px; left:0; z-index:200;
    background:#1f2937; border-radius:6px; padding:4px 8px; align-items:center; gap:6px;
    white-space:nowrap; box-shadow:0 2px 10px rgba(0,0,0,.5); }
.widget-toolbar.show { display:flex !important; }
.widget-tb-btn { background:#374151; border:none; color:#e5e7eb; padding:3px 10px;
    border-radius:4px; font-size:11px; cursor:pointer; }
.widget-tb-btn:hover { background:#4b5563; }
.widget-tb-del { background:#dc2626; }
.widget-tb-del:hover { background:#b91c1c; }

/* ── Ribbon Buttons ── */
.rb-btn { display:inline-flex; align-items:center; gap:2px; padding:3px 7px; border-radius:5px;
    font-size:11px; color:#ccc; cursor:pointer; border:none; background:transparent;
    white-space:nowrap; transition:background .12s; flex-shrink:0; }
.rb-btn:hover { background:#2d2d44; color:#fff; }
.rb-btn:disabled { opacity:.4; cursor:not-allowed; }
.rb-sep { width:1px; height:22px; background:#2d2d44; margin:0 3px; flex-shrink:0; }
.rb-select { background:#252535; color:#ccc; font-size:11px; border:1px solid #3d3d5a;
    border-radius:5px; padding:3px 6px; cursor:pointer; flex-shrink:0; outline:none; }
.rb-select:focus { border-color:#6366f1; }

/* ── Floating Toolbar ── */
#floating-tb { pointer-events:auto; user-select:none; }
.ftb-btn { width:26px; height:26px; border-radius:4px; border:none; background:transparent;
    color:#e5e7eb; cursor:pointer; font-size:12px; display:inline-flex; align-items:center;
    justify-content:center; flex-shrink:0; transition:background .1s; }
.ftb-btn:hover { background:#374151; }
.ftb-sel { background:#1f2937; color:#e5e7eb; font-size:11px; border:1px solid #374151;
    border-radius:4px; padding:2px 4px; cursor:pointer; outline:none; }

/* ── Table Picker ── */
.tp-cell { width:19px; height:19px; border-radius:2px; cursor:pointer; border:1px solid #d1d5db; transition:background .08s; }
.tp-cell.on { background:#c7d2fe; border-color:#6366f1; }

/* ── Save dot ── */
.save-dot { width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0; }
.save-dot.dirty  { background:#f59e0b; }
.save-dot.saved  { background:#22c55e; }
.save-dot.saving { background:#6366f1; animation:pulse 1s infinite; }
</style>
@endpush

@section('content')
<div id="report-builder" x-data="reportBuilder()" x-init="init()">

    {{-- ══ Ribbon ══ --}}
    <div class="builder-ribbon">
        <a href="{{ route('projects.reports.index', $project) }}" class="rb-btn" title="Back">
            <i class="ti ti-arrow-left"></i>
        </a>
        <span class="text-xs text-gray-400 font-medium max-w-[130px] truncate ml-1">{{ $report->title }}</span>
        <span class="save-dot ml-1" :class="isSaving?'saving':isDirty?'dirty':'saved'"></span>

        <div class="rb-sep"></div>

        <select class="rb-select" @change="execCmd('formatBlock',$event.target.value);$event.target.value=''" title="Paragraph style">
            <option value="">Style</option>
            <option value="p">Normal</option>
            <option value="h1">H1</option>
            <option value="h2">H2</option>
            <option value="h3">H3</option>
            <option value="h4">H4</option>
            <option value="blockquote">Quote</option>
            <option value="pre">Code</option>
        </select>

        <div class="rb-sep"></div>

        <button class="rb-btn font-bold" @click="execCmd('bold')" title="Bold (Ctrl+B)">B</button>
        <button class="rb-btn italic"    @click="execCmd('italic')" title="Italic (Ctrl+I)">I</button>
        <button class="rb-btn underline" @click="execCmd('underline')" title="Underline (Ctrl+U)">U</button>
        <button class="rb-btn" @click="execCmd('strikeThrough')" style="text-decoration:line-through" title="Strikethrough">S</button>

        <div class="relative" title="Text Color">
            <button class="rb-btn">A <span style="display:inline-block;width:10px;height:3px;background:#ef4444;margin-bottom:1px"></span></button>
            <input type="color" value="#ef4444" @input="execCmd('foreColor',$event.target.value)"
                   class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
        </div>
        <div class="relative" title="Highlight Color">
            <button class="rb-btn">H <span style="display:inline-block;width:10px;height:3px;background:#fef08a;margin-bottom:1px"></span></button>
            <input type="color" value="#fef08a" @input="execCmd('backColor',$event.target.value)"
                   class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
        </div>

        <div class="rb-sep"></div>

        <button class="rb-btn" @click="execCmd('justifyLeft')"   title="Align Left"><i class="ti ti-align-left"></i></button>
        <button class="rb-btn" @click="execCmd('justifyCenter')" title="Align Center"><i class="ti ti-align-center"></i></button>
        <button class="rb-btn" @click="execCmd('justifyRight')"  title="Align Right"><i class="ti ti-align-right"></i></button>
        <button class="rb-btn" @click="execCmd('insertUnorderedList')" title="Bullet List"><i class="ti ti-list"></i></button>
        <button class="rb-btn" @click="execCmd('insertOrderedList')"   title="Numbered List"><i class="ti ti-list-numbers"></i></button>

        <div class="rb-sep"></div>

        <button class="rb-btn" @click="insertImageFromFile()" title="Insert Image"><i class="ti ti-photo"></i> Image</button>
        <button class="rb-btn" @click="insertLink()" title="Insert Link"><i class="ti ti-link"></i></button>

        {{-- Table Picker --}}
        <div class="relative" x-data="{ open:false, hr:1, hc:1 }">
            <button class="rb-btn" @mousedown.prevent @click="open=!open" title="Insert Table">
                <i class="ti ti-table"></i> Table
            </button>
            <div x-show="open" @click.outside="open=false" x-cloak
                 class="absolute top-11 left-0 bg-white border border-gray-200 rounded-xl shadow-xl p-3 z-50 w-40">
                <p class="text-xs text-gray-400 text-center mb-2" x-text="hr+'×'+hc"></p>
                <div class="grid gap-0.5" style="grid-template-columns:repeat(6,1fr)">
                    <template x-for="cell in tableCells" :key="cell.key">
                        <div @mousedown.prevent @click="insertTable(cell.r,cell.c);open=false"
                             @mouseenter="hr=cell.r;hc=cell.c"
                             :class="cell.r<=hr&&cell.c<=hc?'on':''"
                             class="tp-cell"></div>
                    </template>
                </div>
            </div>
        </div>

        <button class="rb-btn" @mousedown.prevent @click="insertDivider()" title="Horizontal Divider">
            <i class="ti ti-minus"></i>
        </button>

        <div class="rb-sep"></div>

        <span class="text-xs text-gray-600 mr-0.5">Widgets:</span>
        <button class="rb-btn" @mousedown.prevent @click="insertWidget('kpi')"><i class="ti ti-chart-bar"></i> KPI</button>
        <button class="rb-btn" @mousedown.prevent @click="insertWidget('chart')"><i class="ti ti-chart-donut"></i> Chart</button>
        <button class="rb-btn" @mousedown.prevent @click="insertWidget('gantt')"><i class="ti ti-calendar-stats"></i> Gantt</button>
        <button class="rb-btn" @mousedown.prevent @click="insertWidget('milestone')"><i class="ti ti-flag"></i> Milestone</button>
        <button class="rb-btn" @mousedown.prevent @click="insertWidget('team')"><i class="ti ti-users"></i> Team</button>
        <button class="rb-btn" @mousedown.prevent @click="insertWidget('blocker')"><i class="ti ti-alert-triangle"></i> Blocker</button>

        <div class="flex-1"></div>

        <select class="rb-select mr-1" x-model="slideLayout" title="Canvas Size">
            <option value="a4">A4 Portrait</option>
            <option value="slide">16:9 Slide</option>
            <option value="wide">Wide</option>
        </select>

        <div class="rb-sep"></div>

        <button class="rb-btn" @click="save()" :disabled="isSaving"><i class="ti ti-device-floppy"></i> Save</button>
        <a href="{{ route('projects.reports.preview', [$project, $report]) }}" target="_blank" class="rb-btn"><i class="ti ti-eye"></i> Preview</a>
        <a href="{{ route('projects.reports.export',  [$project, $report]) }}" target="_blank" class="rb-btn"><i class="ti ti-printer"></i> Export</a>

        <div class="relative" x-data="{ open:false }">
            <button class="rb-btn" @click="open=!open"><i class="ti ti-dots-vertical"></i></button>
            <div x-show="open" @click.outside="open=false" x-cloak
                 class="absolute right-0 top-11 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50 py-1">
                <button @click="openTemplateSave();open=false"
                        class="w-full text-left px-4 py-2 text-xs text-gray-300 hover:bg-gray-700">
                    <i class="ti ti-template mr-1"></i> Save as Template
                </button>
            </div>
        </div>
    </div>

    {{-- ══ Body ══ --}}
    <div class="builder-body">

        {{-- Slide Panel --}}
        <div class="slide-panel">
            <div class="text-xs text-gray-500 mb-2 font-medium">Slides</div>
            <template x-for="(slide, idx) in slides" :key="slide.id">
                <div class="relative group">
                    <div class="slide-thumb" :class="idx===currentSlideIndex?'active':''"
                         @click="switchSlide(idx)">
                        <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;background:#fff">
                            <div style="transform:scale(0.165);transform-origin:top left;width:794px;padding:40px;font-family:sans-serif;font-size:14px;line-height:1.6;color:#1a1a1a"
                                 x-html="slide.html_content||'<p style=\'color:#ccc;font-size:12px\'>Empty</p>'"></div>
                        </div>
                        <div class="slide-thumb-num" x-text="idx+1"></div>
                    </div>
                    <div class="hidden group-hover:flex absolute top-1 right-1 gap-1 z-10">
                        <button @click.stop="duplicateSlide(idx)"
                                class="w-5 h-5 rounded bg-gray-700 text-gray-300 hover:bg-gray-600 flex items-center justify-center" title="Duplicate">
                            <i class="ti ti-copy" style="font-size:9px"></i>
                        </button>
                        <button @click.stop="deleteSlide(idx)" x-show="slides.length>1"
                                class="w-5 h-5 rounded bg-red-800 text-red-300 hover:bg-red-700 flex items-center justify-center" title="Delete">
                            <i class="ti ti-trash" style="font-size:9px"></i>
                        </button>
                    </div>
                </div>
            </template>
            <button @click="addSlide()"
                    class="w-full mt-1 py-2 rounded-lg border border-dashed border-gray-600 text-gray-500 hover:border-indigo-500 hover:text-indigo-400 text-xs transition-colors">
                + Add Slide
            </button>
        </div>

        {{-- Canvas Area --}}
        <div class="canvas-area" @click.self="$refs.slideCanvas&&$refs.slideCanvas.focus()">
            <div class="canvas-wrap">
                <div class="slide-canvas"
                     contenteditable="true"
                     spellcheck="false"
                     x-ref="slideCanvas"
                     @input="onSlideInput()"
                     @blur="saveSelection()"
                     @mouseup="onSelectionChange()"
                     @keyup="onSelectionChange()"
                     @keydown="onCanvasKeydown($event)"
                     @click.stop="onSelectionChange();deselectAllWidgets()"
                     @dragover.prevent
                     @drop.prevent="handleDrop($event)"
                     :style="canvasStyle">
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Floating Toolbar ══ --}}
    <div id="floating-tb" x-show="showToolbar" x-cloak
         class="fixed z-[200] bg-gray-900 rounded-lg shadow-2xl flex items-center gap-0.5 px-2 py-1.5 border border-gray-700"
         :style="`top:${toolbarY}px;left:${toolbarX}px`"
         @mousedown.prevent>
        <button @click="execCmd('bold')"          class="ftb-btn font-bold" title="Bold">B</button>
        <button @click="execCmd('italic')"        class="ftb-btn italic"    title="Italic">I</button>
        <button @click="execCmd('underline')"     class="ftb-btn underline" title="Underline">U</button>
        <button @click="execCmd('strikeThrough')" class="ftb-btn" style="text-decoration:line-through" title="Strike">S</button>
        <div class="w-px h-4 bg-gray-700 mx-0.5"></div>
        <select @change="execCmd('fontSize',$event.target.value)" class="ftb-sel" title="Size">
            <option value="1">8</option><option value="2">10</option><option value="3" selected>12</option>
            <option value="4">14</option><option value="5">18</option><option value="6">24</option><option value="7">36</option>
        </select>
        <select @change="execCmd('formatBlock',$event.target.value)" class="ftb-sel ml-0.5" title="Style">
            <option value="p">Normal</option><option value="h1">H1</option><option value="h2">H2</option><option value="h3">H3</option>
        </select>
        <div class="w-px h-4 bg-gray-700 mx-0.5"></div>
        <div class="relative" title="Text Color">
            <button class="ftb-btn text-xs">A</button>
            <input type="color" @input="execCmd('foreColor',$event.target.value)" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
        </div>
        <div class="relative" title="Highlight">
            <button class="ftb-btn text-xs" style="background:#fef08a;color:#1a1a1a;border-radius:3px;width:auto;padding:0 4px">H</button>
            <input type="color" @input="execCmd('backColor',$event.target.value)" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer">
        </div>
        <div class="w-px h-4 bg-gray-700 mx-0.5"></div>
        <button @click="execCmd('justifyLeft')"   class="ftb-btn" title="Left"><i class="ti ti-align-left"></i></button>
        <button @click="execCmd('justifyCenter')" class="ftb-btn" title="Center"><i class="ti ti-align-center"></i></button>
        <button @click="execCmd('justifyRight')"  class="ftb-btn" title="Right"><i class="ti ti-align-right"></i></button>
        <div class="w-px h-4 bg-gray-700 mx-0.5"></div>
        <button @click="execCmd('insertUnorderedList')" class="ftb-btn" title="Bullet"><i class="ti ti-list"></i></button>
        <button @click="execCmd('insertOrderedList')"   class="ftb-btn" title="Numbered"><i class="ti ti-list-numbers"></i></button>
        <div class="w-px h-4 bg-gray-700 mx-0.5"></div>
        <button @click="insertLink()" class="ftb-btn" title="Link"><i class="ti ti-link"></i></button>
    </div>

    {{-- ══ Save Toast ══ --}}
    <div x-show="saveToast" x-cloak x-transition
         class="fixed bottom-6 right-6 z-[300] flex items-center gap-3 bg-gray-900 text-white text-sm px-5 py-3 rounded-xl shadow-2xl border border-green-500/30">
        <span class="text-green-400 text-base">✓</span> Saved successfully
    </div>
    <div x-show="saveError" x-cloak x-transition
         class="fixed bottom-6 right-6 z-[300] flex items-center gap-3 bg-red-900 text-white text-sm px-5 py-3 rounded-xl shadow-2xl border border-red-500/30">
        <span class="text-red-300 text-base">✕</span> <span x-text="saveError"></span>
        <button @click="saveError=''" class="ml-2 text-red-300 hover:text-white">✕</button>
    </div>

    {{-- ══ Template Save Modal ══ --}}
    <div x-show="showTemplateSave" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         @keydown.escape.window="showTemplateSave=false">
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-80 shadow-2xl">
            <h3 class="text-sm font-semibold text-gray-200 mb-4">Save as Template</h3>
            <input type="text" x-model="templateName" placeholder="Template name"
                   class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-gray-200 text-sm mb-4 outline-none focus:border-indigo-500">
            <div class="flex gap-3">
                <button @click="showTemplateSave=false"
                        class="flex-1 py-2 rounded-lg border border-gray-600 text-xs text-gray-400 hover:bg-gray-700">Cancel</button>
                <button @click="doSaveAsTemplate()"
                        class="flex-1 py-2 rounded-lg bg-indigo-600 text-xs text-white hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@php
$reportJson = [
    'id'     => $report->id,
    'title'  => $report->title,
    'slides' => $report->slides->map(function($s) {
        return [
            'id'           => $s->id,
            'slide_order'  => $s->slide_order,
            'bg_color'     => $s->bg_color,
            'notes'        => $s->notes ?? '',
            'html_content' => $s->html_content ?? '',
        ];
    })->values(),
];
@endphp
<script>
const REPORT_DATA  = @json($reportJson);
const PROJECT_KPI  = @json($kpi);
const CHART_DATA   = @json($chartData);
const PROJECT_DATA = @json($projectData);
const SAVE_URL     = '{{ route('projects.reports.save', [$project, $report]) }}';
const TEMPLATE_URL = '{{ route('projects.reports.save-as-template', [$project, $report]) }}';

/* ── Global widget helpers (available to onclick attributes in saved HTML) ── */
window.__selectWidget = function(el) {
    document.querySelectorAll('.report-widget').forEach(w => {
        w.classList.remove('selected');
        const tb = w.querySelector('.widget-toolbar');
        if (tb) tb.classList.remove('show');
    });
    el.classList.add('selected');
    const tb = el.querySelector('.widget-toolbar');
    if (tb) tb.classList.add('show');
};

window.__deleteWidget = function(btn) {
    const widget = btn.closest('.report-widget');
    if (!widget) return;
    if (confirm('Delete this widget?')) {
        const next = widget.nextElementSibling;
        widget.remove();
        if (next) {
            const range = document.createRange();
            range.setStart(next, 0);
            range.collapse(true);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
        window.__reportBuilder && window.__reportBuilder.onSlideInput();
    }
};

window.__refreshWidget = function(btn) {
    const widget = btn.closest('.report-widget');
    if (!widget || !window.__reportBuilder) return;
    window.__reportBuilder.renderWidget(widget.dataset.widgetId, widget.dataset.widgetType);
};

/* ── Click outside → deselect widgets ── */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.report-widget')) {
        document.querySelectorAll('.report-widget').forEach(w => {
            w.classList.remove('selected');
            const tb = w.querySelector('.widget-toolbar');
            if (tb) tb.classList.remove('show');
        });
    }
});

function reportBuilder() {
    return {
        slides: [],
        currentSlideIndex: 0,
        isDirty: false,
        isSaving: false,
        slideLayout: 'slide',
        showToolbar: false,
        toolbarX: 0,
        toolbarY: 0,
        showTemplateSave: false,
        templateName: '',
        saveToast: false,
        saveError: '',
        _chartInstances: {},
        _savedRange: null,
        tableCells: Array.from({length:36}, (_,i) => ({ r:Math.floor(i/6)+1, c:(i%6)+1, key:i })),

        get canvasStyle() {
            const base = { background:'#fff', outline:'none' };
            return {
                a4:    { ...base, width:'794px',  minHeight:'1123px', padding:'60px 72px' },
                slide: { ...base, width:'960px',  height:'540px',     padding:'48px 56px', overflow:'hidden' },
                wide:  { ...base, width:'1122px', minHeight:'794px',  padding:'56px 72px' },
            }[this.slideLayout] || { ...base, width:'794px', minHeight:'1123px', padding:'60px 72px' };
        },

        get currentSlide() { return this.slides[this.currentSlideIndex] || null; },

        // ── Init ──
        init() {
            window.__reportBuilder = this;
            this.slides = (REPORT_DATA.slides || []).map(s => ({ ...s }));
            if (!this.slides.length) this.addSlide();
            this.$nextTick(() => this.loadSlideToCanvas());
            window.addEventListener('keydown', e => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); this.save(); }
            });
            document.addEventListener('mousedown', e => {
                const tb = document.getElementById('floating-tb');
                if (tb && !tb.contains(e.target)) {
                    const sel = window.getSelection();
                    if (!sel || sel.isCollapsed) this.showToolbar = false;
                }
            });
            setInterval(() => { if (this.isDirty && !this.isSaving) this.save(); }, 90000);
        },

        // ── Canvas I/O ──
        loadSlideToCanvas() {
            const c = this.$refs.slideCanvas;
            if (!c) return;
            const html = this.currentSlide?.html_content || '';
            c.innerHTML = html;
            if (!html) {
                c.focus();
                const r = document.createRange();
                r.setStart(c, 0); r.collapse(true);
                window.getSelection()?.removeAllRanges();
                window.getSelection()?.addRange(r);
            }
            this.$nextTick(() => this.renderWidgets());
        },

        saveCurrentCanvas() {
            const c = this.$refs.slideCanvas;
            if (!c || !this.currentSlide) return;
            // Hide all widget toolbars before saving
            c.querySelectorAll('.widget-toolbar').forEach(tb => tb.classList.remove('show'));
            c.querySelectorAll('.report-widget').forEach(w => w.classList.remove('selected'));
            this.currentSlide.html_content = c.innerHTML;
        },

        onSlideInput() {
            const c = this.$refs.slideCanvas;
            if (c && this.currentSlide) {
                this.currentSlide.html_content = c.innerHTML;
                this.isDirty = true;
            }
        },

        // ── Selection save/restore ──
        saveSelection() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const canvas = this.$refs.slideCanvas;
                const range = sel.getRangeAt(0);
                if (canvas && canvas.contains(range.commonAncestorContainer)) {
                    this._savedRange = range.cloneRange();
                }
            }
        },

        restoreSelection() {
            const canvas = this.$refs.slideCanvas;
            if (!canvas) return;
            // If selection is already inside canvas (e.g. @mousedown.prevent kept focus),
            // don't disturb it — just ensure canvas has focus
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && canvas.contains(sel.anchorNode)) {
                canvas.focus();
                return;
            }
            canvas.focus();
            if (this._savedRange) {
                try {
                    sel.removeAllRanges();
                    sel.addRange(this._savedRange);
                } catch(e) {
                    this._placeCaretAtEnd(canvas);
                }
            } else {
                this._placeCaretAtEnd(canvas);
            }
        },

        _placeCaretAtEnd(el) {
            const range = document.createRange();
            range.selectNodeContents(el);
            range.collapse(false);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        },

        // ── Slide management ──
        switchSlide(idx) {
            if (idx === this.currentSlideIndex) return;
            this.saveCurrentCanvas();
            this.currentSlideIndex = idx;
            this.showToolbar = false;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        addSlide() {
            this.saveCurrentCanvas();
            const slide = { id:'new_'+Date.now(), slide_order:this.slides.length, bg_color:'#ffffff', notes:'', html_content:'' };
            this.slides.push(slide);
            this.currentSlideIndex = this.slides.length - 1;
            this.isDirty = true;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        deleteSlide(idx) {
            if (this.slides.length <= 1) return;
            this.slides.splice(idx, 1);
            if (this.currentSlideIndex >= this.slides.length) this.currentSlideIndex = this.slides.length - 1;
            this.isDirty = true;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        duplicateSlide(idx) {
            this.saveCurrentCanvas();
            const dup = { ...JSON.parse(JSON.stringify(this.slides[idx])), id:'new_'+Date.now() };
            this.slides.splice(idx+1, 0, dup);
            this.currentSlideIndex = idx + 1;
            this.isDirty = true;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        deselectAllWidgets() {
            document.querySelectorAll('.report-widget').forEach(w => {
                w.classList.remove('selected');
                w.querySelector('.widget-toolbar')?.classList.remove('show');
            });
        },

        // ── Selection / Floating Toolbar ──
        onSelectionChange() {
            const sel = window.getSelection();
            const canvas = this.$refs.slideCanvas;
            if (!sel || sel.isCollapsed || !sel.toString().trim() || !canvas?.contains(sel.anchorNode)) {
                this.showToolbar = false;
                return;
            }
            const rect = sel.getRangeAt(0).getBoundingClientRect();
            const tbW = 460;
            this.toolbarX = Math.max(8, Math.min(window.innerWidth - tbW - 8, rect.left + rect.width/2 - tbW/2));
            this.toolbarY = Math.max(8, rect.top - 54 + window.scrollY);
            this.showToolbar = true;
        },

        onCanvasKeydown(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                document.execCommand('insertText', false, '    ');
            }
        },

        // ── execCommand ──
        execCmd(cmd, val = null) {
            this.$refs.slideCanvas?.focus();
            document.execCommand(cmd, false, val);
            this.onSlideInput();
        },

        insertLink() {
            this.$refs.slideCanvas?.focus();
            const url = prompt('Enter URL:', 'https://');
            if (url?.trim()) { document.execCommand('createLink', false, url.trim()); this.onSlideInput(); }
        },

        // ── DOM-based range insertion helper ──
        _getInsertRange() {
            const canvas = this.$refs.slideCanvas;
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && canvas.contains(sel.anchorNode)) {
                return sel.getRangeAt(0);
            }
            // Fallback: restore saved or place at end
            if (this._savedRange) {
                try {
                    sel.removeAllRanges();
                    sel.addRange(this._savedRange);
                    return sel.getRangeAt(0);
                } catch(e) {}
            }
            const range = document.createRange();
            range.selectNodeContents(canvas);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
            return sel.getRangeAt(0);
        },

        // ── Image ──
        async compressImage(file) {
            return new Promise(resolve => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    URL.revokeObjectURL(url);
                    let [w, h] = [img.width, img.height];
                    if (w > 1920) { h = Math.round(h*1920/w); w = 1920; }
                    if (h > 1200) { w = Math.round(w*1200/h); h = 1200; }
                    const cv = document.createElement('canvas');
                    cv.width = w; cv.height = h;
                    cv.getContext('2d').drawImage(img, 0, 0, w, h);
                    cv.toBlob(resolve, file.type === 'image/png' ? 'image/png' : 'image/jpeg', 0.82);
                };
                img.src = url;
            });
        },

        async insertImageFromFile() {
            const input = document.createElement('input');
            input.type = 'file'; input.accept = 'image/*';
            input.onchange = async e => { if (e.target.files[0]) await this.insertImageFile(e.target.files[0]); };
            input.click();
        },

        async insertImageFile(file) {
            const blob = await this.compressImage(file);
            const reader = new FileReader();
            reader.onload = e => {
                this.restoreSelection();
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'max-width:100%;height:auto;border-radius:4px;display:block;margin:8px 0';
                const range = this._getInsertRange();
                range.deleteContents();
                range.insertNode(img);
                const p = document.createElement('p');
                p.innerHTML = '<br>';
                img.after(p);
                const newRange = document.createRange();
                newRange.setStart(p, 0); newRange.collapse(true);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(newRange);
                this.onSlideInput();
            };
            reader.readAsDataURL(blob);
        },

        async handleDrop(e) {
            const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            for (const f of files) await this.insertImageFile(f);
        },

        // ── Insert Table (DOM API) ──
        insertTable(rows = 3, cols = 3) {
            this.restoreSelection();
            const range = this._getInsertRange();

            const table = document.createElement('table');
            table.style.cssText = 'width:100%;border-collapse:collapse;margin:10px 0';

            for (let r = 0; r < rows; r++) {
                const tr = document.createElement('tr');
                for (let c = 0; c < cols; c++) {
                    const isHead = r === 0;
                    const cell = document.createElement(isHead ? 'th' : 'td');
                    cell.style.cssText = 'border:1px solid #d1d5db;padding:8px 12px;text-align:left;min-width:60px;' +
                        (isHead ? 'background:#f3f4f6;font-weight:600;' : '');
                    cell.innerHTML = isHead ? 'Header ' + (c + 1) : '';
                    tr.appendChild(cell);
                }
                table.appendChild(tr);
            }

            range.deleteContents();
            range.insertNode(table);

            const p = document.createElement('p');
            p.innerHTML = '<br>';
            table.after(p);

            const newRange = document.createRange();
            newRange.setStart(p, 0); newRange.collapse(true);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(newRange);

            this.onSlideInput();
        },

        // ── Insert Divider ──
        insertDivider() {
            this.restoreSelection();
            const range = this._getInsertRange();
            const hr = document.createElement('hr');
            hr.style.cssText = 'border:none;border-top:2px solid #e5e7eb;margin:18px 0';
            range.deleteContents();
            range.insertNode(hr);
            const p = document.createElement('p');
            p.innerHTML = '<br>';
            hr.after(p);
            const newRange = document.createRange();
            newRange.setStart(p, 0); newRange.collapse(true);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(newRange);
            this.onSlideInput();
        },

        // ── Widgets (DOM API) ──
        insertWidget(type) {
            const id = 'w_' + Date.now();
            const labels = { kpi:'KPI Dashboard', chart:'Task Status Chart', gantt:'Gantt Timeline',
                             milestone:'Milestones', team:'Team Members', blocker:'Active Blockers' };

            this.restoreSelection();
            const range = this._getInsertRange();

            // Widget wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'report-widget';
            wrapper.dataset.widgetType = type;
            wrapper.dataset.widgetId = id;
            wrapper.setAttribute('contenteditable', 'false');
            wrapper.setAttribute('onclick', 'event.stopPropagation();window.__selectWidget(this)');

            // Widget toolbar
            const toolbar = document.createElement('div');
            toolbar.className = 'widget-toolbar';
            toolbar.innerHTML =
                `<span style="font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">${type}</span>` +
                `<button class="widget-tb-btn" onclick="event.stopPropagation();window.__refreshWidget(this)">↻ Refresh</button>` +
                `<button class="widget-tb-btn widget-tb-del" onclick="event.stopPropagation();window.__deleteWidget(this)">✕ Delete</button>`;
            wrapper.appendChild(toolbar);

            // Widget content
            const content = document.createElement('div');
            content.id = 'wc-' + id;
            content.style.cssText = 'padding:12px;min-height:80px';
            content.innerHTML = `<p style="color:#9ca3af;text-align:center;padding:20px;font-size:13px">⏳ Loading ${labels[type]||type}...</p>`;
            wrapper.appendChild(content);

            range.deleteContents();
            range.insertNode(wrapper);

            const p = document.createElement('p');
            p.innerHTML = '<br>';
            wrapper.after(p);

            const newRange = document.createRange();
            newRange.setStart(p, 0); newRange.collapse(true);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(newRange);

            this.onSlideInput();
            this.$nextTick(() => this.renderWidget(id, type));
        },

        // ── Widget Rendering ──
        renderWidgets() {
            const c = this.$refs.slideCanvas;
            if (!c) return;
            c.querySelectorAll('.report-widget[data-widget-id]').forEach(w => {
                this.renderWidget(w.dataset.widgetId, w.dataset.widgetType);
            });
        },

        renderWidget(id, type) {
            const el = document.getElementById('wc-' + id);
            if (!el) return;
            if (this._chartInstances[id]) {
                try { this._chartInstances[id].destroy(); } catch(e) {}
                delete this._chartInstances[id];
            }
            switch (type) {
                case 'kpi':       el.innerHTML = this._renderKPI(); break;
                case 'chart':
                    el.innerHTML = `<div style="padding:12px"><canvas id="chart-${id}" width="420" height="200"></canvas></div>`;
                    this.$nextTick(() => this._initChart(id));
                    break;
                case 'gantt':
                    el.style.minHeight = '220px';
                    el.innerHTML = this._renderGantt(el.parentElement?.offsetWidth || 700);
                    break;
                case 'milestone': el.innerHTML = this._renderMilestones(); break;
                case 'team':      el.innerHTML = this._renderTeam(); break;
                case 'blocker':   el.innerHTML = this._renderBlockers(); break;
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
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:12px">` +
                cards.map(c =>
                    `<div style="background:${c.bg};border-radius:8px;padding:12px;text-align:center;border:1px solid ${c.color}22">
                        <div style="font-size:1.8em;font-weight:800;color:${c.color};line-height:1">${c.value}</div>
                        <div style="font-size:10px;color:#6b7280;margin-top:4px">${c.label}</div>
                    </div>`
                ).join('') + '</div>';
        },

        _initChart(id) {
            const canvas = document.getElementById('chart-' + id);
            if (!canvas || typeof Chart === 'undefined') return;
            const d = CHART_DATA.tasksByStatus;
            this._chartInstances[id] = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: ['Todo','In Progress','Review','Done','Cancelled'],
                    datasets: [{ data:[d.todo,d.in_progress,d.review,d.done,d.cancelled],
                        backgroundColor:['#94a3b8','#6366f1','#f59e0b','#22c55e','#ef4444'],
                        borderWidth:1, borderColor:'#fff' }]
                },
                options: { responsive:false, plugins:{ legend:{ position:'right', labels:{ font:{size:10} } } } },
            });
        },

        _renderGantt(W) {
            const tasks = PROJECT_DATA.tasks.filter(t => t.start_date && t.due_date);
            if (!tasks.length) return '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:12px">No tasks with scheduled dates</div>';
            const all = tasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
            const minD = new Date(Math.min(...all)), maxD = new Date(Math.max(...all));
            const totalDays = Math.max(1, (maxD - minD) / 86400000 + 1);
            const lW = 130, cW = Math.max(300, W - lW - 4), rH = 22, hH = 26;
            const H = hH + tasks.length * rH + 2;
            const sc = { done:'#16a34a', in_progress:'#4f46e5', review:'#f59e0b', todo:'#94a3b8', cancelled:'#ef4444' };
            let s = `<svg width="${lW+cW}" height="${H}" xmlns="http://www.w3.org/2000/svg" style="font-family:sans-serif;display:block">`;
            s += `<rect width="${lW+cW}" height="${H}" fill="#f8fafc" rx="2"/>`;
            s += `<rect width="${lW+cW}" height="${hH}" fill="#e2e8f0" rx="2"/>`;
            const cur = new Date(minD.getFullYear(), minD.getMonth(), 1);
            while (cur <= maxD) {
                const x = lW + ((cur - minD) / 86400000) / totalDays * cW;
                s += `<line x1="${x}" y1="${hH}" x2="${x}" y2="${H}" stroke="#e2e8f0" stroke-width="1"/>`;
                s += `<text x="${x+2}" y="18" font-size="9" fill="#64748b">${cur.toLocaleString('default',{month:'short'})} ${cur.getFullYear()}</text>`;
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
                s += `<rect x="${bx}" y="${y+3}" width="${bw}" height="${rH-6}" rx="2" fill="${sc[t.status]||'#94a3b8'}" opacity="0.85"/>`;
                if (t.progress_pct > 0)
                    s += `<rect x="${bx}" y="${y+3}" width="${bw * t.progress_pct / 100}" height="${rH-6}" rx="2" fill="rgba(255,255,255,.3)"/>`;
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
            if (!ms.length) return '<div style="padding:16px;text-align:center;color:#9ca3af;font-size:12px">No milestones</div>';
            return '<div>' + ms.map(m =>
                `<div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-bottom:1px solid #f1f5f9">
                    <span>${m.is_completed?'✅':'⭕'}</span>
                    <span style="flex:1;font-size:12px;color:#374151">${m.name}</span>
                    <span style="font-size:11px;color:#9ca3af;flex-shrink:0">${m.due_date||'—'}</span>
                </div>`
            ).join('') + '</div>';
        },

        _renderTeam() {
            const m = PROJECT_DATA.members;
            if (!m.length) return '<div style="padding:16px;text-align:center;color:#9ca3af;font-size:12px">No members</div>';
            return `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;padding:12px">` +
                m.map(mb =>
                    `<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
                        <div style="width:28px;height:28px;border-radius:50%;background:#4f46e5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">${mb.name.charAt(0).toUpperCase()}</div>
                        <div style="min-width:0">
                            <div style="font-size:11px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${mb.name}</div>
                            <div style="font-size:9px;color:#9ca3af;text-transform:capitalize">${mb.role}</div>
                        </div>
                    </div>`
                ).join('') + '</div>';
        },

        _renderBlockers() {
            const bl = PROJECT_DATA.active_blockers_list;
            if (!bl.length) return '<div style="padding:16px;text-align:center;color:#16a34a;font-size:12px;background:#f0fdf4">✅ No active blockers</div>';
            return '<div style="background:#fef2f2">' + bl.map(b =>
                `<div style="display:flex;align-items:flex-start;gap:8px;padding:10px 14px;border-bottom:1px solid #fee2e2">
                    <span style="flex-shrink:0;font-size:14px">🚨</span>
                    <div>
                        <div style="font-size:11px;font-weight:600;color:#dc2626">${b.task_title}</div>
                        <div style="font-size:10px;color:#6b7280;margin-top:2px">${b.description}</div>
                    </div>
                </div>`
            ).join('') + '</div>';
        },

        // ── Save ──
        async save() {
            this.saveCurrentCanvas();
            this.isSaving = true;
            this.saveError = '';
            try {
                const slides = this.slides.map((s, i) => ({
                    id: s.id, slide_order: i,
                    bg_color: s.bg_color || '#ffffff',
                    notes: s.notes || '',
                    html_content: s.html_content || '',
                    elements: [],
                }));
                const res = await fetch(SAVE_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ slides }),
                });
                const data = await res.json();
                if (data.success) {
                    this.slides = data.slides.map(s => ({ ...s }));
                    this.isDirty = false;
                    this.$nextTick(() => this.loadSlideToCanvas());
                    this._showToast();
                } else {
                    this.saveError = data.error || 'Save failed';
                }
            } catch(err) {
                console.error('Save failed', err);
                this.saveError = 'Network error — could not save';
            }
            this.isSaving = false;
        },

        _showToast() {
            this.saveToast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.saveToast = false; }, 2500);
        },

        openTemplateSave() { this.templateName = ''; this.showTemplateSave = true; },
        async doSaveAsTemplate() {
            if (!this.templateName.trim()) return;
            await this.save();
            const res = await fetch(TEMPLATE_URL, {
                method:'POST',
                headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ template_name: this.templateName }),
            });
            const d = await res.json();
            if (d.success) this.showTemplateSave = false;
        },
    };
}
</script>
@endpush
