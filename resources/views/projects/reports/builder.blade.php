@extends('layouts.app')

@section('title', $report->title . ' — Builder')

@push('styles')
<style>
/* Builder layout */
#report-builder { display:flex; flex-direction:column; height:calc(100vh - 56px); overflow:hidden; user-select:none; }
.builder-toolbar { flex-shrink:0; height:48px; display:flex; align-items:center; gap:8px; padding:0 12px;
    background:#1e1e2e; border-bottom:1px solid #2d2d44; }
.builder-body { flex:1; display:flex; overflow:hidden; }
.slide-panel { width:160px; flex-shrink:0; overflow-y:auto; background:#16162a; border-right:1px solid #2d2d44; padding:10px 8px; }
.canvas-area { flex:1; overflow:auto; background:#111122; display:flex; align-items:flex-start; justify-content:center; padding:24px; }
.props-panel { width:260px; flex-shrink:0; overflow-y:auto; background:#16162a; border-left:1px solid #2d2d44; padding:12px; }

/* Canvas */
.report-canvas { position:relative; width:960px; height:540px; flex-shrink:0; box-shadow:0 8px 40px rgba(0,0,0,0.6); overflow:hidden; cursor:default; }

/* Slide thumbnail */
.slide-thumb { position:relative; width:100%; padding-top:56.25%; cursor:pointer; border-radius:6px; overflow:hidden;
    border:2px solid transparent; margin-bottom:8px; transition:border-color 0.15s; }
.slide-thumb.active { border-color:#6366f1; }
.slide-thumb-inner { position:absolute; inset:0; overflow:hidden; }
.slide-thumb-label { position:absolute; bottom:2px; left:0; right:0; text-align:center; font-size:9px; color:#888; }

/* Canvas element */
.canvas-el { position:absolute; box-sizing:border-box; }
.canvas-el.selected { outline:2px solid #6366f1; outline-offset:1px; }
.canvas-el .resize-handle { position:absolute; width:8px; height:8px; background:#6366f1; border:2px solid #fff;
    border-radius:50%; z-index:100; }
.canvas-el .resize-handle[data-handle="nw"] { top:-4px; left:-4px; cursor:nw-resize; }
.canvas-el .resize-handle[data-handle="ne"] { top:-4px; right:-4px; cursor:ne-resize; }
.canvas-el .resize-handle[data-handle="sw"] { bottom:-4px; left:-4px; cursor:sw-resize; }
.canvas-el .resize-handle[data-handle="se"] { bottom:-4px; right:-4px; cursor:se-resize; }
.canvas-el .resize-handle[data-handle="n"]  { top:-4px; left:calc(50% - 4px); cursor:n-resize; }
.canvas-el .resize-handle[data-handle="s"]  { bottom:-4px; left:calc(50% - 4px); cursor:s-resize; }
.canvas-el .resize-handle[data-handle="e"]  { right:-4px; top:calc(50% - 4px); cursor:e-resize; }
.canvas-el .resize-handle[data-handle="w"]  { left:-4px; top:calc(50% - 4px); cursor:w-resize; }

/* Element types */
.el-text { overflow:hidden; display:flex; align-items:flex-start; }
.el-text [contenteditable] { outline:none; width:100%; min-height:100%; }
.el-kpi { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }
.el-shape { width:100%; height:100%; }

/* Toolbar buttons */
.tb-btn { display:flex; align-items:center; gap:4px; padding:4px 8px; border-radius:6px; font-size:12px;
    color:#ccc; cursor:pointer; border:none; background:transparent; white-space:nowrap; transition:background 0.15s; }
.tb-btn:hover { background:#2d2d44; color:#fff; }
.tb-btn.active { background:#4f46e5; color:#fff; }
.tb-sep { width:1px; height:24px; background:#2d2d44; margin:0 4px; }

/* Props panel labels */
.prop-label { font-size:11px; color:#888; margin-bottom:3px; }
.prop-input { width:100%; padding:4px 8px; border-radius:5px; background:#0f0f1a; border:1px solid #2d2d44;
    color:#ddd; font-size:12px; outline:none; }
.prop-input:focus { border-color:#6366f1; }
.prop-section { border-top:1px solid #2d2d44; padding-top:10px; margin-top:10px; }

/* Save indicator */
.save-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-left:4px; }
.save-dot.dirty { background:#f59e0b; }
.save-dot.saved { background:#22c55e; }
.save-dot.saving { background:#6366f1; animation:pulse 1s infinite; }
</style>
@endpush

@section('content')
<div id="report-builder" x-data="reportBuilder()" x-init="init()">

    {{-- ── Toolbar ── --}}
    <div class="builder-toolbar">
        <a href="{{ route('projects.reports.index', $project) }}"
           class="tb-btn" title="{{ app()->getLocale() === 'th' ? 'กลับ' : 'Back' }}">
            <i class="ti ti-arrow-left"></i>
        </a>
        <div class="tb-sep"></div>

        {{-- Title --}}
        <span class="text-sm text-gray-300 font-medium max-w-[180px] truncate">{{ $report->title }}</span>
        <span class="save-dot" :class="isSaving ? 'saving' : isDirty ? 'dirty' : 'saved'"></span>

        <div class="flex-1"></div>

        {{-- Add elements --}}
        <span class="text-xs text-gray-500 mr-1">{{ app()->getLocale() === 'th' ? 'เพิ่ม:' : 'Add:' }}</span>
        <button class="tb-btn" @click="addElement('text')"><i class="ti ti-text-size"></i> Text</button>
        <button class="tb-btn" @click="addElement('kpi')"><i class="ti ti-chart-bar"></i> KPI</button>
        <button class="tb-btn" @click="addElement('chart')"><i class="ti ti-chart-donut"></i> Chart</button>
        <button class="tb-btn" @click="addElement('shape')"><i class="ti ti-square"></i> Shape</button>
        <button class="tb-btn" @click="$refs.imgInput.click()"><i class="ti ti-photo"></i> Image</button>
        <input type="file" x-ref="imgInput" accept="image/*" class="hidden" @change="uploadImage($event)">

        <div class="tb-sep"></div>

        {{-- Delete --}}
        <button class="tb-btn" @click="deleteSelected()" :disabled="!selectedId"
                :class="selectedId ? 'text-red-400 hover:bg-red-900/30' : 'opacity-30 cursor-not-allowed'">
            <i class="ti ti-trash"></i>
        </button>
        <div class="tb-sep"></div>

        {{-- Save / Preview --}}
        <button class="tb-btn" @click="save()" :disabled="isSaving">
            <i class="ti ti-device-floppy"></i> {{ app()->getLocale() === 'th' ? 'บันทึก' : 'Save' }}
        </button>
        <a href="{{ route('projects.reports.preview', [$project, $report]) }}" target="_blank"
           class="tb-btn"><i class="ti ti-eye"></i> {{ app()->getLocale() === 'th' ? 'ดูตัวอย่าง' : 'Preview' }}</a>
        <a href="{{ route('projects.reports.export', [$project, $report]) }}" target="_blank"
           class="tb-btn"><i class="ti ti-printer"></i> {{ app()->getLocale() === 'th' ? 'Export' : 'Export' }}</a>

        {{-- Save as template --}}
        <div class="relative" x-data="{ open: false }">
            <button class="tb-btn" @click="open = !open"><i class="ti ti-dots-vertical"></i></button>
            <div x-show="open" @click.outside="open = false"
                 class="absolute right-0 top-10 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50 py-1">
                <button @click="openTemplateSave(); open = false"
                        class="w-full text-left px-4 py-2 text-xs text-gray-300 hover:bg-gray-700">
                    <i class="ti ti-template mr-1"></i>
                    {{ app()->getLocale() === 'th' ? 'บันทึกเป็นเทมเพลต' : 'Save as Template' }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="builder-body">

        {{-- Slide Panel --}}
        <div class="slide-panel">
            <div class="text-xs text-gray-500 mb-2 px-1">{{ app()->getLocale() === 'th' ? 'สไลด์' : 'Slides' }}</div>

            <template x-for="(slide, idx) in slides" :key="slide.id">
                <div class="relative group">
                    <div class="slide-thumb" :class="idx === currentSlideIndex ? 'active' : ''"
                         @click="currentSlideIndex = idx; selectedId = null">
                        <div class="slide-thumb-inner" :style="{ background: slide.bg_color }">
                            <svg width="100%" height="100%" viewBox="0 0 960 540" style="position:absolute;inset:0">
                                <template x-for="el in slide.elements" :key="el.id">
                                    <g>
                                        <rect x-show="el.type === 'shape'"
                                              :x="el.x" :y="el.y" :width="el.w" :height="el.h"
                                              :fill="el.props.fill ?? '#4f46e5'" :opacity="el.props.opacity ?? 1"></rect>
                                        <rect x-show="el.type !== 'shape'"
                                              :x="el.x" :y="el.y" :width="el.w" :height="el.h"
                                              :fill="el.type === 'text' ? (el.props.bg_color ?? 'none') : '#e0e7ff'"
                                              opacity="0.6"></rect>
                                    </g>
                                </template>
                            </svg>
                        </div>
                        <div class="slide-thumb-label" x-text="idx + 1"></div>
                    </div>
                    {{-- Slide actions --}}
                    <div class="hidden group-hover:flex absolute top-1 right-1 gap-1 z-10">
                        <button @click.stop="duplicateSlide(idx)"
                                class="w-5 h-5 rounded bg-gray-700 text-gray-300 hover:bg-gray-600 flex items-center justify-center">
                            <i class="ti ti-copy" style="font-size:9px"></i>
                        </button>
                        <button @click.stop="deleteSlide(idx)" x-show="slides.length > 1"
                                class="w-5 h-5 rounded bg-red-800 text-red-300 hover:bg-red-700 flex items-center justify-center">
                            <i class="ti ti-trash" style="font-size:9px"></i>
                        </button>
                    </div>
                </div>
            </template>

            <button @click="addSlide()"
                    class="w-full mt-1 py-2 rounded-lg border border-dashed border-gray-600 text-gray-500 hover:border-indigo-500 hover:text-indigo-400 text-xs transition-colors">
                + {{ app()->getLocale() === 'th' ? 'เพิ่มสไลด์' : 'Add Slide' }}
            </button>
        </div>

        {{-- Canvas Area --}}
        <div class="canvas-area" x-ref="canvasContainer" @click.self="selectedId = null">
            <div class="report-canvas"
                 :style="`background:${currentSlide ? currentSlide.bg_color : '#fff'};transform:scale(${canvasScale});transform-origin:top center;`"
                 @click.self="selectedId = null">

                <template x-if="currentSlide">
                    <template x-for="el in currentSlide.elements" :key="el.id">
                        <div class="canvas-el"
                             :class="selectedId === el.id ? 'selected' : ''"
                             :style="`left:${el.x}px;top:${el.y}px;width:${el.w}px;height:${el.h}px;z-index:${el.z_index}`"
                             @mousedown="startDrag($event, el)"
                             @click.stop="selectedId = el.id">

                            {{-- Text element --}}
                            <div x-show="el.type === 'text'" class="el-text w-full h-full"
                                 :style="`font-size:${el.props.font_size ?? 20}px;font-weight:${el.props.font_weight ?? 'normal'};color:${el.props.color ?? '#1a1a1a'};text-align:${el.props.align ?? 'left'};background:${el.props.bg_color ?? 'transparent'};padding:8px;font-style:${el.props.italic ? 'italic' : 'normal'};line-height:${el.props.line_height ?? 1.4};`">
                                <div contenteditable="true" x-ref="textEl"
                                     @dblclick.stop="$el.focus()"
                                     @mousedown.stop
                                     @blur="el.props.content = $el.innerHTML; isDirty = true"
                                     x-html="el.props.content"
                                     style="width:100%;min-height:100%;outline:none;cursor:text"></div>
                            </div>

                            {{-- KPI element --}}
                            <div x-show="el.type === 'kpi'" class="el-kpi w-full h-full rounded-lg"
                                 :style="`background:${el.props.bg ?? '#f5f3ff'};border:2px solid ${el.props.accent ?? '#4f46e5'};`">
                                <div class="text-2xl font-bold" :style="`color:${el.props.accent ?? '#4f46e5'}`"
                                     x-text="(el.props.prefix ?? '') + kpiValue(el.props.data_source) + (el.props.suffix ?? '')"></div>
                                <div class="text-xs text-gray-500 mt-1" x-text="el.props.label ?? ''"></div>
                            </div>

                            {{-- Chart element --}}
                            <div x-show="el.type === 'chart'" class="w-full h-full flex flex-col items-center justify-center bg-white/80 rounded-lg p-2">
                                <div class="text-xs font-medium text-gray-600 mb-1" x-text="el.props.title ?? 'Chart'"></div>
                                <canvas :id="'chart-' + el.id" style="max-width:100%;max-height:calc(100% - 24px)"></canvas>
                            </div>

                            {{-- Image element --}}
                            <div x-show="el.type === 'image'" class="w-full h-full overflow-hidden rounded"
                                 :style="`background:#e5e7eb`">
                                <img x-show="el.props.url" :src="el.props.url"
                                     :style="`width:100%;height:100%;object-fit:${el.props.fit ?? 'cover'}`"
                                     alt="">
                                <div x-show="!el.props.url"
                                     class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="ti ti-photo text-3xl"></i>
                                </div>
                            </div>

                            {{-- Shape element --}}
                            <div x-show="el.type === 'shape'" class="el-shape"
                                 :style="`background:${el.props.fill ?? '#4f46e5'};opacity:${el.props.opacity ?? 1};border-radius:${el.props.border_radius ?? 0}px;border:${el.props.border_width ?? 0}px solid ${el.props.border_color ?? '#000'}`">
                            </div>

                            {{-- Resize handles (only for selected) --}}
                            <template x-if="selectedId === el.id">
                                <div>
                                    <div class="resize-handle" data-handle="nw" @mousedown.stop="startResize($event, el, 'nw')"></div>
                                    <div class="resize-handle" data-handle="n"  @mousedown.stop="startResize($event, el, 'n')"></div>
                                    <div class="resize-handle" data-handle="ne" @mousedown.stop="startResize($event, el, 'ne')"></div>
                                    <div class="resize-handle" data-handle="e"  @mousedown.stop="startResize($event, el, 'e')"></div>
                                    <div class="resize-handle" data-handle="se" @mousedown.stop="startResize($event, el, 'se')"></div>
                                    <div class="resize-handle" data-handle="s"  @mousedown.stop="startResize($event, el, 's')"></div>
                                    <div class="resize-handle" data-handle="sw" @mousedown.stop="startResize($event, el, 'sw')"></div>
                                    <div class="resize-handle" data-handle="w"  @mousedown.stop="startResize($event, el, 'w')"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </template>
            </div>
        </div>

        {{-- Properties Panel --}}
        <div class="props-panel" x-show="!!selectedElement">
            <div class="text-xs font-semibold text-gray-300 uppercase tracking-wider mb-3">
                {{ app()->getLocale() === 'th' ? 'คุณสมบัติ' : 'Properties' }}
            </div>

            <template x-if="selectedElement">
                <div class="space-y-3">
                    {{-- Position & Size --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="prop-label">X</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.x)"
                                   @input="selectedElement.x = +$event.target.value; isDirty = true">
                        </div>
                        <div>
                            <div class="prop-label">Y</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.y)"
                                   @input="selectedElement.y = +$event.target.value; isDirty = true">
                        </div>
                        <div>
                            <div class="prop-label">W</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.w)"
                                   @input="selectedElement.w = Math.max(10, +$event.target.value); isDirty = true">
                        </div>
                        <div>
                            <div class="prop-label">H</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.h)"
                                   @input="selectedElement.h = Math.max(10, +$event.target.value); isDirty = true">
                        </div>
                    </div>

                    <div>
                        <div class="prop-label">Z-index</div>
                        <input type="number" class="prop-input" :value="selectedElement.z_index"
                               @input="selectedElement.z_index = +$event.target.value; isDirty = true">
                    </div>

                    {{-- Text props --}}
                    <template x-if="selectedElement.type === 'text'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">{{ app()->getLocale() === 'th' ? 'ข้อความ' : 'Text' }}</div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'ขนาดตัวอักษร' : 'Font Size' }}</div>
                                <input type="number" min="8" max="200" class="prop-input"
                                       :value="selectedElement.props.font_size ?? 20"
                                       @input="selectedElement.props.font_size = +$event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'สีตัวอักษร' : 'Color' }}</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer"
                                       :value="selectedElement.props.color ?? '#1a1a1a'"
                                       @input="selectedElement.props.color = $event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'สีพื้นหลัง' : 'Background' }}</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer"
                                       :value="selectedElement.props.bg_color ?? '#ffffff'"
                                       @input="selectedElement.props.bg_color = $event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'การจัดชิด' : 'Align' }}</div>
                                <div class="flex gap-1">
                                    <template x-for="a in ['left','center','right']" :key="a">
                                        <button class="flex-1 py-1 rounded text-xs border"
                                                :class="selectedElement.props.align === a ? 'bg-indigo-600 border-indigo-500 text-white' : 'border-gray-600 text-gray-400'"
                                                @click="selectedElement.props.align = a; isDirty = true"
                                                x-text="a"></button>
                                    </template>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-1 text-xs text-gray-400 cursor-pointer">
                                    <input type="checkbox" :checked="selectedElement.props.font_weight === 'bold'"
                                           @change="selectedElement.props.font_weight = $event.target.checked ? 'bold' : 'normal'; isDirty = true">
                                    Bold
                                </label>
                                <label class="flex items-center gap-1 text-xs text-gray-400 cursor-pointer">
                                    <input type="checkbox" :checked="selectedElement.props.italic"
                                           @change="selectedElement.props.italic = $event.target.checked; isDirty = true">
                                    Italic
                                </label>
                            </div>
                            <div>
                                <div class="prop-label">Line Height</div>
                                <input type="range" min="1" max="3" step="0.1" class="w-full"
                                       :value="selectedElement.props.line_height ?? 1.4"
                                       @input="selectedElement.props.line_height = +$event.target.value; isDirty = true">
                            </div>
                        </div>
                    </template>

                    {{-- KPI props --}}
                    <template x-if="selectedElement.type === 'kpi'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">KPI</div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'แหล่งข้อมูล' : 'Data Source' }}</div>
                                <select class="prop-input"
                                        :value="selectedElement.props.data_source ?? 'total'"
                                        @change="selectedElement.props.data_source = $event.target.value; isDirty = true">
                                    <option value="total">Total Tasks</option>
                                    <option value="done">Done Tasks</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="progress_pct">Progress %</option>
                                    <option value="members">Members</option>
                                </select>
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'ป้ายกำกับ' : 'Label' }}</div>
                                <input type="text" class="prop-input"
                                       :value="selectedElement.props.label ?? ''"
                                       @input="selectedElement.props.label = $event.target.value; isDirty = true">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <div class="prop-label">Prefix</div>
                                    <input type="text" class="prop-input" maxlength="5"
                                           :value="selectedElement.props.prefix ?? ''"
                                           @input="selectedElement.props.prefix = $event.target.value; isDirty = true">
                                </div>
                                <div>
                                    <div class="prop-label">Suffix</div>
                                    <input type="text" class="prop-input" maxlength="5"
                                           :value="selectedElement.props.suffix ?? ''"
                                           @input="selectedElement.props.suffix = $event.target.value; isDirty = true">
                                </div>
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'สีหลัก' : 'Accent' }}</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer"
                                       :value="selectedElement.props.accent ?? '#4f46e5'"
                                       @input="selectedElement.props.accent = $event.target.value; isDirty = true">
                            </div>
                        </div>
                    </template>

                    {{-- Chart props --}}
                    <template x-if="selectedElement.type === 'chart'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Chart</div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'ประเภทกราฟ' : 'Chart Type' }}</div>
                                <select class="prop-input"
                                        :value="selectedElement.props.chart_type ?? 'doughnut'"
                                        @change="selectedElement.props.chart_type = $event.target.value; isDirty = true; $nextTick(() => renderAllCharts())">
                                    <option value="doughnut">Doughnut</option>
                                    <option value="bar">Bar</option>
                                    <option value="pie">Pie</option>
                                    <option value="line">Line</option>
                                </select>
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'แหล่งข้อมูล' : 'Data Source' }}</div>
                                <select class="prop-input"
                                        :value="selectedElement.props.data_source ?? 'status'"
                                        @change="selectedElement.props.data_source = $event.target.value; isDirty = true; $nextTick(() => renderAllCharts())">
                                    <option value="status">Tasks by Status</option>
                                    <option value="priority">Tasks by Priority</option>
                                    <option value="assignee">Tasks by Assignee</option>
                                </select>
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'ชื่อกราฟ' : 'Title' }}</div>
                                <input type="text" class="prop-input"
                                       :value="selectedElement.props.title ?? ''"
                                       @input="selectedElement.props.title = $event.target.value; isDirty = true">
                            </div>
                        </div>
                    </template>

                    {{-- Shape props --}}
                    <template x-if="selectedElement.type === 'shape'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Shape</div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'สีพื้น' : 'Fill' }}</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer"
                                       :value="selectedElement.props.fill ?? '#4f46e5'"
                                       @input="selectedElement.props.fill = $event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'ความโปร่งใส' : 'Opacity' }}</div>
                                <input type="range" min="0" max="1" step="0.05" class="w-full"
                                       :value="selectedElement.props.opacity ?? 1"
                                       @input="selectedElement.props.opacity = +$event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">Border Radius</div>
                                <input type="range" min="0" max="100" step="1" class="w-full"
                                       :value="selectedElement.props.border_radius ?? 0"
                                       @input="selectedElement.props.border_radius = +$event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'ความหนาขอบ' : 'Border Width' }}</div>
                                <input type="number" min="0" max="20" class="prop-input"
                                       :value="selectedElement.props.border_width ?? 0"
                                       @input="selectedElement.props.border_width = +$event.target.value; isDirty = true">
                            </div>
                            <div>
                                <div class="prop-label">{{ app()->getLocale() === 'th' ? 'สีขอบ' : 'Border Color' }}</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer"
                                       :value="selectedElement.props.border_color ?? '#000000'"
                                       @input="selectedElement.props.border_color = $event.target.value; isDirty = true">
                            </div>
                        </div>
                    </template>

                    {{-- Image props --}}
                    <template x-if="selectedElement.type === 'image'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Image</div>
                            <div>
                                <div class="prop-label">Fit</div>
                                <select class="prop-input"
                                        :value="selectedElement.props.fit ?? 'cover'"
                                        @change="selectedElement.props.fit = $event.target.value; isDirty = true">
                                    <option value="cover">Cover</option>
                                    <option value="contain">Contain</option>
                                    <option value="fill">Fill</option>
                                </select>
                            </div>
                            <button class="w-full py-1.5 rounded border border-gray-600 text-xs text-gray-300 hover:bg-gray-700"
                                    @click="replaceImage(selectedElement)">
                                <i class="ti ti-upload mr-1"></i>
                                {{ app()->getLocale() === 'th' ? 'เปลี่ยนรูป' : 'Replace Image' }}
                            </button>
                        </div>
                    </template>

                    {{-- Slide Background --}}
                    <div class="prop-section">
                        <div class="prop-label font-semibold text-gray-400 mb-2">
                            {{ app()->getLocale() === 'th' ? 'พื้นหลังสไลด์' : 'Slide Background' }}
                        </div>
                        <input type="color" class="w-full h-8 rounded cursor-pointer"
                               :value="currentSlide ? currentSlide.bg_color : '#ffffff'"
                               @input="if(currentSlide) { currentSlide.bg_color = $event.target.value; isDirty = true }">
                    </div>

                    {{-- Delete --}}
                    <button @click="deleteSelected()"
                            class="w-full mt-2 py-2 rounded-lg bg-red-900/30 border border-red-800 text-red-400 text-xs hover:bg-red-900/50">
                        <i class="ti ti-trash mr-1"></i>
                        {{ app()->getLocale() === 'th' ? 'ลบองค์ประกอบ' : 'Delete Element' }}
                    </button>
                </div>
            </template>

            {{-- No selection state --}}
            <template x-if="!selectedElement">
                <div>
                    <div class="prop-section">
                        <div class="prop-label font-semibold text-gray-400 mb-2">
                            {{ app()->getLocale() === 'th' ? 'พื้นหลังสไลด์' : 'Slide Background' }}
                        </div>
                        <input type="color" class="w-full h-8 rounded cursor-pointer"
                               :value="currentSlide ? currentSlide.bg_color : '#ffffff'"
                               @input="if(currentSlide) { currentSlide.bg_color = $event.target.value; isDirty = true }">
                    </div>
                    <p class="text-xs text-gray-600 mt-4 text-center">
                        {{ app()->getLocale() === 'th' ? 'คลิกที่องค์ประกอบเพื่อแก้ไข' : 'Click an element to edit' }}
                    </p>
                </div>
            </template>
        </div>
    </div>

    {{-- Save as Template Modal --}}
    <div x-show="showTemplateSave" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         @keydown.escape.window="showTemplateSave = false">
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-80 shadow-2xl">
            <h3 class="text-sm font-semibold text-gray-200 mb-4">
                {{ app()->getLocale() === 'th' ? 'บันทึกเป็นเทมเพลต' : 'Save as Template' }}
            </h3>
            <input type="text" x-model="templateName"
                   placeholder="{{ app()->getLocale() === 'th' ? 'ชื่อเทมเพลต' : 'Template name' }}"
                   class="w-full prop-input mb-4">
            <div class="flex gap-3">
                <button @click="showTemplateSave = false"
                        class="flex-1 py-2 rounded-lg border border-gray-600 text-xs text-gray-400 hover:bg-gray-700">
                    {{ app()->getLocale() === 'th' ? 'ยกเลิก' : 'Cancel' }}
                </button>
                <button @click="doSaveAsTemplate()"
                        class="flex-1 py-2 rounded-lg bg-indigo-600 text-xs text-white hover:bg-indigo-700">
                    {{ app()->getLocale() === 'th' ? 'บันทึก' : 'Save' }}
                </button>
            </div>
        </div>
    </div>

    <input type="file" x-ref="replaceImgInput" accept="image/*" class="hidden" @change="doReplaceImage($event)">
</div>
@endsection

@push('scripts')
<script>
const REPORT_DATA = @json([
    'id'     => $report->id,
    'title'  => $report->title,
    'slides' => $report->slides->map(fn($s) => [
        'id'          => $s->id,
        'slide_order' => $s->slide_order,
        'bg_color'    => $s->bg_color,
        'notes'       => $s->notes ?? '',
        'elements'    => $s->elements->map(fn($e) => [
            'id'      => $e->id,
            'type'    => $e->type,
            'x'       => $e->x,
            'y'       => $e->y,
            'w'       => $e->w,
            'h'       => $e->h,
            'z_index' => $e->z_index,
            'props'   => $e->props,
        ])->values(),
    ])->values(),
]);

const PROJECT_KPI   = @json($kpi);
const CHART_DATA    = @json($chartData);
const SAVE_URL      = '{{ route('projects.reports.save', [$project, $report]) }}';
const ATTACH_URL    = '{{ route('projects.reports.attachments.store', [$project, $report]) }}';
const TEMPLATE_URL  = '{{ route('projects.reports.save-as-template', [$project, $report]) }}';

function reportBuilder() {
    return {
        slides: [],
        currentSlideIndex: 0,
        selectedId: null,
        canvasScale: 1,
        isDirty: false,
        isSaving: false,
        showTemplateSave: false,
        templateName: '',
        _replaceTarget: null,
        _chartInstances: {},

        init() {
            this.slides = JSON.parse(JSON.stringify(REPORT_DATA.slides || []));
            if (!this.slides.length) this.addSlide();
            this.$nextTick(() => {
                this.recalcScale();
                this.renderAllCharts();
            });
            window.addEventListener('resize', () => this.recalcScale());

            // Keyboard shortcuts
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' || e.key === 'Backspace') {
                    if (document.activeElement.tagName === 'INPUT' ||
                        document.activeElement.tagName === 'TEXTAREA' ||
                        document.activeElement.contentEditable === 'true') return;
                    this.deleteSelected();
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.save();
                }
            });

            // Auto-save every 60s
            setInterval(() => { if (this.isDirty && !this.isSaving) this.save(); }, 60000);
        },

        get currentSlide() { return this.slides[this.currentSlideIndex] || null; },
        get selectedElement() {
            if (!this.selectedId || !this.currentSlide) return null;
            return this.currentSlide.elements.find(e => e.id === this.selectedId) || null;
        },

        recalcScale() {
            const el = this.$refs.canvasContainer;
            if (!el) return;
            const cw = el.clientWidth - 48;
            const ch = el.clientHeight - 48;
            this.canvasScale = Math.min(cw / 960, ch / 540, 1);
        },

        // Slides
        addSlide() {
            const s = {
                id: 'new_' + Date.now(),
                slide_order: this.slides.length,
                bg_color: '#ffffff',
                notes: '',
                elements: [],
            };
            this.slides.push(s);
            this.currentSlideIndex = this.slides.length - 1;
            this.selectedId = null;
            this.isDirty = true;
        },

        deleteSlide(idx) {
            if (this.slides.length <= 1) return;
            this.slides.splice(idx, 1);
            if (this.currentSlideIndex >= this.slides.length) this.currentSlideIndex = this.slides.length - 1;
            this.selectedId = null;
            this.isDirty = true;
        },

        duplicateSlide(idx) {
            const dup = JSON.parse(JSON.stringify(this.slides[idx]));
            dup.id = 'new_' + Date.now();
            dup.elements = dup.elements.map(e => ({ ...e, id: 'new_' + Date.now() + '_' + Math.random().toString(36).slice(2) }));
            this.slides.splice(idx + 1, 0, dup);
            this.currentSlideIndex = idx + 1;
            this.isDirty = true;
            this.$nextTick(() => this.renderAllCharts());
        },

        // Elements
        addElement(type) {
            if (!this.currentSlide) return;
            const defaults = {
                text:  { x: 80, y: 80, w: 400, h: 80,  props: { content: '<p>Click to edit</p>', font_size: 24, font_weight: 'normal', color: '#1a1a1a', align: 'left', bg_color: null, italic: false, line_height: 1.4 } },
                kpi:   { x: 80, y: 80, w: 220, h: 130, props: { data_source: 'total', label: 'Total Tasks', prefix: '', suffix: '', accent: '#4f46e5', bg: '#f5f3ff' } },
                chart: { x: 80, y: 80, w: 380, h: 280, props: { chart_type: 'doughnut', data_source: 'status', title: 'Task Status' } },
                shape: { x: 80, y: 80, w: 240, h: 100, props: { fill: '#4f46e5', opacity: 1, border_radius: 8, border_width: 0, border_color: '#000000' } },
                image: { x: 80, y: 80, w: 300, h: 200, props: { attachment_id: null, url: null, fit: 'cover' } },
            };
            const d = defaults[type];
            if (!d) return;
            const el = {
                id: 'new_' + Date.now(),
                type,
                z_index: this.currentSlide.elements.length,
                ...d,
                props: { ...d.props },
            };
            this.currentSlide.elements.push(el);
            this.selectedId = el.id;
            this.isDirty = true;
            if (type === 'chart') this.$nextTick(() => this.renderChart(el));
        },

        deleteSelected() {
            if (!this.selectedId || !this.currentSlide) return;
            const id = this.selectedId;
            if (this._chartInstances[id]) { this._chartInstances[id].destroy(); delete this._chartInstances[id]; }
            this.currentSlide.elements = this.currentSlide.elements.filter(e => e.id !== id);
            this.selectedId = null;
            this.isDirty = true;
        },

        // Drag
        startDrag(e, element) {
            if (e.target.dataset.handle) return;
            if (e.target.contentEditable === 'true') return;
            e.preventDefault();
            this.selectedId = element.id;
            const startX = e.clientX, startY = e.clientY;
            const origX = element.x, origY = element.y;
            const scale = this.canvasScale;

            const onMove = (ev) => {
                element.x = Math.max(0, Math.min(960 - element.w, origX + (ev.clientX - startX) / scale));
                element.y = Math.max(0, Math.min(540 - element.h, origY + (ev.clientY - startY) / scale));
            };
            const onUp = () => {
                this.isDirty = true;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        // Resize
        startResize(e, element, handle) {
            e.stopPropagation();
            e.preventDefault();
            const startX = e.clientX, startY = e.clientY;
            const origX = element.x, origY = element.y, origW = element.w, origH = element.h;
            const scale = this.canvasScale;

            const onMove = (ev) => {
                const dx = (ev.clientX - startX) / scale;
                const dy = (ev.clientY - startY) / scale;
                if (handle.includes('e')) element.w = Math.max(30, origW + dx);
                if (handle.includes('s')) element.h = Math.max(20, origH + dy);
                if (handle.includes('w')) { const nw = Math.max(30, origW - dx); element.x = origX + origW - nw; element.w = nw; }
                if (handle.includes('n')) { const nh = Math.max(20, origH - dy); element.y = origY + origH - nh; element.h = nh; }
            };
            const onUp = () => {
                this.isDirty = true;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        // KPI value lookup
        kpiValue(src) {
            const m = { total: PROJECT_KPI.total, done: PROJECT_KPI.done, in_progress: PROJECT_KPI.in_progress,
                         overdue: PROJECT_KPI.overdue, progress_pct: PROJECT_KPI.progress_pct + '%', members: PROJECT_KPI.members };
            return m[src] ?? '--';
        },

        // Charts
        renderAllCharts() {
            if (!this.currentSlide) return;
            this.currentSlide.elements.filter(e => e.type === 'chart').forEach(el => {
                this.$nextTick(() => this.renderChart(el));
            });
        },

        renderChart(el) {
            const canvas = document.getElementById('chart-' + el.id);
            if (!canvas) return;
            if (this._chartInstances[el.id]) { this._chartInstances[el.id].destroy(); }

            const ds = el.props.data_source ?? 'status';
            let labels, values, colors;

            if (ds === 'status') {
                labels = ['Todo', 'In Progress', 'Review', 'Done', 'Cancelled'];
                values = [CHART_DATA.tasksByStatus.todo, CHART_DATA.tasksByStatus.in_progress,
                          CHART_DATA.tasksByStatus.review, CHART_DATA.tasksByStatus.done, CHART_DATA.tasksByStatus.cancelled];
                colors = ['#94a3b8', '#6366f1', '#f59e0b', '#22c55e', '#ef4444'];
            } else if (ds === 'priority') {
                labels = ['Critical', 'High', 'Medium', 'Low'];
                values = [CHART_DATA.tasksByPriority.critical, CHART_DATA.tasksByPriority.high,
                          CHART_DATA.tasksByPriority.medium, CHART_DATA.tasksByPriority.low];
                colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
            } else {
                labels = CHART_DATA.tasksByAssignee.map(a => a.name);
                values = CHART_DATA.tasksByAssignee.map(a => a.count);
                colors = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#ddd6fe','#818cf8','#4f46e5'];
            }

            const type = el.props.chart_type ?? 'doughnut';
            this._chartInstances[el.id] = new Chart(canvas, {
                type: type,
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: type === 'line' ? 2 : 1,
                        borderColor: type === 'line' ? colors[0] : '#fff',
                        fill: type === 'line',
                        tension: 0.4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { font: { size: 10 }, boxWidth: 10 } } },
                    scales: (type === 'bar' || type === 'line') ? {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    } : {},
                },
            });
        },

        // Image upload
        async uploadImage(e) {
            const file = e.target.files[0];
            if (!file) return;
            e.target.value = '';
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            const res = await fetch(ATTACH_URL, { method: 'POST', body: fd });
            const data = await res.json();
            this.addElement('image');
            const el = this.currentSlide.elements[this.currentSlide.elements.length - 1];
            el.props.url = data.url;
            el.props.attachment_id = data.id;
            this.isDirty = true;
        },

        replaceImage(el) {
            this._replaceTarget = el;
            this.$refs.replaceImgInput.click();
        },

        async doReplaceImage(e) {
            const file = e.target.files[0];
            if (!file || !this._replaceTarget) return;
            e.target.value = '';
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            const res = await fetch(ATTACH_URL, { method: 'POST', body: fd });
            const data = await res.json();
            this._replaceTarget.props.url = data.url;
            this._replaceTarget.props.attachment_id = data.id;
            this._replaceTarget = null;
            this.isDirty = true;
        },

        // Save
        async save() {
            this.isSaving = true;
            try {
                const res = await fetch(SAVE_URL, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ slides: this.slides }),
                });
                const data = await res.json();
                if (data.success) {
                    this.slides = data.slides;
                    this.isDirty = false;
                    this.$nextTick(() => this.renderAllCharts());
                }
            } catch (err) { console.error('Save failed', err); }
            this.isSaving = false;
        },

        openTemplateSave() { this.templateName = ''; this.showTemplateSave = true; },

        async doSaveAsTemplate() {
            if (!this.templateName.trim()) return;
            await this.save();
            const res = await fetch(TEMPLATE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ template_name: this.templateName }),
            });
            const data = await res.json();
            if (data.success) this.showTemplateSave = false;
        },
    };
}
</script>
@endpush
