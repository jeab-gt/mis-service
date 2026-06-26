@extends('layouts.app')

@section('title', $report->title . ' — Builder')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@300;400;600;700&family=Noto+Sans+Thai:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
[x-cloak] { display: none !important; }

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

/* ── Canvas Wrapper (position context for widget overlay) ── */
.slide-canvas-wrapper {
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    font-size:14px; line-height:1.65; color:#1a1a1a;
}

/* ── Slide Canvas (text layer) ── */
.slide-canvas {
    background:#fff; outline:none; cursor:text;
    word-wrap:break-word; overflow-wrap:break-word;
}
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

/* ── Props Panel ── */
.props-panel { width:220px; flex-shrink:0; overflow-y:auto; background:#fff;
    border-left:1px solid #e5e7eb; display:flex; flex-direction:column; }
.props-panel-header { padding:10px 12px; border-bottom:1px solid #e5e7eb; background:#f9fafb; flex-shrink:0; }

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

/* ── Shape Picker ── */
.shape-btn { width:28px; height:28px; display:flex; align-items:center; justify-content:center;
    border:1px solid #e5e7eb; border-radius:4px; cursor:pointer; font-size:13px; background:#fff;
    flex-shrink:0; transition:background .1s,border-color .1s; }
.shape-btn:hover { background:#f3f4f6; border-color:#6366f1; }
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

        <select class="rb-select" @change="setCanvasFont($event.target.value);$event.target.value=''" title="Font Family" style="max-width:110px">
            <option value="">Font</option>
            <option value="Arial, sans-serif">Arial</option>
            <option value="'Times New Roman', serif">Times New Roman</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="Verdana, sans-serif">Verdana</option>
            <option value="Tahoma, sans-serif">Tahoma</option>
            <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
            <option value="'Courier New', monospace">Courier New</option>
            <option value="Impact, sans-serif">Impact</option>
            <option value="'Sarabun', sans-serif">Sarabun</option>
            <option value="'Prompt', sans-serif">Prompt</option>
            <option value="'Noto Sans Thai', sans-serif">Noto Sans Thai</option>
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
        <div class="relative">
            <button class="rb-btn" @mousedown.prevent @click="tableOpen=!tableOpen" title="Insert Table">
                <i class="ti ti-table"></i> Table
            </button>
            <div x-show="tableOpen" @click.outside="tableOpen=false" x-cloak
                 class="absolute top-11 left-0 bg-white border border-gray-200 rounded-xl shadow-xl p-3 z-50 w-40">
                <p class="text-xs text-gray-400 text-center mb-2" x-text="tableHr+'×'+tableHc"></p>
                <div class="grid gap-0.5" style="grid-template-columns:repeat(6,1fr)">
                    <template x-for="cell in tableCells" :key="cell.key">
                        <div @mousedown.prevent @click="insertTable(cell.r,cell.c);tableOpen=false"
                             @mouseenter="tableHr=cell.r;tableHc=cell.c"
                             :class="cell.r<=tableHr&&cell.c<=tableHc?'on':''"
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

        {{-- Shape Picker --}}
        <div class="relative">
            <button class="rb-btn" @mousedown.prevent @click="shapeOpen=!shapeOpen" title="Insert Shape">
                <i class="ti ti-shapes"></i> Shape
            </button>
            <div x-show="shapeOpen" @click.outside="shapeOpen=false" x-cloak
                 class="absolute top-11 left-0 bg-white border border-gray-200 rounded-xl shadow-xl p-3 z-50 w-72">
                <p class="text-xs font-semibold text-gray-500 mb-1">Lines</p>
                <div class="flex flex-wrap gap-1 mb-2">
                    <button @mousedown.prevent @click="insertShape('line');shapeOpen=false"         class="shape-btn" title="Line">─</button>
                    <button @mousedown.prevent @click="insertShape('arrow_right');shapeOpen=false"  class="shape-btn" title="Arrow →">→</button>
                    <button @mousedown.prevent @click="insertShape('arrow_left');shapeOpen=false"   class="shape-btn" title="Arrow ←">←</button>
                    <button @mousedown.prevent @click="insertShape('arrow_both');shapeOpen=false"   class="shape-btn" title="Both">↔</button>
                    <button @mousedown.prevent @click="insertShape('arrow_up');shapeOpen=false"     class="shape-btn" title="Up">↑</button>
                    <button @mousedown.prevent @click="insertShape('arrow_down');shapeOpen=false"   class="shape-btn" title="Down">↓</button>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-1">Basic Shapes</p>
                <div class="flex flex-wrap gap-1 mb-2">
                    <button @mousedown.prevent @click="insertShape('rectangle');shapeOpen=false"    class="shape-btn" title="Rectangle">▭</button>
                    <button @mousedown.prevent @click="insertShape('rounded_rect');shapeOpen=false" class="shape-btn" title="Rounded">▢</button>
                    <button @mousedown.prevent @click="insertShape('circle');shapeOpen=false"       class="shape-btn" title="Circle">○</button>
                    <button @mousedown.prevent @click="insertShape('triangle');shapeOpen=false"     class="shape-btn" title="Triangle">△</button>
                    <button @mousedown.prevent @click="insertShape('diamond');shapeOpen=false"      class="shape-btn" title="Diamond">◇</button>
                    <button @mousedown.prevent @click="insertShape('hexagon');shapeOpen=false"      class="shape-btn" title="Hexagon">⬡</button>
                    <button @mousedown.prevent @click="insertShape('star');shapeOpen=false"         class="shape-btn" title="Star">★</button>
                    <button @mousedown.prevent @click="insertShape('heart');shapeOpen=false"        class="shape-btn" title="Heart">♥</button>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-1">Block Arrows</p>
                <div class="flex flex-wrap gap-1 mb-2">
                    <button @mousedown.prevent @click="insertShape('block_right');shapeOpen=false"  class="shape-btn" title="Block →">⇒</button>
                    <button @mousedown.prevent @click="insertShape('block_left');shapeOpen=false"   class="shape-btn" title="Block ←">⇐</button>
                    <button @mousedown.prevent @click="insertShape('block_up');shapeOpen=false"     class="shape-btn" title="Block ↑">⇑</button>
                    <button @mousedown.prevent @click="insertShape('block_down');shapeOpen=false"   class="shape-btn" title="Block ↓">⇓</button>
                </div>
                <p class="text-xs font-semibold text-gray-500 mb-1">Flowchart</p>
                <div class="flex flex-wrap gap-1">
                    <button @mousedown.prevent @click="insertShape('flow_process');shapeOpen=false"  class="shape-btn" title="Process">▭</button>
                    <button @mousedown.prevent @click="insertShape('flow_decision');shapeOpen=false" class="shape-btn" title="Decision">◇</button>
                    <button @mousedown.prevent @click="insertShape('flow_terminal');shapeOpen=false" class="shape-btn" title="Terminal">⬭</button>
                    <button @mousedown.prevent @click="insertShape('flow_data');shapeOpen=false"     class="shape-btn" title="Data">▱</button>
                    <button @mousedown.prevent @click="insertShape('flow_connector');shapeOpen=false" class="shape-btn" title="Connector">○</button>
                </div>
            </div>
        </div>

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
                {{-- Wrapper: position context for widget overlay --}}
                <div class="slide-canvas-wrapper" :style="canvasWrapperStyle" @click="selectedWidget=null">

                    {{-- Text layer --}}
                    <div class="slide-canvas"
                         contenteditable="true"
                         spellcheck="false"
                         x-ref="slideCanvas"
                         @input="onSlideInput()"
                         @blur="saveSelection()"
                         @mouseup="onSelectionChange()"
                         @keyup="onSelectionChange()"
                         @keydown="onCanvasKeydown($event)"
                         @click.stop="onSelectionChange()"
                         @dragover.prevent
                         @drop.prevent="handleDrop($event)"
                         :style="canvasInnerStyle">
                    </div>

                    {{-- Widget overlay layer --}}
                    <div style="position:absolute;inset:0;pointer-events:none;z-index:2">
                        <template x-for="widget in (slides[currentSlideIndex]?.widgets ?? [])" :key="widget.id">
                            <div :style="{
                                     position:'absolute',
                                     left:widget.x+'px', top:widget.y+'px',
                                     width:widget.w+'px', height:widget.h+'px',
                                     pointerEvents:'all',
                                     border:selectedWidget&&selectedWidget.id===widget.id?'2px solid #2563eb':'2px dashed #6366f1',
                                     borderRadius:'8px',
                                     background:'white',
                                     cursor:'move',
                                     boxShadow:selectedWidget&&selectedWidget.id===widget.id?'0 0 0 3px rgba(37,99,235,0.15)':'none',
                                     zIndex:selectedWidget&&selectedWidget.id===widget.id?10:1
                                 }"
                                 @click.stop="selectWidget(widget)"
                                 @mousedown.stop="startWidgetDrag($event, widget)">

                                {{-- Widget toolbar (visible when selected) --}}
                                <div x-show="selectedWidget&&selectedWidget.id===widget.id"
                                     style="position:absolute;top:-34px;left:0;background:#1f2937;color:white;
                                            border-radius:6px;padding:3px 8px;display:flex;align-items:center;
                                            gap:8px;z-index:10;white-space:nowrap;font-size:11px;
                                            box-shadow:0 2px 8px rgba(0,0,0,.4)">
                                    <span x-text="widget.type.toUpperCase()" style="color:#9ca3af;font-size:10px;letter-spacing:.5px"></span>
                                    <button @click.stop="deleteWidget(widget)"
                                            style="background:#dc2626;border:none;color:white;padding:2px 8px;
                                                   border-radius:4px;cursor:pointer;font-size:11px">✕ Delete</button>
                                </div>

                                {{-- Widget content --}}
                                <div :id="'wc-'+widget.id" style="width:100%;height:100%;overflow:hidden;padding:8px">
                                    <p style="color:#9ca3af;text-align:center;padding:20px;font-size:12px">⏳ Loading...</p>
                                </div>

                                {{-- Resize handle --}}
                                <div x-show="selectedWidget&&selectedWidget.id===widget.id"
                                     style="position:absolute;bottom:-5px;right:-5px;width:12px;height:12px;
                                            background:#2563eb;border-radius:2px;cursor:se-resize;z-index:11"
                                     @mousedown.stop="startWidgetResize($event, widget)">
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </div>

        {{-- ══ Right Properties Panel ══ --}}
        <div class="props-panel">
            <div class="props-panel-header">
                <h3 class="text-xs font-semibold text-gray-600">
                    <span x-show="!selectedWidget">Properties</span>
                    <span x-show="selectedWidget" x-text="selectedWidget ? selectedWidget.type.toUpperCase()+' Widget' : ''"></span>
                </h3>
            </div>

            {{-- Nothing selected --}}
            <template x-if="!selectedWidget">
                <div class="flex flex-col items-center justify-center p-4 text-center text-gray-400" style="min-height:120px">
                    <div style="font-size:24px;margin-bottom:6px">🖱️</div>
                    <div class="text-xs">Click a widget to configure</div>
                </div>
            </template>

            {{-- Widget config --}}
            <template x-if="selectedWidget">
                <div class="p-3 space-y-3">

                    {{-- Position & Size --}}
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1.5">Position & Size</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gray-400 block mb-0.5">X</label>
                                <input type="number" x-model.number="selectedWidget.x"
                                       class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 block mb-0.5">Y</label>
                                <input type="number" x-model.number="selectedWidget.y"
                                       class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 block mb-0.5">Width</label>
                                <input type="number" x-model.number="selectedWidget.w"
                                       @change="$nextTick(()=>renderWidget(selectedWidget.id,selectedWidget.type))"
                                       class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 block mb-0.5">Height</label>
                                <input type="number" x-model.number="selectedWidget.h"
                                       @change="$nextTick(()=>renderWidget(selectedWidget.id,selectedWidget.type))"
                                       class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                            </div>
                        </div>
                    </div>

                    {{-- Data Mode (live data widgets only) --}}
                    <template x-if="selectedWidget.type!=='image'&&selectedWidget.type!=='shape'&&selectedWidget.type!=='table'">
                        <div>
                            <label class="text-xs text-gray-500 block mb-0.5">Data Mode</label>
                            <select x-model="selectedWidget.data_mode"
                                    @change="$nextTick(()=>renderWidget(selectedWidget.id,selectedWidget.type))"
                                    class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                                <option value="live">🔄 Live (real-time)</option>
                                <option value="snapshot">📸 Snapshot</option>
                            </select>
                        </div>
                    </template>

                    {{-- KPI metric selector --}}
                    <template x-if="selectedWidget.type==='kpi'">
                        <div>
                            <label class="text-xs text-gray-500 block mb-0.5">Metric</label>
                            <select x-model="selectedWidget.config.metric"
                                    @change="$nextTick(()=>renderWidget(selectedWidget.id,'kpi'))"
                                    class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                                <option value="">All KPIs</option>
                                <option value="total_tasks">Total Tasks</option>
                                <option value="done_tasks">Done Tasks</option>
                                <option value="in_progress">In Progress</option>
                                <option value="overdue">Overdue</option>
                                <option value="progress_pct">Progress %</option>
                                <option value="blockers">Active Blockers</option>
                            </select>
                        </div>
                    </template>

                    {{-- Chart type selector --}}
                    <template x-if="selectedWidget.type==='chart'">
                        <div>
                            <label class="text-xs text-gray-500 block mb-0.5">Chart Type</label>
                            <select x-model="selectedWidget.config.chart_type"
                                    @change="$nextTick(()=>renderWidget(selectedWidget.id,'chart'))"
                                    class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                                <option value="tasks_by_status">Tasks by Status (Donut)</option>
                                <option value="burndown">Burndown Chart</option>
                                <option value="workload">Workload per Member</option>
                            </select>
                        </div>
                    </template>

                    {{-- Table widget config --}}
                    <template x-if="selectedWidget.type==='table'">
                        <div class="space-y-2">
                            {{-- Headers --}}
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Headers</p>
                                <template x-for="(h, i) in selectedWidget.config.headers" :key="i">
                                    <input type="text" x-model="selectedWidget.config.headers[i]"
                                           @input="renderWidget(selectedWidget.id,'table')"
                                           class="w-full border border-gray-200 rounded px-2 py-1 text-xs mt-0.5 focus:outline-none focus:border-indigo-400"
                                           :placeholder="'Header '+(i+1)">
                                </template>
                            </div>
                            {{-- Col controls --}}
                            <div class="flex gap-1">
                                <button @click="addTableCol()"
                                        class="flex-1 border border-gray-200 rounded text-xs py-1 hover:bg-gray-50">+ Col</button>
                                <button @click="removeTableCol()"
                                        class="flex-1 border border-gray-200 rounded text-xs py-1 hover:bg-gray-50 text-red-500">- Col</button>
                            </div>
                            {{-- Row controls --}}
                            <div class="flex gap-1">
                                <button @click="addTableRow()"
                                        class="flex-1 border border-gray-200 rounded text-xs py-1 hover:bg-gray-50">+ Row</button>
                                <button @click="removeTableRow()"
                                        class="flex-1 border border-gray-200 rounded text-xs py-1 hover:bg-gray-50 text-red-500">- Row</button>
                            </div>
                            {{-- Cell data --}}
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Cell Data</p>
                                <template x-for="(row, ri) in selectedWidget.config.data" :key="ri">
                                    <div class="flex gap-1 mt-1 items-center">
                                        <span class="text-xs text-gray-400 w-4 shrink-0" x-text="ri+1"></span>
                                        <template x-for="(cell, ci) in row" :key="ci">
                                            <input type="text" x-model="selectedWidget.config.data[ri][ci]"
                                                   @input="renderWidget(selectedWidget.id,'table')"
                                                   class="flex-1 border border-gray-200 rounded px-1 py-0.5 text-xs min-w-0 focus:outline-none focus:border-indigo-400">
                                        </template>
                                    </div>
                                </template>
                            </div>
                            {{-- Style --}}
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Font Size</label>
                                    <input type="number" x-model.number="selectedWidget.config.fontSize"
                                           @input="renderWidget(selectedWidget.id,'table')"
                                           class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400"
                                           min="8" max="24">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Border</label>
                                    <input type="color" x-model="selectedWidget.config.borderColor"
                                           @input="renderWidget(selectedWidget.id,'table')"
                                           class="w-full h-7 rounded border cursor-pointer">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Header BG</label>
                                    <input type="color" x-model="selectedWidget.config.headerBg"
                                           @input="renderWidget(selectedWidget.id,'table')"
                                           class="w-full h-7 rounded border cursor-pointer">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Header Text</label>
                                    <input type="color" x-model="selectedWidget.config.headerColor"
                                           @input="renderWidget(selectedWidget.id,'table')"
                                           class="w-full h-7 rounded border cursor-pointer">
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Shape widget config --}}
                    <template x-if="selectedWidget.type==='shape'">
                        <div class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Fill</label>
                                    <input type="color" x-model="selectedWidget.config.fill"
                                           @input="renderWidget(selectedWidget.id,'shape')"
                                           class="w-full h-7 rounded border cursor-pointer">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Border</label>
                                    <input type="color" x-model="selectedWidget.config.stroke"
                                           @input="renderWidget(selectedWidget.id,'shape')"
                                           class="w-full h-7 rounded border cursor-pointer">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Border W</label>
                                    <input type="number" x-model.number="selectedWidget.config.stroke_width"
                                           @input="renderWidget(selectedWidget.id,'shape')"
                                           class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400"
                                           min="0" max="10">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-0.5">Font Size</label>
                                    <input type="number" x-model.number="selectedWidget.config.font_size"
                                           @input="renderWidget(selectedWidget.id,'shape')"
                                           class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400"
                                           min="8" max="48">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-0.5">Text ในรูป</label>
                                <input type="text" x-model="selectedWidget.config.text"
                                       @input="renderWidget(selectedWidget.id,'shape')"
                                       class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400"
                                       placeholder="ข้อความในรูป...">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-0.5">Text Color</label>
                                <input type="color" x-model="selectedWidget.config.text_color"
                                       @input="renderWidget(selectedWidget.id,'shape')"
                                       class="w-full h-7 rounded border cursor-pointer">
                            </div>
                        </div>
                    </template>

                    {{-- Image widget config --}}
                    <template x-if="selectedWidget.type==='image'">
                        <div class="space-y-2">
                            <div>
                                <label class="text-xs text-gray-500 block mb-0.5">Fit</label>
                                <select x-model="selectedWidget.config.objectFit"
                                        @change="$nextTick(()=>renderWidget(selectedWidget.id,'image'))"
                                        class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-indigo-400">
                                    <option value="contain">Contain</option>
                                    <option value="cover">Cover</option>
                                    <option value="fill">Fill (stretch)</option>
                                    <option value="none">Original</option>
                                </select>
                            </div>
                            <button @click="replaceImageWidget(selectedWidget)"
                                    class="w-full bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg py-1.5 text-xs hover:bg-indigo-100 transition-colors">
                                🖼 Change Image
                            </button>
                        </div>
                    </template>

                    {{-- Delete widget --}}
                    <button @click="deleteWidget(selectedWidget)"
                            class="w-full bg-red-50 text-red-600 border border-red-200 rounded-lg py-1.5 text-xs hover:bg-red-100 transition-colors">
                        🗑 Delete Widget
                    </button>

                </div>
            </template>
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
        <select @change="execCmd('fontName',$event.target.value)" class="ftb-sel ml-0.5" title="Font" style="max-width:100px">
            <option value="Arial, sans-serif">Arial</option>
            <option value="'Times New Roman', serif">Times NR</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="Verdana, sans-serif">Verdana</option>
            <option value="Tahoma, sans-serif">Tahoma</option>
            <option value="'Courier New', monospace">Courier</option>
            <option value="Impact, sans-serif">Impact</option>
            <option value="'Sarabun', sans-serif">Sarabun</option>
            <option value="'Prompt', sans-serif">Prompt</option>
            <option value="'Noto Sans Thai', sans-serif">Noto Thai</option>
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
            'widgets_data' => $s->widgets_data ?? [],
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

