@extends('layouts.app')

@section('title', $report->title . ' — Builder')

@push('styles')
<script src="/vendor/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<style>
[x-cloak] { display: none !important; }
#report-builder { display:flex; flex-direction:column; height:calc(100vh - 56px); overflow:hidden; user-select:none; }
.builder-toolbar { flex-shrink:0; height:48px; display:flex; align-items:center; gap:4px; padding:0 10px;
    background:#1e1e2e; border-bottom:1px solid #2d2d44; overflow-x:auto; overflow-y:hidden; }
.builder-body { flex:1; display:flex; overflow:hidden; }
.slide-panel { width:152px; flex-shrink:0; overflow-y:auto; background:#16162a; border-right:1px solid #2d2d44; padding:8px; }
.canvas-area { flex:1; overflow:auto; background:#111122; display:flex; align-items:flex-start; justify-content:center; padding:24px; }
.props-panel { width:256px; flex-shrink:0; overflow-y:auto; background:#16162a; border-left:1px solid #2d2d44; padding:12px; }

.report-canvas { position:relative; width:960px; height:540px; flex-shrink:0; box-shadow:0 8px 40px rgba(0,0,0,.6); overflow:hidden; cursor:default; }

.slide-thumb { position:relative; width:100%; padding-top:56.25%; cursor:pointer; border-radius:6px; overflow:hidden;
    border:2px solid transparent; margin-bottom:6px; transition:border-color .15s; }
.slide-thumb.active { border-color:#6366f1; }
.slide-thumb-inner { position:absolute; inset:0; overflow:hidden; }
.slide-thumb-label { position:absolute; bottom:2px; left:0; right:0; text-align:center; font-size:9px; color:#888; }

.canvas-el { position:absolute; box-sizing:border-box; }
.canvas-el.selected { outline:2px solid #6366f1; outline-offset:1px; }
.canvas-el .rh { position:absolute; width:8px; height:8px; background:#6366f1; border:2px solid #fff; border-radius:50%; z-index:100; }
.canvas-el .rh[data-h="nw"] { top:-4px; left:-4px; cursor:nw-resize; }
.canvas-el .rh[data-h="ne"] { top:-4px; right:-4px; cursor:ne-resize; }
.canvas-el .rh[data-h="sw"] { bottom:-4px; left:-4px; cursor:sw-resize; }
.canvas-el .rh[data-h="se"] { bottom:-4px; right:-4px; cursor:se-resize; }
.canvas-el .rh[data-h="n"]  { top:-4px; left:calc(50% - 4px); cursor:n-resize; }
.canvas-el .rh[data-h="s"]  { bottom:-4px; left:calc(50% - 4px); cursor:s-resize; }
.canvas-el .rh[data-h="e"]  { right:-4px; top:calc(50% - 4px); cursor:e-resize; }
.canvas-el .rh[data-h="w"]  { left:-4px; top:calc(50% - 4px); cursor:w-resize; }

.el-text { overflow:hidden; }
.el-kpi { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }

.tb-btn { display:flex; align-items:center; gap:3px; padding:3px 7px; border-radius:5px; font-size:11px;
    color:#ccc; cursor:pointer; border:none; background:transparent; white-space:nowrap; transition:background .15s; flex-shrink:0; }
.tb-btn:hover { background:#2d2d44; color:#fff; }
.tb-btn:disabled { opacity:.35; cursor:not-allowed; }
.tb-sep { width:1px; height:22px; background:#2d2d44; margin:0 3px; flex-shrink:0; }

.prop-label { font-size:11px; color:#888; margin-bottom:3px; }
.prop-input { width:100%; padding:4px 8px; border-radius:5px; background:#0f0f1a; border:1px solid #2d2d44; color:#ddd; font-size:12px; outline:none; }
.prop-input:focus { border-color:#6366f1; }
.prop-section { border-top:1px solid #2d2d44; padding-top:10px; margin-top:10px; }

.save-dot { width:7px; height:7px; border-radius:50%; display:inline-block; }
.save-dot.dirty  { background:#f59e0b; }
.save-dot.saved  { background:#22c55e; }
.save-dot.saving { background:#6366f1; animation:pulse 1s infinite; }
</style>
@endpush

@section('content')
<div id="report-builder" x-data="reportBuilder()" x-init="init()">

    {{-- ══ Toolbar ══ --}}
    <div class="builder-toolbar">
        <a href="{{ route('projects.reports.index', $project) }}" class="tb-btn" title="Back">
            <i class="ti ti-arrow-left"></i>
        </a>
        <div class="tb-sep"></div>

        <span class="text-xs text-gray-400 font-medium max-w-[140px] truncate">{{ $report->title }}</span>
        <span class="save-dot ml-1" :class="isSaving ? 'saving' : isDirty ? 'dirty' : 'saved'"></span>

        <div class="flex-1"></div>

        {{-- Content group --}}
        <span class="text-xs text-gray-600 mr-1">Content:</span>
        <button class="tb-btn" @click="addElement('text')"><i class="ti ti-text-size"></i> Text</button>
        <button class="tb-btn" @click="addElement('table')"><i class="ti ti-table"></i> Table</button>
        <button class="tb-btn" @click="addElement('divider')"><i class="ti ti-minus"></i> Divider</button>
        <button class="tb-btn" @click="addElement('shape')"><i class="ti ti-square"></i> Shape</button>
        <button class="tb-btn" @click="$refs.imgInput.click()"><i class="ti ti-photo"></i> Image</button>
        <input type="file" x-ref="imgInput" accept="image/*" class="hidden" @change="uploadImage($event)">
        <div class="tb-sep"></div>

        {{-- Data Widgets group --}}
        <span class="text-xs text-gray-600 mr-1">Widgets:</span>
        <button class="tb-btn" @click="addElement('kpi')"><i class="ti ti-chart-bar"></i> KPI</button>
        <button class="tb-btn" @click="addElement('chart')"><i class="ti ti-chart-donut"></i> Chart</button>
        <button class="tb-btn" @click="addElement('gantt_mini')"><i class="ti ti-calendar-stats"></i> Gantt</button>
        <button class="tb-btn" @click="addElement('milestone_list')"><i class="ti ti-flag"></i> Milestones</button>
        <button class="tb-btn" @click="addElement('team_list')"><i class="ti ti-users"></i> Team</button>
        <button class="tb-btn" @click="addElement('blocker_list')"><i class="ti ti-alert-triangle"></i> Blockers</button>
        <div class="tb-sep"></div>

        {{-- Actions --}}
        <button class="tb-btn" @click="deleteSelected()" :disabled="!selectedId"
                :class="selectedId ? 'text-red-400 hover:bg-red-900/30' : ''">
            <i class="ti ti-trash"></i>
        </button>
        <div class="tb-sep"></div>
        <button class="tb-btn" @click="save()" :disabled="isSaving">
            <i class="ti ti-device-floppy"></i> {{ app()->getLocale() === 'th' ? 'บันทึก' : 'Save' }}
        </button>
        <a href="{{ route('projects.reports.preview', [$project, $report]) }}" target="_blank" class="tb-btn">
            <i class="ti ti-eye"></i> Preview
        </a>
        <a href="{{ route('projects.reports.export', [$project, $report]) }}" target="_blank" class="tb-btn">
            <i class="ti ti-printer"></i> Export
        </a>
        <div class="relative" x-data="{ open:false }">
            <button class="tb-btn" @click="open=!open"><i class="ti ti-dots-vertical"></i></button>
            <div x-show="open" @click.outside="open=false"
                 class="absolute right-0 top-10 w-52 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50 py-1">
                <button @click="openTemplateSave(); open=false"
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
            <div class="text-xs text-gray-500 mb-2">{{ app()->getLocale() === 'th' ? 'สไลด์' : 'Slides' }}</div>

            <template x-for="(slide, idx) in slides" :key="slide.id">
                <div class="relative group">
                    <div class="slide-thumb" :class="idx === currentSlideIndex ? 'active' : ''"
                         @click="currentSlideIndex = idx; selectedId = null">
                        <div class="slide-thumb-inner" :style="{ background: slide.bg_color }">
                            <svg width="100%" height="100%" viewBox="0 0 960 540" style="position:absolute;inset:0">
                                <template x-for="el in slide.elements" :key="el.id">
                                    <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h"
                                          :fill="el.type==='shape' ? (el.props.fill??'#4f46e5') : (el.type==='text'||el.type==='table' ? (el.props.bg_color??'#e0e7ff') : '#e0e7ff')"
                                          :opacity="el.type==='shape' ? (el.props.opacity??1) : 0.5" rx="2"></rect>
                                </template>
                            </svg>
                        </div>
                        <div class="slide-thumb-label" x-text="idx + 1"></div>
                    </div>
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

                            {{-- TEXT --}}
                            <div x-show="el.type === 'text'" class="el-text w-full h-full"
                                 :style="`font-size:${el.props.font_size??20}px;font-weight:${el.props.font_weight??'normal'};color:${el.props.color??'#1a1a1a'};text-align:${el.props.align??'left'};background:${el.props.bg_color??'transparent'};padding:8px;font-style:${el.props.italic?'italic':'normal'};line-height:${el.props.line_height??1.4}`"
                                 @dblclick.stop="openTinyEditor(el)">
                                <div x-html="el.props.content ?? '<p style=\'color:#9ca3af\'>Double-click to edit</p>'"
                                     style="pointer-events:none;overflow:hidden;width:100%;height:100%"></div>
                            </div>

                            {{-- TABLE --}}
                            <div x-show="el.type === 'table'" class="w-full h-full overflow-hidden rounded bg-white/95"
                                 @dblclick.stop="openTinyEditor(el)">
                                <div x-html="el.props.content ?? '<p style=\'color:#9ca3af;padding:8px;font-size:12px\'>Double-click to edit table</p>'"
                                     style="pointer-events:none;overflow:hidden;width:100%;height:100%;padding:4px;font-size:11px"></div>
                            </div>

                            {{-- KPI --}}
                            <div x-show="el.type === 'kpi'" class="el-kpi w-full h-full rounded-lg"
                                 :style="`background:${el.props.bg??'#f5f3ff'};border:2px solid ${el.props.accent??'#4f46e5'};`">
                                <div style="font-size:2em;font-weight:800" :style="`color:${el.props.accent??'#4f46e5'}`"
                                     x-text="(el.props.prefix??'') + kpiValue(el.props.data_source) + (el.props.suffix??'')"></div>
                                <div style="font-size:0.75em;color:#6b7280;margin-top:4px" x-text="el.props.label??''"></div>
                            </div>

                            {{-- CHART --}}
                            <div x-show="el.type === 'chart'" class="w-full h-full flex flex-col bg-white/80 rounded-lg p-2">
                                <div style="font-size:11px;font-weight:600;color:#374151;margin-bottom:4px" x-text="el.props.title??'Chart'"></div>
                                <canvas :id="'chart-'+el.id" style="flex:1;min-height:0;max-height:100%"></canvas>
                            </div>

                            {{-- IMAGE --}}
                            <div x-show="el.type === 'image'" class="w-full h-full overflow-hidden rounded bg-gray-200 flex items-center justify-center">
                                <img x-show="el.props.url" :src="el.props.url"
                                     :style="`width:100%;height:100%;object-fit:${el.props.fit??'cover'}`">
                                <div x-show="!el.props.url" class="text-gray-400 text-center">
                                    <i class="ti ti-photo text-2xl block"></i>
                                    <p style="font-size:10px;margin-top:2px">Double-click to replace</p>
                                </div>
                            </div>

                            {{-- SHAPE --}}
                            <div x-show="el.type === 'shape'" class="w-full h-full"
                                 :style="`background:${el.props.fill??'#4f46e5'};opacity:${el.props.opacity??1};border-radius:${el.props.border_radius??0}px;border:${el.props.border_width??0}px solid ${el.props.border_color??'#000'}`">
                            </div>

                            {{-- GANTT MINI --}}
                            <div x-show="el.type === 'gantt_mini'"
                                 class="w-full h-full overflow-hidden rounded"
                                 style="background:#f8fafc"
                                 x-html="buildGanttSvg(el)">
                            </div>

                            {{-- MILESTONE LIST --}}
                            <div x-show="el.type === 'milestone_list'"
                                 class="w-full h-full overflow-hidden rounded bg-white/95 flex flex-col">
                                <div style="font-size:11px;font-weight:600;color:#374151;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0"
                                     x-text="el.props.title ?? 'Milestones'"></div>
                                <div style="overflow-y:auto;flex:1">
                                    <template x-if="PROJECT_DATA.milestones.length === 0">
                                        <div style="text-align:center;color:#9ca3af;font-size:10px;padding:12px">No milestones</div>
                                    </template>
                                    <template x-for="m in PROJECT_DATA.milestones" :key="m.id">
                                        <div style="display:flex;align-items:center;gap:6px;padding:5px 10px;border-bottom:1px solid #f1f5f9;font-size:10px">
                                            <span x-text="m.is_completed ? '✅' : '⭕'"></span>
                                            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="m.name"></span>
                                            <span style="color:#9ca3af;flex-shrink:0" x-text="m.due_date ?? '—'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- TEAM LIST --}}
                            <div x-show="el.type === 'team_list'"
                                 class="w-full h-full overflow-hidden rounded bg-white/95 flex flex-col">
                                <div style="font-size:11px;font-weight:600;color:#374151;padding:6px 10px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0"
                                     x-text="el.props.title ?? 'Team Members'"></div>
                                <div style="overflow-y:auto;flex:1">
                                    <template x-for="m in PROJECT_DATA.members" :key="m.id">
                                        <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-bottom:1px solid #f1f5f9">
                                            <div style="width:22px;height:22px;border-radius:50%;background:#4f46e5;display:flex;align-items:center;justify-content:center;color:#fff;font-size:9px;font-weight:700;flex-shrink:0"
                                                 x-text="m.name.charAt(0).toUpperCase()"></div>
                                            <div style="flex:1;min-width:0">
                                                <div style="font-size:10px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="m.name"></div>
                                                <div style="font-size:9px;color:#9ca3af;text-transform:capitalize" x-text="m.role"></div>
                                            </div>
                                            <span style="font-size:9px;color:#9ca3af;flex-shrink:0" x-text="m.tasks_count + ' tasks'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- BLOCKER LIST --}}
                            <div x-show="el.type === 'blocker_list'"
                                 class="w-full h-full overflow-hidden rounded flex flex-col"
                                 :style="`background:${PROJECT_DATA.active_blockers_list.length ? '#fff5f5' : '#f0fdf4'}`">
                                <div style="font-size:11px;font-weight:600;padding:6px 10px;border-bottom:1px solid;flex-shrink:0"
                                     :class="PROJECT_DATA.active_blockers_list.length ? 'bg-red-50 text-red-700 border-red-100' : 'bg-green-50 text-green-700 border-green-100'"
                                     x-text="el.props.title ?? 'Active Blockers'"></div>
                                <div style="overflow-y:auto;flex:1">
                                    <template x-if="!PROJECT_DATA.active_blockers_list.length">
                                        <div style="text-align:center;color:#16a34a;font-size:10px;padding:12px">✅ No active blockers</div>
                                    </template>
                                    <template x-for="(b, i) in PROJECT_DATA.active_blockers_list" :key="i">
                                        <div style="display:flex;align-items:flex-start;gap:6px;padding:6px 10px;border-bottom:1px solid #fee2e2">
                                            <span style="flex-shrink:0;font-size:12px">🚨</span>
                                            <div style="min-width:0">
                                                <div style="font-size:10px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="b.task_title"></div>
                                                <div style="font-size:9px;color:#dc2626;margin-top:1px" x-text="b.description"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- DIVIDER --}}
                            <div x-show="el.type === 'divider'" class="w-full h-full flex items-center">
                                <div class="w-full" :style="`border-top:${el.props.thickness??2}px solid ${el.props.color??'#e5e7eb'}`"></div>
                            </div>

                            {{-- Resize handles (selected only) --}}
                            <template x-if="selectedId === el.id">
                                <div>
                                    <div class="rh" data-h="nw" @mousedown.stop="startResize($event,el,'nw')"></div>
                                    <div class="rh" data-h="n"  @mousedown.stop="startResize($event,el,'n')"></div>
                                    <div class="rh" data-h="ne" @mousedown.stop="startResize($event,el,'ne')"></div>
                                    <div class="rh" data-h="e"  @mousedown.stop="startResize($event,el,'e')"></div>
                                    <div class="rh" data-h="se" @mousedown.stop="startResize($event,el,'se')"></div>
                                    <div class="rh" data-h="s"  @mousedown.stop="startResize($event,el,'s')"></div>
                                    <div class="rh" data-h="sw" @mousedown.stop="startResize($event,el,'sw')"></div>
                                    <div class="rh" data-h="w"  @mousedown.stop="startResize($event,el,'w')"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </template>
            </div>
        </div>

        {{-- Properties Panel --}}
        <div class="props-panel">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                {{ app()->getLocale() === 'th' ? 'คุณสมบัติ' : 'Properties' }}
            </div>

            <template x-if="selectedElement">
                <div class="space-y-3">
                    {{-- Position & Size --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div><div class="prop-label">X</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.x)"
                                   @input="selectedElement.x=+$event.target.value;isDirty=true"></div>
                        <div><div class="prop-label">Y</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.y)"
                                   @input="selectedElement.y=+$event.target.value;isDirty=true"></div>
                        <div><div class="prop-label">W</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.w)"
                                   @input="selectedElement.w=Math.max(10,+$event.target.value);isDirty=true"></div>
                        <div><div class="prop-label">H</div>
                            <input type="number" class="prop-input" :value="Math.round(selectedElement.h)"
                                   @input="selectedElement.h=Math.max(10,+$event.target.value);isDirty=true"></div>
                    </div>
                    <div><div class="prop-label">Z-index</div>
                        <input type="number" class="prop-input" :value="selectedElement.z_index"
                               @input="selectedElement.z_index=+$event.target.value;isDirty=true"></div>

                    {{-- TEXT props --}}
                    <template x-if="selectedElement.type === 'text'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Text</div>
                            <button @click="openTinyEditor(selectedElement)"
                                    class="w-full py-1.5 rounded text-xs bg-indigo-600 text-white hover:bg-indigo-700">
                                <i class="ti ti-edit mr-1"></i> Open TinyMCE Editor
                            </button>
                            <div><div class="prop-label">Font Size</div>
                                <input type="number" min="8" max="200" class="prop-input" :value="selectedElement.props.font_size??20"
                                       @input="selectedElement.props.font_size=+$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Color</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer" :value="selectedElement.props.color??'#1a1a1a'"
                                       @input="selectedElement.props.color=$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Background</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer" :value="selectedElement.props.bg_color??'#ffffff'"
                                       @input="selectedElement.props.bg_color=$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Align</div>
                                <div class="flex gap-1">
                                    <template x-for="a in ['left','center','right']" :key="a">
                                        <button class="flex-1 py-1 rounded text-xs border"
                                                :class="selectedElement.props.align===a ? 'bg-indigo-600 border-indigo-500 text-white' : 'border-gray-600 text-gray-400'"
                                                @click="selectedElement.props.align=a;isDirty=true" x-text="a"></button>
                                    </template>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-1 text-xs text-gray-400 cursor-pointer">
                                    <input type="checkbox" :checked="selectedElement.props.font_weight==='bold'"
                                           @change="selectedElement.props.font_weight=$event.target.checked?'bold':'normal';isDirty=true"> Bold
                                </label>
                                <label class="flex items-center gap-1 text-xs text-gray-400 cursor-pointer">
                                    <input type="checkbox" :checked="selectedElement.props.italic"
                                           @change="selectedElement.props.italic=$event.target.checked;isDirty=true"> Italic
                                </label>
                            </div>
                        </div>
                    </template>

                    {{-- TABLE props --}}
                    <template x-if="selectedElement.type === 'table'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Table</div>
                            <button @click="openTinyEditor(selectedElement)"
                                    class="w-full py-1.5 rounded text-xs bg-indigo-600 text-white hover:bg-indigo-700">
                                <i class="ti ti-table mr-1"></i> Edit Table Content
                            </button>
                        </div>
                    </template>

                    {{-- KPI props --}}
                    <template x-if="selectedElement.type === 'kpi'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">KPI</div>
                            <div><div class="prop-label">Data Source</div>
                                <select class="prop-input" :value="selectedElement.props.data_source??'total'"
                                        @change="selectedElement.props.data_source=$event.target.value;isDirty=true">
                                    <option value="total">Total Tasks</option>
                                    <option value="done">Done Tasks</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="progress_pct">Progress %</option>
                                    <option value="members">Members</option>
                                </select></div>
                            <div><div class="prop-label">Label</div>
                                <input type="text" class="prop-input" :value="selectedElement.props.label??''"
                                       @input="selectedElement.props.label=$event.target.value;isDirty=true"></div>
                            <div class="grid grid-cols-2 gap-2">
                                <div><div class="prop-label">Prefix</div>
                                    <input type="text" class="prop-input" maxlength="5" :value="selectedElement.props.prefix??''"
                                           @input="selectedElement.props.prefix=$event.target.value;isDirty=true"></div>
                                <div><div class="prop-label">Suffix</div>
                                    <input type="text" class="prop-input" maxlength="5" :value="selectedElement.props.suffix??''"
                                           @input="selectedElement.props.suffix=$event.target.value;isDirty=true"></div>
                            </div>
                            <div><div class="prop-label">Accent</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer" :value="selectedElement.props.accent??'#4f46e5'"
                                       @input="selectedElement.props.accent=$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- CHART props --}}
                    <template x-if="selectedElement.type === 'chart'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Chart</div>
                            <div><div class="prop-label">Chart Type</div>
                                <select class="prop-input" :value="selectedElement.props.chart_type??'doughnut'"
                                        @change="selectedElement.props.chart_type=$event.target.value;isDirty=true;$nextTick(()=>renderAllCharts())">
                                    <option value="doughnut">Doughnut</option>
                                    <option value="bar">Bar</option>
                                    <option value="pie">Pie</option>
                                    <option value="line">Line</option>
                                </select></div>
                            <div><div class="prop-label">Data Source</div>
                                <select class="prop-input" :value="selectedElement.props.data_source??'status'"
                                        @change="selectedElement.props.data_source=$event.target.value;isDirty=true;$nextTick(()=>renderAllCharts())">
                                    <option value="status">Tasks by Status</option>
                                    <option value="priority">Tasks by Priority</option>
                                    <option value="assignee">Tasks by Assignee</option>
                                </select></div>
                            <div><div class="prop-label">Title</div>
                                <input type="text" class="prop-input" :value="selectedElement.props.title??''"
                                       @input="selectedElement.props.title=$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- SHAPE props --}}
                    <template x-if="selectedElement.type === 'shape'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Shape</div>
                            <div><div class="prop-label">Fill</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer" :value="selectedElement.props.fill??'#4f46e5'"
                                       @input="selectedElement.props.fill=$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Opacity</div>
                                <input type="range" min="0" max="1" step=".05" class="w-full" :value="selectedElement.props.opacity??1"
                                       @input="selectedElement.props.opacity=+$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Border Radius</div>
                                <input type="range" min="0" max="100" step="1" class="w-full" :value="selectedElement.props.border_radius??0"
                                       @input="selectedElement.props.border_radius=+$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Border Width</div>
                                <input type="number" min="0" max="20" class="prop-input" :value="selectedElement.props.border_width??0"
                                       @input="selectedElement.props.border_width=+$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Border Color</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer" :value="selectedElement.props.border_color??'#000000'"
                                       @input="selectedElement.props.border_color=$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- IMAGE props --}}
                    <template x-if="selectedElement.type === 'image'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Image</div>
                            <div><div class="prop-label">Fit</div>
                                <select class="prop-input" :value="selectedElement.props.fit??'cover'"
                                        @change="selectedElement.props.fit=$event.target.value;isDirty=true">
                                    <option value="cover">Cover</option>
                                    <option value="contain">Contain</option>
                                    <option value="fill">Fill</option>
                                </select></div>
                            <button class="w-full py-1.5 rounded border border-gray-600 text-xs text-gray-300 hover:bg-gray-700"
                                    @click="replaceImage(selectedElement)">
                                <i class="ti ti-upload mr-1"></i> Replace Image
                            </button>
                        </div>
                    </template>

                    {{-- GANTT MINI props --}}
                    <template x-if="selectedElement.type === 'gantt_mini'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Gantt Mini</div>
                            <div><div class="prop-label">Title</div>
                                <input type="text" class="prop-input" :value="selectedElement.props.title??'Project Timeline'"
                                       @input="selectedElement.props.title=$event.target.value;isDirty=true"></div>
                            <p class="text-xs text-gray-600">Shows tasks with scheduled start/end dates from project.</p>
                        </div>
                    </template>

                    {{-- MILESTONE LIST props --}}
                    <template x-if="selectedElement.type === 'milestone_list'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Milestone List</div>
                            <div><div class="prop-label">Title</div>
                                <input type="text" class="prop-input" :value="selectedElement.props.title??'Milestones'"
                                       @input="selectedElement.props.title=$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- TEAM LIST props --}}
                    <template x-if="selectedElement.type === 'team_list'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Team List</div>
                            <div><div class="prop-label">Title</div>
                                <input type="text" class="prop-input" :value="selectedElement.props.title??'Team Members'"
                                       @input="selectedElement.props.title=$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- BLOCKER LIST props --}}
                    <template x-if="selectedElement.type === 'blocker_list'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Blocker List</div>
                            <div><div class="prop-label">Title</div>
                                <input type="text" class="prop-input" :value="selectedElement.props.title??'Active Blockers'"
                                       @input="selectedElement.props.title=$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- DIVIDER props --}}
                    <template x-if="selectedElement.type === 'divider'">
                        <div class="prop-section space-y-2">
                            <div class="prop-label font-semibold text-gray-400">Divider</div>
                            <div><div class="prop-label">Color</div>
                                <input type="color" class="w-full h-8 rounded cursor-pointer" :value="selectedElement.props.color??'#e5e7eb'"
                                       @input="selectedElement.props.color=$event.target.value;isDirty=true"></div>
                            <div><div class="prop-label">Thickness (px)</div>
                                <input type="number" min="1" max="20" class="prop-input" :value="selectedElement.props.thickness??2"
                                       @input="selectedElement.props.thickness=+$event.target.value;isDirty=true"></div>
                        </div>
                    </template>

                    {{-- Slide Background (always shown) --}}
                    <div class="prop-section">
                        <div class="prop-label font-semibold text-gray-400 mb-2">Slide Background</div>
                        <input type="color" class="w-full h-8 rounded cursor-pointer"
                               :value="currentSlide ? currentSlide.bg_color : '#ffffff'"
                               @input="if(currentSlide){currentSlide.bg_color=$event.target.value;isDirty=true}">
                    </div>

                    <button @click="deleteSelected()"
                            class="w-full mt-2 py-2 rounded-lg bg-red-900/30 border border-red-800 text-red-400 text-xs hover:bg-red-900/50">
                        <i class="ti ti-trash mr-1"></i> Delete Element
                    </button>
                </div>
            </template>

            <template x-if="!selectedElement">
                <div>
                    <div class="prop-section">
                        <div class="prop-label font-semibold text-gray-400 mb-2">Slide Background</div>
                        <input type="color" class="w-full h-8 rounded cursor-pointer"
                               :value="currentSlide ? currentSlide.bg_color : '#ffffff'"
                               @input="if(currentSlide){currentSlide.bg_color=$event.target.value;isDirty=true}">
                    </div>
                    <p class="text-xs text-gray-600 mt-4 text-center">Click element to select · Double-click text/table to edit</p>
                </div>
            </template>
        </div>
    </div>{{-- /builder-body --}}

    {{-- ══ TinyMCE Editor Overlay ══ --}}
    <div x-show="tinyEditing" x-cloak
         class="fixed inset-0 z-[200] flex items-center justify-center bg-black/75"
         @keydown.escape.window="cancelTinyEditor()">
        <div class="bg-white rounded-xl overflow-hidden shadow-2xl flex flex-col"
             style="width:900px;max-width:95vw;height:600px">
            <div class="flex justify-between items-center px-5 py-3 bg-gray-100 border-b flex-shrink-0">
                <h3 class="font-semibold text-gray-800 text-sm" x-text="tinyEditorLabel"></h3>
                <div class="flex gap-2">
                    <button @click="saveTinyEditor()"
                            class="px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                        <i class="ti ti-check mr-1"></i> Save
                    </button>
                    <button @click="cancelTinyEditor()"
                            class="px-4 py-1.5 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-hidden">
                <textarea id="tinymce-editor-area" style="width:100%;height:100%"></textarea>
            </div>
        </div>
    </div>

    {{-- ══ Template Save Modal ══ --}}
    <div x-show="showTemplateSave" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         @keydown.escape.window="showTemplateSave=false">
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-80 shadow-2xl">
            <h3 class="text-sm font-semibold text-gray-200 mb-4">Save as Template</h3>
            <input type="text" x-model="templateName" placeholder="Template name"
                   class="w-full prop-input mb-4">
            <div class="flex gap-3">
                <button @click="showTemplateSave=false"
                        class="flex-1 py-2 rounded-lg border border-gray-600 text-xs text-gray-400 hover:bg-gray-700">Cancel</button>
                <button @click="doSaveAsTemplate()"
                        class="flex-1 py-2 rounded-lg bg-indigo-600 text-xs text-white hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>

    <input type="file" x-ref="replaceImgInput" accept="image/*" class="hidden" @change="doReplaceImage($event)">
</div>
@endsection

@push('scripts')
@php
$reportJson = [
    'id'     => $report->id,
    'title'  => $report->title,
    'slides' => $report->slides->map(function($s) {
        return [
            'id'          => $s->id,
            'slide_order' => $s->slide_order,
            'bg_color'    => $s->bg_color,
            'notes'       => $s->notes ?? '',
            'elements'    => $s->elements->map(function($e) {
                return [
                    'id'      => $e->id,
                    'type'    => $e->type,
                    'x'       => $e->x,
                    'y'       => $e->y,
                    'w'       => $e->w,
                    'h'       => $e->h,
                    'z_index' => $e->z_index,
                    'props'   => $e->props,
                ];
            })->values(),
        ];
    })->values(),
];
@endphp
<script>
const REPORT_DATA   = @json($reportJson);
const PROJECT_KPI   = @json($kpi);
const CHART_DATA    = @json($chartData);
const PROJECT_DATA  = @json($projectData);
const SAVE_URL      = '{{ route('projects.reports.save', [$project, $report]) }}';
const UPLOAD_IMG_URL = '{{ route('projects.reports.upload-image', [$project, $report]) }}';
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
        tinyEditing: false,
        tinyEditorElement: null,
        tinyEditorLabel: 'Edit Content',
        _tinyBackup: null,
        _replaceTarget: null,
        _chartInstances: {},

        init() {
            this.slides = JSON.parse(JSON.stringify(REPORT_DATA.slides || []));
            if (!this.slides.length) this.addSlide();
            this.$nextTick(() => { this.recalcScale(); this.renderAllCharts(); });
            window.addEventListener('resize', () => this.recalcScale());
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' || e.key === 'Backspace') {
                    const tag = document.activeElement.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                    if (document.activeElement.contentEditable === 'true') return;
                    if (this.tinyEditing) return;
                    this.deleteSelected();
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); this.save(); }
                if ((e.ctrlKey || e.metaKey) && e.key === 'z') { /* undo stub */ }
            });
            setInterval(() => { if (this.isDirty && !this.isSaving && !this.tinyEditing) this.save(); }, 60000);
        },

        get currentSlide() { return this.slides[this.currentSlideIndex] || null; },
        get selectedElement() {
            if (!this.selectedId || !this.currentSlide) return null;
            return this.currentSlide.elements.find(e => e.id === this.selectedId) || null;
        },

        recalcScale() {
            const el = this.$refs.canvasContainer;
            if (!el) return;
            this.canvasScale = Math.min((el.clientWidth - 48) / 960, (el.clientHeight - 48) / 540, 1);
        },

        // ── Slide management ──
        addSlide() {
            const s = { id: 'new_' + Date.now(), slide_order: this.slides.length, bg_color: '#ffffff', notes: '', elements: [] };
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

        // ── Element management ──
        addElement(type) {
            if (!this.currentSlide) return;
            const tableDefault = '<table style="width:100%;border-collapse:collapse;font-size:11px"><thead><tr>' +
                '<th style="border:1px solid #e2e8f0;padding:6px 8px;background:#f8fafc;text-align:left">Column 1</th>' +
                '<th style="border:1px solid #e2e8f0;padding:6px 8px;background:#f8fafc;text-align:left">Column 2</th>' +
                '<th style="border:1px solid #e2e8f0;padding:6px 8px;background:#f8fafc;text-align:left">Column 3</th>' +
                '</tr></thead><tbody><tr>' +
                '<td style="border:1px solid #e2e8f0;padding:6px 8px">Cell</td>' +
                '<td style="border:1px solid #e2e8f0;padding:6px 8px">Cell</td>' +
                '<td style="border:1px solid #e2e8f0;padding:6px 8px">Cell</td>' +
                '</tr><tr>' +
                '<td style="border:1px solid #e2e8f0;padding:6px 8px">Cell</td>' +
                '<td style="border:1px solid #e2e8f0;padding:6px 8px">Cell</td>' +
                '<td style="border:1px solid #e2e8f0;padding:6px 8px">Cell</td>' +
                '</tr></tbody></table>';

            const defaults = {
                text:          { x:80, y:80, w:400, h:80,  props:{ content:'<p>Click to edit text</p>', font_size:24, font_weight:'normal', color:'#1a1a1a', align:'left', bg_color:null, italic:false, line_height:1.4 } },
                table:         { x:80, y:80, w:500, h:200, props:{ content: tableDefault } },
                kpi:           { x:80, y:80, w:220, h:130, props:{ data_source:'total', label:'Total Tasks', prefix:'', suffix:'', accent:'#4f46e5', bg:'#f5f3ff' } },
                chart:         { x:80, y:80, w:380, h:280, props:{ chart_type:'doughnut', data_source:'status', title:'Task Status' } },
                shape:         { x:80, y:80, w:240, h:100, props:{ fill:'#4f46e5', opacity:1, border_radius:8, border_width:0, border_color:'#000000' } },
                image:         { x:80, y:80, w:300, h:200, props:{ url:null, fit:'cover' } },
                gantt_mini:    { x:40, y:80, w:880, h:300, props:{ title:'Project Timeline' } },
                milestone_list:{ x:40, y:80, w:440, h:280, props:{ title:'Milestones' } },
                team_list:     { x:500, y:80, w:420, h:280, props:{ title:'Team Members' } },
                blocker_list:  { x:40, y:300, w:880, h:200, props:{ title:'Active Blockers' } },
                divider:       { x:40, y:260, w:880, h:8,   props:{ color:'#e5e7eb', thickness:2 } },
            };
            const d = defaults[type];
            if (!d) return;
            const el = { id:'new_'+Date.now(), type, z_index:this.currentSlide.elements.length, ...d, props:{...d.props} };
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

        // ── TinyMCE ──
        openTinyEditor(element) {
            this._tinyBackup = element.props.content;
            this.tinyEditorElement = element;
            this.tinyEditorLabel = element.type === 'table' ? 'Edit Table Content' : 'Edit Text Content';
            this.tinyEditing = true;
            this.$nextTick(() => {
                if (typeof tinymce === 'undefined') { alert('TinyMCE not loaded'); return; }
                tinymce.remove('#tinymce-editor-area');
                tinymce.init({
                    selector: '#tinymce-editor-area',
                    base_url: '/vendor/tinymce',
                    suffix: '.min',
                    height: 530,
                    plugins: 'lists link table code',
                    toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | link table | code',
                    menubar: false,
                    branding: false,
                    content_style: 'body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;font-size:14px;line-height:1.5;margin:12px}',
                    setup: (editor) => {
                        editor.on('init', () => { editor.setContent(element.props.content || ''); });
                    },
                });
            });
        },
        saveTinyEditor() {
            if (typeof tinymce !== 'undefined') {
                const editor = tinymce.get('tinymce-editor-area');
                if (editor && this.tinyEditorElement) {
                    this.tinyEditorElement.props.content = editor.getContent();
                    this.isDirty = true;
                }
                tinymce.remove('#tinymce-editor-area');
            }
            this.tinyEditing = false;
            this.tinyEditorElement = null;
        },
        cancelTinyEditor() {
            if (typeof tinymce !== 'undefined') tinymce.remove('#tinymce-editor-area');
            if (this.tinyEditorElement && this._tinyBackup !== undefined) {
                this.tinyEditorElement.props.content = this._tinyBackup;
            }
            this.tinyEditing = false;
            this.tinyEditorElement = null;
        },

        // ── Drag ──
        startDrag(e, element) {
            if (e.target.dataset.h) return;
            e.preventDefault();
            this.selectedId = element.id;
            const sx = e.clientX, sy = e.clientY, ox = element.x, oy = element.y, sc = this.canvasScale;
            const onMove = (ev) => {
                element.x = Math.max(0, Math.min(960 - element.w, ox + (ev.clientX - sx) / sc));
                element.y = Math.max(0, Math.min(540 - element.h, oy + (ev.clientY - sy) / sc));
            };
            const onUp = () => { this.isDirty = true; window.removeEventListener('mousemove', onMove); window.removeEventListener('mouseup', onUp); };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        // ── Resize ──
        startResize(e, element, handle) {
            e.stopPropagation(); e.preventDefault();
            const sx = e.clientX, sy = e.clientY, ox = element.x, oy = element.y, ow = element.w, oh = element.h, sc = this.canvasScale;
            const onMove = (ev) => {
                const dx = (ev.clientX - sx) / sc, dy = (ev.clientY - sy) / sc;
                if (handle.includes('e')) element.w = Math.max(30, ow + dx);
                if (handle.includes('s')) element.h = Math.max(10, oh + dy);
                if (handle.includes('w')) { const nw = Math.max(30, ow - dx); element.x = ox + ow - nw; element.w = nw; }
                if (handle.includes('n')) { const nh = Math.max(10, oh - dy); element.y = oy + oh - nh; element.h = nh; }
            };
            const onUp = () => { this.isDirty = true; window.removeEventListener('mousemove', onMove); window.removeEventListener('mouseup', onUp); };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        // ── KPI ──
        kpiValue(src) {
            const m = { total:PROJECT_KPI.total, done:PROJECT_KPI.done, in_progress:PROJECT_KPI.in_progress,
                        overdue:PROJECT_KPI.overdue, progress_pct:PROJECT_KPI.progress_pct+'%', members:PROJECT_KPI.members };
            return m[src] ?? '--';
        },

        // ── Gantt SVG ──
        buildGanttSvg(el) {
            const tasks = PROJECT_DATA.tasks.filter(t => t.start_date && t.due_date);
            const W = el.w - 4;
            if (!tasks.length) {
                return `<svg width="${W}" height="60" xmlns="http://www.w3.org/2000/svg"><text x="${W/2}" y="35" text-anchor="middle" fill="#9ca3af" font-size="11" font-family="sans-serif">No tasks with scheduled dates</text></svg>`;
            }
            const allDates = tasks.flatMap(t => [new Date(t.start_date), new Date(t.due_date)]);
            const minDate = new Date(Math.min(...allDates));
            const maxDate = new Date(Math.max(...allDates));
            const totalDays = Math.max(1, (maxDate - minDate) / 86400000 + 1);
            const labelW = 130, chartW = W - labelW;
            const rowH = Math.max(16, Math.min(26, (el.h - 36) / Math.max(tasks.length, 1)));
            const headerH = 28;
            const H = headerH + tasks.length * rowH + 2;
            const sc = { done:'#16a34a', in_progress:'#4f46e5', review:'#f59e0b', todo:'#94a3b8', cancelled:'#ef4444' };
            let s = `<svg width="${W}" height="${H}" xmlns="http://www.w3.org/2000/svg" style="font-family:sans-serif">`;
            s += `<rect width="${W}" height="${H}" fill="#f8fafc" rx="3"/>`;
            s += `<rect width="${W}" height="${headerH}" fill="#e2e8f0" rx="3"/>`;
            s += `<rect y="${headerH-4}" width="${W}" height="4" fill="#e2e8f0"/>`;
            // Month grid lines + labels
            const cur = new Date(minDate.getFullYear(), minDate.getMonth(), 1);
            while (cur <= maxDate) {
                const xOff = Math.max(0, ((cur - minDate) / 86400000) / totalDays * chartW);
                const lx = labelW + xOff;
                s += `<line x1="${lx}" y1="${headerH}" x2="${lx}" y2="${H}" stroke="#e2e8f0" stroke-width="1"/>`;
                s += `<text x="${lx+2}" y="19" font-size="9" fill="#64748b">${cur.toLocaleString('default',{month:'short'})} ${String(cur.getFullYear()).slice(-2)}</text>`;
                cur.setMonth(cur.getMonth() + 1);
            }
            // Separator line
            s += `<line x1="${labelW}" y1="${headerH}" x2="${labelW}" y2="${H}" stroke="#cbd5e1" stroke-width="1"/>`;
            // Task rows
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
                if (t.progress_pct > 0) {
                    s += `<rect x="${bx}" y="${y+3}" width="${bw*t.progress_pct/100}" height="${rowH-6}" rx="2" fill="rgba(255,255,255,.3)"/>`;
                }
            });
            // Today line
            const today = new Date();
            if (today >= minDate && today <= maxDate) {
                const tx = labelW + ((today-minDate)/86400000)/totalDays*chartW;
                s += `<line x1="${tx}" y1="${headerH}" x2="${tx}" y2="${H}" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="4,2"/>`;
                s += `<text x="${tx+2}" y="${headerH-2}" font-size="8" fill="#ef4444">Today</text>`;
            }
            s += '</svg>';
            return s;
        },

        // ── Charts ──
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
                labels = ['Todo','In Progress','Review','Done','Cancelled'];
                values = [CHART_DATA.tasksByStatus.todo,CHART_DATA.tasksByStatus.in_progress,CHART_DATA.tasksByStatus.review,CHART_DATA.tasksByStatus.done,CHART_DATA.tasksByStatus.cancelled];
                colors = ['#94a3b8','#6366f1','#f59e0b','#22c55e','#ef4444'];
            } else if (ds === 'priority') {
                labels = ['Critical','High','Medium','Low'];
                values = [CHART_DATA.tasksByPriority.critical,CHART_DATA.tasksByPriority.high,CHART_DATA.tasksByPriority.medium,CHART_DATA.tasksByPriority.low];
                colors = ['#ef4444','#f97316','#eab308','#22c55e'];
            } else {
                labels = CHART_DATA.tasksByAssignee.map(a => a.name);
                values = CHART_DATA.tasksByAssignee.map(a => a.count);
                colors = ['#6366f1','#8b5cf6','#a78bfa','#4f46e5','#818cf8','#c4b5fd'];
            }
            const type = el.props.chart_type ?? 'doughnut';
            this._chartInstances[el.id] = new Chart(canvas, {
                type,
                data: { labels, datasets: [{ data:values, backgroundColor:colors, borderWidth:1, borderColor:'#fff', tension:0.4 }] },
                options: {
                    responsive:true, maintainAspectRatio:false,
                    plugins: { legend:{ position:'right', labels:{ font:{size:10}, boxWidth:10 } } },
                    scales: (type==='bar'||type==='line') ? { y:{ beginAtZero:true, ticks:{stepSize:1} } } : {},
                },
            });
        },

        // ── Image upload with canvas compression ──
        async compressImage(file) {
            return new Promise((resolve) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    URL.revokeObjectURL(url);
                    const maxW = 1920, maxH = 1080;
                    let w = img.width, h = img.height;
                    if (w > maxW) { h = Math.round(h * maxW / w); w = maxW; }
                    if (h > maxH) { w = Math.round(w * maxH / h); h = maxH; }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob(resolve, file.type === 'image/png' ? 'image/png' : 'image/jpeg', 0.85);
                };
                img.src = url;
            });
        },
        async uploadImage(e) {
            const file = e.target.files[0];
            if (!file) return;
            e.target.value = '';
            const compressed = await this.compressImage(file);
            const fd = new FormData();
            fd.append('image', compressed, file.name);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            const res = await fetch(UPLOAD_IMG_URL, { method:'POST', body:fd });
            const data = await res.json();
            this.addElement('image');
            const el = this.currentSlide.elements[this.currentSlide.elements.length - 1];
            el.props.url = data.url;
            this.isDirty = true;
        },
        replaceImage(el) { this._replaceTarget = el; this.$refs.replaceImgInput.click(); },
        async doReplaceImage(e) {
            const file = e.target.files[0];
            if (!file || !this._replaceTarget) return;
            e.target.value = '';
            const compressed = await this.compressImage(file);
            const fd = new FormData();
            fd.append('image', compressed, file.name);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            const res = await fetch(UPLOAD_IMG_URL, { method:'POST', body:fd });
            const data = await res.json();
            this._replaceTarget.props.url = data.url;
            this._replaceTarget = null;
            this.isDirty = true;
        },

        // ── Save ──
        async save() {
            this.isSaving = true;
            try {
                const res = await fetch(SAVE_URL, {
                    method:'PUT',
                    headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ slides: this.slides }),
                });
                const data = await res.json();
                if (data.success) { this.slides = data.slides; this.isDirty = false; this.$nextTick(() => this.renderAllCharts()); }
            } catch(err) { console.error('Save failed', err); }
            this.isSaving = false;
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
            const data = await res.json();
            if (data.success) this.showTemplateSave = false;
        },
    };
}
</script>
@endpush