function reportBuilder() {
    return {
        slides: [],
        currentSlideIndex: 0,
        selectedWidget: null,
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
        tableOpen: false,
        tableHr: 1,
        tableHc: 1,
        shapeOpen: false,

        get canvasWrapperStyle() {
            const base = {
                position: 'relative',
                background: '#fff',
                boxShadow: '0 8px 40px rgba(0,0,0,.55)',
            };
            return {
                a4:    { ...base, width:'794px',  minHeight:'1123px' },
                slide: { ...base, width:'960px',  height:'540px' },
                wide:  { ...base, width:'1122px', minHeight:'794px' },
            }[this.slideLayout] || { ...base, width:'794px', minHeight:'1123px' };
        },

        get canvasInnerStyle() {
            const base = { outline:'none', wordWrap:'break-word', overflowWrap:'break-word',
                           boxSizing:'border-box', width:'100%' };
            return {
                a4:    { ...base, padding:'60px 72px', minHeight:'1123px' },
                slide: { ...base, padding:'48px 56px', height:'540px', overflow:'hidden' },
                wide:  { ...base, padding:'56px 72px', minHeight:'794px' },
            }[this.slideLayout] || { ...base, padding:'60px 72px', minHeight:'1123px' };
        },

        get currentSlide() { return this.slides[this.currentSlideIndex] || null; },

        // ── Init ──
        init() {
            window.__reportBuilder = this;
            this.slides = (REPORT_DATA.slides || []).map(s => ({
                ...s,
                widgets: Array.isArray(s.widgets_data)
                    ? s.widgets_data.map(w => ({ config:{}, data_mode:'live', ...w }))
                    : [],
            }));
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
            // Let Alpine.js x-for render widgets first, then populate their content
            this.$nextTick(() => setTimeout(() => this.renderWidgets(), 50));
        },

        saveCurrentCanvas() {
            const c = this.$refs.slideCanvas;
            if (!c || !this.currentSlide) return;
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
            this.selectedWidget = null;
            this.showToolbar = false;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        addSlide() {
            this.saveCurrentCanvas();
            const slide = { id:'new_'+Date.now(), slide_order:this.slides.length, bg_color:'#ffffff', notes:'', html_content:'', widgets:[] };
            this.slides.push(slide);
            this.currentSlideIndex = this.slides.length - 1;
            this.selectedWidget = null;
            this.isDirty = true;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        deleteSlide(idx) {
            if (this.slides.length <= 1) return;
            this.slides.splice(idx, 1);
            if (this.currentSlideIndex >= this.slides.length) this.currentSlideIndex = this.slides.length - 1;
            this.selectedWidget = null;
            this.isDirty = true;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
        },

        duplicateSlide(idx) {
            this.saveCurrentCanvas();
            const dup = { ...JSON.parse(JSON.stringify(this.slides[idx])), id:'new_'+Date.now() };
            this.slides.splice(idx+1, 0, dup);
            this.currentSlideIndex = idx + 1;
            this.selectedWidget = null;
            this.isDirty = true;
            this._savedRange = null;
            this.$nextTick(() => this.loadSlideToCanvas());
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

        setCanvasFont(font) {
            if (!font) return;
            const sel = window.getSelection();
            if (sel && !sel.isCollapsed && this.$refs.slideCanvas?.contains(sel.anchorNode)) {
                document.execCommand('fontName', false, font);
            } else {
                this.$refs.slideCanvas.style.fontFamily = font;
            }
            this.onSlideInput();
        },

        // ── DOM-based range insertion helper ──
        _getInsertRange() {
            const canvas = this.$refs.slideCanvas;
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && canvas.contains(sel.anchorNode)) {
                return sel.getRangeAt(0);
            }
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
            if (!this.currentSlide) return;
            const blob = await this.compressImage(file);
            const reader = new FileReader();
            reader.onload = e => {
                const widget = {
                    id: 'w_' + Date.now(),
                    type: 'image',
                    x: 40, y: 40,
                    w: 360, h: 240,
                    config: { src: e.target.result, objectFit: 'contain' },
                    data_mode: 'live',
                    snapshot_data: null,
                };
                this.currentSlide.widgets.push(widget);
                this.selectedWidget = widget;
                this.isDirty = true;
                this.$nextTick(() => setTimeout(() => this.renderWidget(widget.id, 'image'), 30));
            };
            reader.readAsDataURL(blob);
        },

        replaceImageWidget(widget) {
            const input = document.createElement('input');
            input.type = 'file'; input.accept = 'image/*';
            input.onchange = async e => {
                if (!e.target.files[0]) return;
                const blob = await this.compressImage(e.target.files[0]);
                const reader = new FileReader();
                reader.onload = ev => {
                    widget.config.src = ev.target.result;
                    this.isDirty = true;
                    this.$nextTick(() => this.renderWidget(widget.id, 'image'));
                };
                reader.readAsDataURL(blob);
            };
            input.click();
        },

        async handleDrop(e) {
            const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            for (const f of files) await this.insertImageFile(f);
        },

        // ── Shape widget ──
        insertShape(shapeType) {
            if (!this.currentSlide) return;
            const widget = {
                id: 'w_' + Date.now(),
                type: 'shape',
                x: 100, y: 100,
                w: 200, h: 120,
                config: {
                    shape_type:   shapeType,
                    fill:         '#6366f1',
                    stroke:       '#4338ca',
                    stroke_width: 2,
                    text:         '',
                    text_color:   '#ffffff',
                    font_size:    14,
                },
                data_mode: 'static',
                snapshot_data: null,
            };
            this.currentSlide.widgets.push(widget);
            this.selectedWidget = widget;
            this.isDirty = true;
            this.$nextTick(() => setTimeout(() => this.renderWidget(widget.id, 'shape'), 30));
        },

        _renderShape(widget) {
            if (!widget) return '';
            const sc  = widget.config || {};
            const sw  = widget.w, sh = widget.h;
            const fill = sc.fill || '#6366f1';
            const stroke = sc.stroke || '#4338ca';
            const strokeW = sc.stroke_width ?? 2;
            const text = sc.text || '';
            const tc   = sc.text_color || '#ffffff';
            const fs   = sc.font_size || 14;
            const p    = strokeW;

            let shapeSVG = '';
            switch (sc.shape_type) {
                case 'rounded_rect':
                    shapeSVG = `<rect x="${p}" y="${p}" width="${sw-p*2}" height="${sh-p*2}" rx="12" ry="12" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'circle':
                    shapeSVG = `<ellipse cx="${sw/2}" cy="${sh/2}" rx="${sw/2-p}" ry="${sh/2-p}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'triangle':
                    shapeSVG = `<polygon points="${sw/2},${p} ${sw-p},${sh-p} ${p},${sh-p}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'diamond':
                    shapeSVG = `<polygon points="${sw/2},${p} ${sw-p},${sh/2} ${sw/2},${sh-p} ${p},${sh/2}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'hexagon': {
                    const hx = sw/2, hy = sh/2, hr = Math.min(sw,sh)/2-p;
                    const hpts = Array.from({length:6},(_,i)=>{const a=(i*60-30)*Math.PI/180;return `${hx+hr*Math.cos(a)},${hy+hr*Math.sin(a)}`;}).join(' ');
                    shapeSVG = `<polygon points="${hpts}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                }
                case 'star': {
                    const pts = Array.from({length:10},(_,i)=>{const a=(i*36-90)*Math.PI/180,r=i%2===0?Math.min(sw,sh)/2-p:Math.min(sw,sh)/4;return `${sw/2+r*Math.cos(a)},${sh/2+r*Math.sin(a)}`;}).join(' ');
                    shapeSVG = `<polygon points="${pts}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                }
                case 'heart': {
                    const hW=sw-p*2, hH2=sh-p*2, ox=p, oy=p;
                    shapeSVG = `<path d="M${ox+hW/2},${oy+hH2*0.35} C${ox+hW/2},${oy+hH2*0.12} ${ox},${oy+hH2*0.12} ${ox},${oy+hH2*0.35} C${ox},${oy+hH2*0.65} ${ox+hW/2},${oy+hH2*0.9} ${ox+hW/2},${oy+hH2} C${ox+hW/2},${oy+hH2*0.9} ${ox+hW},${oy+hH2*0.65} ${ox+hW},${oy+hH2*0.35} C${ox+hW},${oy+hH2*0.12} ${ox+hW/2},${oy+hH2*0.12} ${ox+hW/2},${oy+hH2*0.35}Z" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                }
                case 'line':
                    shapeSVG = `<line x1="${p}" y1="${sh/2}" x2="${sw-p}" y2="${sh/2}" stroke="${stroke}" stroke-width="${strokeW+1}"/>`;
                    break;
                case 'arrow_right':
                    shapeSVG = `<line x1="${p}" y1="${sh/2}" x2="${sw-p-10}" y2="${sh/2}" stroke="${stroke}" stroke-width="${strokeW+1}"/>
                        <polygon points="${sw-p},${sh/2} ${sw-p-12},${sh/2-6} ${sw-p-12},${sh/2+6}" fill="${stroke}"/>`;
                    break;
                case 'arrow_left':
                    shapeSVG = `<line x1="${p+10}" y1="${sh/2}" x2="${sw-p}" y2="${sh/2}" stroke="${stroke}" stroke-width="${strokeW+1}"/>
                        <polygon points="${p},${sh/2} ${p+12},${sh/2-6} ${p+12},${sh/2+6}" fill="${stroke}"/>`;
                    break;
                case 'arrow_both':
                    shapeSVG = `<line x1="${p+10}" y1="${sh/2}" x2="${sw-p-10}" y2="${sh/2}" stroke="${stroke}" stroke-width="${strokeW+1}"/>
                        <polygon points="${p},${sh/2} ${p+12},${sh/2-6} ${p+12},${sh/2+6}" fill="${stroke}"/>
                        <polygon points="${sw-p},${sh/2} ${sw-p-12},${sh/2-6} ${sw-p-12},${sh/2+6}" fill="${stroke}"/>`;
                    break;
                case 'arrow_up':
                    shapeSVG = `<line x1="${sw/2}" y1="${sh-p}" x2="${sw/2}" y2="${p+10}" stroke="${stroke}" stroke-width="${strokeW+1}"/>
                        <polygon points="${sw/2},${p} ${sw/2-6},${p+12} ${sw/2+6},${p+12}" fill="${stroke}"/>`;
                    break;
                case 'arrow_down':
                    shapeSVG = `<line x1="${sw/2}" y1="${p}" x2="${sw/2}" y2="${sh-p-10}" stroke="${stroke}" stroke-width="${strokeW+1}"/>
                        <polygon points="${sw/2},${sh-p} ${sw/2-6},${sh-p-12} ${sw/2+6},${sh-p-12}" fill="${stroke}"/>`;
                    break;
                case 'block_right':
                    shapeSVG = `<polygon points="${p},${sh*0.3} ${sw*0.65},${sh*0.3} ${sw*0.65},${p} ${sw-p},${sh/2} ${sw*0.65},${sh-p} ${sw*0.65},${sh*0.7} ${p},${sh*0.7}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'block_left':
                    shapeSVG = `<polygon points="${sw-p},${sh*0.3} ${sw*0.35},${sh*0.3} ${sw*0.35},${p} ${p},${sh/2} ${sw*0.35},${sh-p} ${sw*0.35},${sh*0.7} ${sw-p},${sh*0.7}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'block_up':
                    shapeSVG = `<polygon points="${sw*0.3},${sh-p} ${sw*0.3},${sh*0.35} ${p},${sh*0.35} ${sw/2},${p} ${sw-p},${sh*0.35} ${sw*0.7},${sh*0.35} ${sw*0.7},${sh-p}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'block_down':
                    shapeSVG = `<polygon points="${sw*0.3},${p} ${sw*0.3},${sh*0.65} ${p},${sh*0.65} ${sw/2},${sh-p} ${sw-p},${sh*0.65} ${sw*0.7},${sh*0.65} ${sw*0.7},${p}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'flow_terminal':
                    shapeSVG = `<rect x="${p}" y="${p}" width="${sw-p*2}" height="${sh-p*2}" rx="${(sh-p*2)/2}" ry="${(sh-p*2)/2}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'flow_data':
                    shapeSVG = `<polygon points="${sw*0.12},${p} ${sw-p},${p} ${sw*0.88},${sh-p} ${p},${sh-p}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                case 'flow_connector':
                    shapeSVG = `<ellipse cx="${sw/2}" cy="${sh/2}" rx="${Math.min(sw,sh)/2-p}" ry="${Math.min(sw,sh)/2-p}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
                    break;
                default: // rectangle, flow_process
                    shapeSVG = `<rect x="${p}" y="${p}" width="${sw-p*2}" height="${sh-p*2}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeW}"/>`;
            }

            const textEl = text ? `<text x="${sw/2}" y="${sh/2}" text-anchor="middle" dominant-baseline="middle" fill="${tc}" font-size="${fs}" font-family="sans-serif">${text}</text>` : '';
            return `<svg width="100%" height="100%" viewBox="0 0 ${sw} ${sh}" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">${shapeSVG}${textEl}</svg>`;
        },

        // ── Table widget helpers ──
        addTableRow() {
            const w = this.selectedWidget; if (!w) return;
            const cols = w.config.headers.length;
            w.config.data.push(Array.from({length: cols}, () => 'Cell'));
            w.h += 40;
            this.isDirty = true;
            this.renderWidget(w.id, 'table');
        },
        removeTableRow() {
            const w = this.selectedWidget; if (!w || w.config.data.length < 1) return;
            w.config.data.pop();
            w.h = Math.max(80, w.h - 40);
            this.isDirty = true;
            this.renderWidget(w.id, 'table');
        },
        addTableCol() {
            const w = this.selectedWidget; if (!w) return;
            w.config.headers.push('Header ' + (w.config.headers.length + 1));
            w.config.data.forEach(row => row.push('Cell'));
            w.w += 100;
            this.isDirty = true;
            this.renderWidget(w.id, 'table');
        },
        removeTableCol() {
            const w = this.selectedWidget; if (!w || w.config.headers.length < 2) return;
            w.config.headers.pop();
            w.config.data.forEach(row => row.pop());
            w.w = Math.max(100, w.w - 100);
            this.isDirty = true;
            this.renderWidget(w.id, 'table');
        },

        // ── Insert Table (as widget) ──
        insertTable(rows, cols) {
            rows = parseInt(rows) || 3;
            cols = parseInt(cols) || 3;
            if (!this.currentSlide) return;

            const headers = Array.from({length: cols}, (_, i) => `Header ${i+1}`);
            const data    = Array.from({length: rows-1}, (_, r) =>
                Array.from({length: cols}, (_, c) => `Row ${r+1} Col ${c+1}`)
            );

            const widget = {
                id: 'w_' + Date.now(),
                type: 'table',
                x: 80, y: 80,
                w: Math.min(700, cols * 120 + 20),
                h: Math.min(400, rows * 44 + 20),
                config: {
                    headers,
                    data,
                    headerBg:    '#f3f4f6',
                    headerColor: '#111827',
                    borderColor: '#d1d5db',
                    fontSize:    13,
                },
                data_mode: 'static',
                snapshot_data: null,
            };

            this.currentSlide.widgets.push(widget);
            this.selectedWidget = widget;
            this.tableOpen = false;
            this.isDirty = true;
            this.$nextTick(() => setTimeout(() => this.renderWidget(widget.id, 'table'), 30));
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

        // ── Widgets ──
        insertWidget(type) {
            if (!this.currentSlide) return;
            const dims = {
                kpi:       { w:320, h:160 },
                chart:     { w:380, h:250 },
                gantt:     { w:650, h:220 },
                milestone: { w:300, h:200 },
                team:      { w:360, h:220 },
                blocker:   { w:320, h:180 },
            };
            const d = dims[type] || { w:300, h:200 };
            const widget = {
                id: 'w_' + Date.now(),
                type,
                x: 40, y: 40,
                w: d.w, h: d.h,
                config: {},
                data_mode: 'live',
                snapshot_data: null,
            };
            this.currentSlide.widgets.push(widget);
            this.selectedWidget = widget;
            this.isDirty = true;
            // Wait for Alpine.js x-for to render the new widget DOM node
            this.$nextTick(() => setTimeout(() => this.renderWidget(widget.id, type), 30));
        },

        selectWidget(widget) {
            this.selectedWidget = widget;
            this.renderWidget(widget.id, widget.type);
        },

        deleteWidget(widget) {
            if (!this.currentSlide) return;
            if (this._chartInstances[widget.id]) {
                try { this._chartInstances[widget.id].destroy(); } catch(e) {}
                delete this._chartInstances[widget.id];
            }
            this.currentSlide.widgets = this.currentSlide.widgets.filter(w => w.id !== widget.id);
            this.selectedWidget = null;
            this.isDirty = true;
        },

        startWidgetDrag(event, widget) {
            const startX = event.clientX - widget.x;
            const startY = event.clientY - widget.y;
            const onMove = (e) => {
                widget.x = Math.max(0, e.clientX - startX);
                widget.y = Math.max(0, e.clientY - startY);
                this.isDirty = true;
            };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        },

        startWidgetResize(event, widget) {
            const startX = event.clientX;
            const startY = event.clientY;
            const startW = widget.w;
            const startH = widget.h;
            const onMove = (e) => {
                widget.w = Math.max(100, startW + e.clientX - startX);
                widget.h = Math.max(60,  startH + e.clientY - startY);
                this.isDirty = true;
            };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                this.$nextTick(() => this.renderWidget(widget.id, widget.type));
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        },

        // ── Widget Rendering ──
        renderWidgets() {
            if (!this.currentSlide) return;
            this.currentSlide.widgets.forEach(w => this.renderWidget(w.id, w.type));
        },

        renderWidget(id, type) {
            const el = document.getElementById('wc-' + id);
            if (!el) return;
            if (this._chartInstances[id]) {
                try { this._chartInstances[id].destroy(); } catch(e) {}
                delete this._chartInstances[id];
            }
            switch (type) {
                case 'kpi':
                    el.innerHTML = this._renderKPI();
                    break;
                case 'chart':
                    el.style.padding = '8px';
                    el.innerHTML = `<canvas id="chart-${id}" style="width:100%;height:100%"></canvas>`;
                    this.$nextTick(() => this._initChart(id));
                    break;
                case 'gantt':
                    el.innerHTML = this._renderGantt();
                    break;
                case 'milestone':
                    el.innerHTML = this._renderMilestones();
                    break;
                case 'team':
                    el.innerHTML = this._renderTeam();
                    break;
                case 'blocker':
                    el.innerHTML = this._renderBlockers();
                    break;
                case 'image': {
                    const widget = (this.currentSlide?.widgets || []).find(w => w.id === id);
                    const src = widget?.config?.src || '';
                    const fit = widget?.config?.objectFit || 'contain';
                    el.innerHTML = src
                        ? `<img src="${src}" style="width:100%;height:100%;object-fit:${fit};display:block;border-radius:4px" draggable="false">`
                        : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;background:#f9fafb;border-radius:4px">No image</div>`;
                    break;
                }
                case 'shape': {
                    const widget = (this.currentSlide?.widgets || []).find(w => w.id === id);
                    el.innerHTML = this._renderShape(widget);
                    break;
                }
                case 'table': {
                    const widget = (this.currentSlide?.widgets || []).find(w => w.id === id);
                    const cfg = widget?.config || {};
                    const headers = cfg.headers || ['Header 1', 'Header 2', 'Header 3'];
                    const rows    = cfg.data    || [['Cell', 'Cell', 'Cell']];
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
            return `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;padding:8px">` +
                cards.map(c =>
                    `<div style="background:${c.bg};border-radius:6px;padding:8px;text-align:center;border:1px solid ${c.color}22">
                        <div style="font-size:1.5em;font-weight:800;color:${c.color};line-height:1">${c.value}</div>
                        <div style="font-size:9px;color:#6b7280;margin-top:3px">${c.label}</div>
                    </div>`
                ).join('') + '</div>';
        },

        _initChart(id) {
            const canvas = document.getElementById('chart-' + id);
            if (!canvas || typeof Chart === 'undefined') return;
            const widget = (this.currentSlide?.widgets || []).find(w => w.id === id);
            const chartType = widget?.config?.chart_type || 'tasks_by_status';
            const d = CHART_DATA.tasksByStatus;
            let type = 'doughnut', data, options = {};

            if (chartType === 'workload') {
                type = 'bar';
                const members = PROJECT_DATA.members.slice(0, 8);
                data = {
                    labels: members.map(m => m.name.split(' ')[0]),
                    datasets: [{ label:'Tasks', data: members.map(m => m.task_count || 0),
                        backgroundColor:'#6366f1', borderRadius:4 }]
                };
                options = { indexAxis:'y', responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{font:{size:9}} }, y:{ ticks:{font:{size:9}} } } };
            } else if (chartType === 'burndown') {
                type = 'line';
                data = {
                    labels: ['W1','W2','W3','W4','W5','W6'],
                    datasets: [
                        { label:'Ideal',  data:[100,80,60,40,20,0],  borderColor:'#94a3b8', borderDash:[4,2], pointRadius:0, tension:0 },
                        { label:'Actual', data:[100,85,65,50,35,null], borderColor:'#6366f1', fill:false, tension:0.3, pointRadius:3 },
                    ]
                };
                options = { responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ position:'bottom', labels:{ font:{size:9} } } },
                    scales:{ y:{ min:0, max:100, ticks:{font:{size:9}} }, x:{ ticks:{font:{size:9}} } } };
            } else {
                data = {
                    labels: ['Todo','In Progress','Review','Done','Cancelled'],
                    datasets: [{ data:[d.todo,d.in_progress,d.review,d.done,d.cancelled],
                        backgroundColor:['#94a3b8','#6366f1','#f59e0b','#22c55e','#ef4444'],
                        borderWidth:1, borderColor:'#fff' }]
                };
                options = { responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ position:'right', labels:{ font:{size:9}, boxWidth:10 } } } };
            }

            this._chartInstances[id] = new Chart(canvas, { type, data, options });
        },

        _renderGantt() {
            const allTasks = PROJECT_DATA.tasks.filter(t => t.start_date && t.due_date);
            if (!allTasks.length) return '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:12px">No tasks with scheduled dates</div>';

            const LABEL_W = 190, ROW_H = 26, HEADER_H = 30, TOTAL_W = 800;
            const CHART_W = TOTAL_W - LABEL_W;
            const colors = { done:'#16a34a', in_progress:'#2563eb', review:'#d97706', todo:'#9ca3af', cancelled:'#ef4444' };

            // Group by milestone
            const msMap = {};
            (PROJECT_DATA.milestones || []).forEach(m => { msMap[m.id] = m; });
            const grouped = {};
            allTasks.forEach(t => {
                const mid = t.milestone_id || '__none__';
                if (!grouped[mid]) grouped[mid] = [];
                grouped[mid].push(t);
            });

            const rows = [];
            Object.keys(grouped).forEach(mid => {
                const m = mid === '__none__' ? { name:'No Phase', due_date:'' } : msMap[mid];
                if (m) rows.push({ isMilestone:true, data:m });
                grouped[mid].forEach(t => rows.push({ isMilestone:false, data:t }));
            });

            const TOTAL_H = HEADER_H + rows.length * ROW_H + 4;
            const dates = allTasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
            const minD = new Date(Math.min(...dates)), maxD = new Date(Math.max(...dates));
            const totalMs = Math.max(1, maxD - minD);

            let s = `<svg viewBox="0 0 ${TOTAL_W} ${TOTAL_H}" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="font-family:sans-serif;">`;
            s += `<rect width="${TOTAL_W}" height="${TOTAL_H}" fill="white"/>`;

            // Header
            s += `<rect x="0" y="0" width="${TOTAL_W}" height="${HEADER_H}" fill="#f8fafc"/>`;
            s += `<text x="6" y="${HEADER_H/2+4}" font-size="10" fill="#64748b" font-weight="600">Task</text>`;
            s += `<text x="${LABEL_W+6}" y="${HEADER_H/2+4}" font-size="10" fill="#64748b" font-weight="600">Timeline</text>`;

            // Month grid lines
            let cur = new Date(minD.getFullYear(), minD.getMonth(), 1);
            while (cur <= maxD) {
                const x = LABEL_W + (cur - minD) / totalMs * CHART_W;
                s += `<line x1="${x}" y1="${HEADER_H}" x2="${x}" y2="${TOTAL_H}" stroke="#e2e8f0" stroke-width="0.5"/>`;
                s += `<text x="${x+3}" y="${HEADER_H/2+4}" font-size="8" fill="#94a3b8">${cur.toLocaleDateString('en',{month:'short',year:'2-digit'})}</text>`;
                cur = new Date(cur.getFullYear(), cur.getMonth()+1, 1);
            }

            // Dividers
            s += `<line x1="${LABEL_W}" y1="0" x2="${LABEL_W}" y2="${TOTAL_H}" stroke="#e2e8f0" stroke-width="1"/>`;
            s += `<line x1="0" y1="${HEADER_H}" x2="${TOTAL_W}" y2="${HEADER_H}" stroke="#e2e8f0" stroke-width="1"/>`;

            rows.forEach((row, i) => {
                const y = HEADER_H + i * ROW_H;
                if (row.isMilestone) {
                    s += `<rect x="0" y="${y}" width="${TOTAL_W}" height="${ROW_H}" fill="#1e3a5f"/>`;
                    const name = (row.data.name || '').substring(0, 28);
                    s += `<text x="6" y="${y+ROW_H/2+4}" font-size="9" fill="white" font-weight="700">⚑ ${name}</text>`;
                    if (row.data.due_date) {
                        s += `<text x="${TOTAL_W-5}" y="${y+ROW_H/2+4}" font-size="8" fill="#94a3b8" text-anchor="end">${row.data.due_date}</text>`;
                    }
                } else {
                    const t = row.data;
                    const bg = i % 2 === 0 ? '#ffffff' : '#f8fafc';
                    const color = colors[t.status] || '#9ca3af';
                    s += `<rect x="0" y="${y}" width="${TOTAL_W}" height="${ROW_H}" fill="${bg}"/>`;
                    s += `<line x1="0" y1="${y+ROW_H}" x2="${TOTAL_W}" y2="${y+ROW_H}" stroke="#f1f5f9" stroke-width="0.5"/>`;
                    s += `<circle cx="10" cy="${y+ROW_H/2}" r="3.5" fill="${color}"/>`;
                    const lbl = (t.title || '').substring(0, 22);
                    s += `<text x="18" y="${y+ROW_H/2+4}" font-size="9" fill="#374151">${lbl}</text>`;
                    if (t.start_date && t.due_date) {
                        const bx = LABEL_W + (new Date(t.start_date) - minD) / totalMs * CHART_W;
                        const bw = Math.max(4, (new Date(t.due_date) - new Date(t.start_date)) / totalMs * CHART_W);
                        const barY = y + 5, barH = ROW_H - 10;
                        s += `<rect x="${bx}" y="${barY}" width="${bw}" height="${barH}" rx="2" fill="${color}" opacity="0.22"/>`;
                        if ((t.progress_pct || 0) > 0) {
                            s += `<rect x="${bx}" y="${barY}" width="${bw*(t.progress_pct/100)}" height="${barH}" rx="2" fill="${color}"/>`;
                        }
                        if (bw > 28) {
                            s += `<text x="${bx+bw/2}" y="${barY+barH/2+3}" text-anchor="middle" font-size="7" fill="white" font-weight="600">${t.progress_pct||0}%</text>`;
                        }
                    }
                }
            });

            // Today line
            const now = new Date();
            if (now >= minD && now <= maxD) {
                const tx = LABEL_W + (now - minD) / totalMs * CHART_W;
                s += `<line x1="${tx}" y1="0" x2="${tx}" y2="${TOTAL_H}" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="4,2"/>`;
                s += `<rect x="${tx-10}" y="0" width="20" height="13" rx="2" fill="#ef4444"/>`;
                s += `<text x="${tx}" y="9" text-anchor="middle" font-size="6.5" fill="white" font-weight="700">TODAY</text>`;
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
            return `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px;padding:8px">` +
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
                    id:           s.id,
                    slide_order:  i,
                    bg_color:     s.bg_color || '#ffffff',
                    notes:        s.notes || '',
                    html_content: s.html_content || '',
                    widgets:      s.widgets || [],
                    elements:     [],
                }));
                const res = await fetch(SAVE_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ slides }),
                });
                const data = await res.json();
                if (data.success) {
                    this.slides = data.slides.map(s => ({
                        ...s,
                        widgets: Array.isArray(s.widgets_data)
                            ? s.widgets_data.map(w => ({ config:{}, data_mode:'live', ...w }))
                            : [],
                    }));
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
