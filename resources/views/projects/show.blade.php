@extends('layouts.app')
@section('title', $project->name)
@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500">Projects</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>{{ $project->name }}</span>
@endsection

@section('content')
<div x-data="projectShow()" class="space-y-4">

    {{-- Toast notifications --}}
    <div class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2 pointer-events-none">
        <div x-show="savedFlash" x-transition
             class="flex items-center gap-2 bg-green-600 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-lg pointer-events-auto">
            <i class="ti ti-check text-base"></i> บันทึกสำเร็จ
        </div>
        <div x-show="saveError" x-transition
             class="flex items-center gap-2 bg-red-600 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-lg pointer-events-auto">
            <i class="ti ti-alert-circle text-base"></i> บันทึกไม่สำเร็จ กรุณาลองใหม่
        </div>
    </div>

    {{-- Project Header (compact, white + priority border-bottom) --}}
    @php
        $priorityBorderColor = match($project->priority) {
            'critical' => '#ef4444',
            'high'     => '#f97316',
            'medium'   => '#3b82f6',
            default    => '#9ca3af',
        };
    @endphp
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-md"
         style="border-bottom: 4px solid {{ $priorityBorderColor }}">
        <div class="px-4 py-2.5">
            {{-- Row 1: dot + name + badges + edit --}}
            <div class="flex items-center gap-2 flex-wrap min-w-0">
                <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $project->color }}"></div>
                <h1 class="text-sm font-bold text-gray-900 dark:text-white mr-1 truncate">{{ $project->name }}</h1>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0
                             bg-{{ $project->status_badge_color }}-100 text-{{ $project->status_badge_color }}-700
                             dark:bg-{{ $project->status_badge_color }}-900/30 dark:text-{{ $project->status_badge_color }}-400">
                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                </span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0
                             bg-{{ $project->priority_badge_color }}-100 text-{{ $project->priority_badge_color }}-700
                             dark:bg-{{ $project->priority_badge_color }}-900/30 dark:text-{{ $project->priority_badge_color }}-400">
                    {{ ucfirst($project->priority) }}
                </span>
                @if($project->is_cross_factory)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                    <i class="ti ti-building-factory-2 mr-0.5" style="font-size:9px"></i>Cross-Factory
                </span>
                @endif
                @if($project->factory)
                <span class="text-[10px] text-gray-400 flex-shrink-0">{{ $project->factory->name_th }}</span>
                @endif
                <a href="{{ route('projects.edit', $project) }}"
                   class="ml-auto inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-medium flex-shrink-0
                          bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="ti ti-edit"></i> Edit
                </a>
            </div>
            {{-- Row 2: progress bar + % + dates --}}
            <div class="flex items-center gap-2 mt-1.5">
                <span class="text-[10px] text-gray-400 flex-shrink-0">Progress</span>
                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500"
                         style="width: {{ $project->progress_pct }}%; background-color: {{ $project->color }}"></div>
                </div>
                <span class="text-[10px] font-bold flex-shrink-0" style="color: {{ $project->color }}">{{ $project->progress_pct }}%</span>
                @if($project->start_date || $project->end_date)
                <span class="text-[10px] text-gray-400 flex-shrink-0 hidden sm:block">
                    @if($project->start_date){{ $project->start_date->format('d/m/Y') }}@endif
                    @if($project->start_date && $project->end_date) → @endif
                    @if($project->end_date){{ $project->end_date->format('d/m/Y') }}@endif
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden"
         style="border-top: 3px solid {{ $project->color }}">
        {{-- Tab Nav --}}
        <div class="flex border-b border-gray-100 dark:border-gray-700 overflow-x-auto">
            @foreach(['overview' => ['ti-dashboard', 'Overview'], 'tasks' => ['ti-layout-kanban', 'Tasks'], 'gantt' => ['ti-chart-gantt', 'Gantt'], 'calendar' => ['ti-calendar', 'Calendar'], 'members' => ['ti-users', 'Members'], 'reports' => ['ti-chart-bar', 'Reports']] as $tab => [$icon, $label])
            <button @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}' ? 'border-b-2 border-primary text-primary' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                    class="flex items-center gap-1.5 px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors flex-shrink-0">
                <i class="ti {{ $icon }}"></i> {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ═══ TAB: OVERVIEW ═══ --}}
        <div x-show="activeTab === 'overview'" class="p-5 space-y-5">

            {{-- KPI Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach([['Total Tasks', $kpi['total'], 'ti-list', 'blue'], ['Done', $kpi['done'], 'ti-circle-check', 'green'], ['In Progress', $kpi['in_progress'], 'ti-loader-2', 'yellow'], ['Overdue', $kpi['overdue'], 'ti-alarm', 'red']] as [$label, $val, $icon, $color])
                <div class="bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 rounded-xl p-4 border border-{{ $color }}-100 dark:border-{{ $color }}-800/30">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $label }}</span>
                        <i class="ti {{ $icon }} text-{{ $color }}-500 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-{{ $color }}-700 dark:text-{{ $color }}-300">{{ $val }}</p>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Upcoming Milestones --}}
                <div>
                    <h3 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">
                        <i class="ti ti-flag mr-1"></i> Upcoming Milestones
                    </h3>
                    @forelse($upcomingMilestones as $ms)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 mb-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $ms->name }}</p>
                            <p class="text-xs text-gray-400">{{ $ms->due_date->format('d M Y') }}</p>
                        </div>
                        @if($ms->due_date->isPast())
                        <span class="text-xs text-red-500 flex-shrink-0">Overdue</span>
                        @else
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $ms->due_date->diffForHumans() }}</span>
                        @endif
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">No upcoming milestones</p>
                    @endforelse
                </div>

                {{-- Member list --}}
                <div>
                    <h3 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">
                        <i class="ti ti-users mr-1"></i> Team Members
                    </h3>
                    @foreach($project->members->take(8) as $m)
                    @if($m->user)
                    <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/40 mb-1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background-color: var(--color-primary)">
                            {{ strtoupper(substr($m->user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $m->user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $m->user->factory?->name_th }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                            {{ ucfirst($m->role) }}
                        </span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            {{-- Objective --}}
            @if($project->objective)
            <div>
                <h3 class="text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Objective</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $project->objective }}</p>
            </div>
            @endif
        </div>

        {{-- ═══ TAB: TASKS (KANBAN) ═══ --}}
        <div x-show="activeTab === 'tasks'" class="p-4">

            {{-- Add Task button --}}
            <div class="flex justify-end mb-3">
                <button @click="showAddTask = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium btn-primary">
                    <i class="ti ti-plus"></i> Add Task
                </button>
            </div>

            {{-- Add Task form (inline) --}}
            <div x-show="showAddTask" x-transition class="mb-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                <form @submit.prevent="addTask()" class="space-y-3">
                    <input type="text" x-model="newTask.title" placeholder="Task title" class="form-input w-full" required>
                    <div class="grid grid-cols-2 gap-3">
                        <select x-model="newTask.status" class="form-select text-sm">
                            <option value="todo">Todo</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="done">Done</option>
                        </select>
                        <select x-model="newTask.priority" class="form-select text-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                        <select x-model="newTask.assignee_id" class="form-select text-sm">
                            <option value="">Unassigned</option>
                            @foreach($memberUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input type="date" x-model="newTask.due_date" class="form-input text-sm">
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="showAddTask = false" class="px-3 py-1.5 text-sm rounded-xl bg-gray-200 dark:bg-gray-600">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 text-sm rounded-xl btn-primary">Save Task</button>
                    </div>
                </form>
            </div>

            {{-- Kanban columns --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach(['todo' => ['Todo', 'gray'], 'in_progress' => ['In Progress', 'blue'], 'review' => ['Review', 'yellow'], 'done' => ['Done', 'green']] as $status => [$label, $color])
                <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-3"
                     id="kanban-{{ $status }}"
                     data-status="{{ $status }}">

                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-{{ $color }}-400"></div>
                            <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{{ $label }}</h3>
                        </div>
                        <span class="text-xs bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full px-2 py-0.5">
                            {{ $kanbanGroups[$status]->count() }}
                        </span>
                    </div>

                    <div class="space-y-2 min-h-[4rem] kanban-list" id="list-{{ $status }}">
                        @foreach($kanbanGroups[$status] as $task)
                        <div x-data="taskCard({{ $task->id }})"
                             class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-3 shadow-sm cursor-pointer hover:shadow-md transition-shadow task-card"
                             :style="{ borderLeft: '4px solid ' + statusColor }"
                             data-task-id="{{ $task->id }}"
                             @task-saved.window="onSaved($event.detail)"
                             @click="!isDragging && openDrawer(taskId)">

                            <p class="text-sm font-medium leading-snug line-clamp-2" x-text="title"></p>

                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs px-1.5 py-0.5 rounded-md" :class="priorityClass" x-text="priorityLabel"></span>
                                <span x-show="dueDate" class="text-xs" :class="isOverdue ? 'text-red-500' : 'text-gray-400'" x-text="dueDateFmt"></span>
                            </div>

                            <div x-show="checklists.length" class="flex items-center gap-1 mt-2 text-xs text-gray-400">
                                <i class="ti ti-checkbox"></i>
                                <span x-text="checklists.filter(c=>c.is_completed).length + '/' + checklists.length"></span>
                            </div>

                            <div x-show="assigneeName" class="flex items-center gap-1 mt-2">
                                <div class="w-5 h-5 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0"
                                     style="background-color: var(--color-primary)"
                                     x-text="assigneeName.charAt(0).toUpperCase()"></div>
                                <span class="text-xs text-gray-400 truncate" x-text="assigneeName"></span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ═══ TAB: GANTT ═══ --}}
        <div x-show="activeTab === 'gantt'">
        <div x-data="ganttChart()" class="select-none"
             x-effect="if(activeTab==='gantt') $nextTick(()=>fitAll())"
             @task-saved.window="updateGanttRow($event.detail)">

            {{-- Controls --}}
            <div class="flex items-center gap-2 p-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                    <template x-for="m in ['Day','Week','Month']" :key="m">
                        <button @click="setView(m)"
                                :class="viewMode===m ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 text-xs font-medium border-r border-gray-200 dark:border-gray-600 last:border-r-0 transition-colors"
                                x-text="m"></button>
                    </template>
                </div>
                <button @click="scrollToday()"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="ti ti-calendar-event mr-1"></i>Today
                </button>
                <button @click="fitAll()"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="ti ti-arrows-maximize mr-1"></i>Fit All
                </button>
                <span class="ml-auto text-xs text-gray-400" x-show="rows.length"
                      x-text="`${rows.filter(r=>!r.isMilestone).length} tasks`"></span>
            </div>

            {{-- Empty state --}}
            <div x-show="!rows.length" class="py-16 text-center text-gray-400">
                <i class="ti ti-chart-gantt text-4xl mb-3 block"></i>
                <p class="text-sm">ไม่มีงานที่มีกำหนดวันที่</p>
            </div>

            {{-- Excel-like layout: LEFT TABLE + RIGHT TIMELINE --}}
            {{-- height: 100vh - topbar(64) - main-padding(48) - compact-header(66) - space-y-4(16) - tab-nav(44) - gantt-controls(44) - buffer(18) --}}
            <div x-show="rows.length" class="flex overflow-hidden border-t border-gray-200 dark:border-gray-700" style="height:calc(100vh - 300px);min-height:340px">

                {{-- LEFT TABLE (fixed, no horizontal scroll) --}}
                <div x-ref="ganttLeft" @scroll.passive="syncScroll('left')"
                     class="overflow-y-auto overflow-x-hidden flex-shrink-0 border-r-2 border-gray-300 dark:border-gray-600"
                     style="width:450px">

                    {{-- Left header --}}
                    <div class="flex items-stretch bg-gray-100 dark:bg-gray-700 border-b-2 border-gray-300 dark:border-gray-600"
                         style="position:sticky;top:0;z-index:10;height:36px;min-height:36px">
                        <div class="flex items-center px-2 text-xs font-semibold text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600"
                             style="width:180px;min-width:180px">งานที่ต้องทำ</div>
                        <div class="flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600"
                             style="width:50px;min-width:50px">เริ่ม</div>
                        <div class="flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600"
                             style="width:35px;min-width:35px">วัน</div>
                        <div class="flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600"
                             style="width:50px;min-width:50px">สิ้นสุด</div>
                        <div class="flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-600"
                             style="width:70px;min-width:70px">%</div>
                        <div class="flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 flex-1">สถานะ</div>
                    </div>

                    {{-- Left rows --}}
                    <template x-for="row in rows" :key="row.id">
                        <div class="flex items-stretch border-b border-gray-100 dark:border-gray-700"
                             style="height:36px;min-height:36px"
                             :class="!row.isMilestone ? 'hover:bg-indigo-50/50 dark:hover:bg-gray-700/30' : ''">

                            {{-- Milestone row --}}
                            <template x-if="row.isMilestone">
                                <div class="flex items-center w-full px-3 gap-2" style="background:#1e40af">
                                    <i class="ti ti-flag-3 text-white/80 text-sm flex-shrink-0"></i>
                                    <span class="text-white font-bold text-xs truncate flex-1" x-text="row.name"></span>
                                    <span class="text-white/60 text-xs flex-shrink-0 tabular-nums"
                                          x-text="row.startDate ? fmtDate(row.startDate)+' – '+fmtDate(row.endDate) : ''"></span>
                                </div>
                            </template>

                            {{-- Task row --}}
                            <template x-if="!row.isMilestone">
                                <div class="flex items-stretch w-full bg-white dark:bg-gray-800">
                                    <div class="flex items-center gap-1.5 overflow-hidden border-r border-gray-100 dark:border-gray-700"
                                         style="width:180px;min-width:180px;padding:0 6px 0 20px">
                                        <div class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: barBg(row).fill }"></div>
                                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate cursor-pointer hover:underline"
                                              @click="openDrawer(row.taskId)" x-text="row.name" :title="row.name"></span>
                                    </div>
                                    <div class="flex items-center justify-center text-xs text-gray-500 dark:text-gray-400 tabular-nums border-r border-gray-100 dark:border-gray-700"
                                         style="width:50px;min-width:50px" x-text="fmtDate(row.startDate)"></div>
                                    <div class="flex items-center justify-center text-xs text-gray-500 dark:text-gray-400 tabular-nums border-r border-gray-100 dark:border-gray-700"
                                         style="width:35px;min-width:35px" x-text="row.duration+'d'"></div>
                                    <div class="flex items-center justify-center text-xs text-gray-500 dark:text-gray-400 tabular-nums border-r border-gray-100 dark:border-gray-700"
                                         style="width:50px;min-width:50px" x-text="fmtDate(row.endDate)"></div>
                                    <div class="flex flex-col items-center justify-center gap-0.5 border-r border-gray-100 dark:border-gray-700 px-1"
                                         style="width:70px;min-width:70px">
                                        <span class="text-xs font-semibold tabular-nums leading-none"
                                              :style="{ color: barBg(row).fill }" x-text="row.pct+'%'"></span>
                                        <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full" :style="{ width: row.pct + '%', background: barBg(row).fill }"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-center flex-1 text-[10px] font-medium truncate px-1"
                                         :style="`color:${dotColor(row.status)}`" x-text="statusTh(row.status)"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- RIGHT TIMELINE (horizontal scroll) --}}
                <div x-ref="ganttRight" @scroll.passive="syncScroll('right')"
                     class="flex-1 overflow-auto" style="min-width:0">
                    <div :style="`width:${timelineW}px;min-width:${timelineW}px`">

                        {{-- Date header --}}
                        <div class="relative bg-gray-100 dark:bg-gray-700 border-b-2 border-gray-300 dark:border-gray-600"
                             style="position:sticky;top:0;z-index:10;height:36px;min-height:36px">
                            <template x-for="col in cols" :key="col.key">
                                <div class="absolute top-0 bottom-0 flex items-center justify-center text-xs text-gray-600 dark:text-gray-300 font-medium border-r border-gray-200 dark:border-gray-600 select-none overflow-hidden"
                                     :class="col.weekend ? 'bg-gray-200 dark:bg-gray-600/60' : ''"
                                     :style="`left:${col.left}px;width:${col.width}px`"
                                     x-text="col.label"></div>
                            </template>
                            <div x-show="todayLeft>=0"
                                 class="absolute top-0 bottom-0 bg-red-500 pointer-events-none"
                                 style="width:2px;z-index:20"
                                 :style="{ left: todayLeft + 'px' }"></div>
                        </div>

                        {{-- Bar rows --}}
                        <template x-for="row in rows" :key="row.id">
                            <div class="relative border-b border-gray-100 dark:border-gray-700/50"
                                 style="height:36px;min-height:36px"
                                 :class="row.isMilestone ? 'bg-blue-900/5 dark:bg-blue-900/15' : 'bg-white dark:bg-gray-800'">

                                {{-- Column grid lines --}}
                                <template x-for="col in cols" :key="col.key">
                                    <div class="absolute top-0 bottom-0 border-r border-gray-100 dark:border-gray-700/30 pointer-events-none"
                                         :class="col.weekend ? 'bg-gray-50 dark:bg-gray-700/20' : ''"
                                         :style="`left:${col.left}px;width:${col.width}px`"></div>
                                </template>

                                {{-- Today line --}}
                                <div x-show="todayLeft>=0"
                                     class="absolute top-0 bottom-0 bg-red-500/50 pointer-events-none"
                                     style="width:2px;z-index:5"
                                     :style="{ left: todayLeft + 'px' }"></div>

                                {{-- MILESTONE bar --}}
                                <template x-if="row.isMilestone && row.barLeft!==null && row.barWidth">
                                    <div class="absolute rounded pointer-events-none"
                                         style="background:#1e40af;opacity:0.85;top:12px;bottom:12px;z-index:3"
                                         :style="{ left: row.barLeft + 'px', width: row.barWidth + 'px' }">
                                    </div>
                                </template>

                                {{-- TASK bar (non-todo, colored + progress split) --}}
                                <template x-if="!row.isMilestone && row.status!=='todo' && row.barLeft!==null">
                                    <div class="absolute rounded group cursor-grab active:cursor-grabbing"
                                         style="top:9px;bottom:9px;z-index:4;overflow:hidden"
                                         :style="{ left: row.barLeft + 'px', width: Math.max(row.barWidth, 8) + 'px', background: barBg(row).base }"
                                         @click.prevent="!_barDragging && openDrawer(row.taskId)"
                                         @mousedown.prevent="startDrag($event,row)"
                                         :title="`${row.name} | ${fmtDate(row.startDate)} – ${fmtDate(row.endDate)} | ${row.pct}%`">
                                        <div class="absolute top-0 left-0 bottom-0 pointer-events-none"
                                             :style="{ width: row.pct + '%', background: barBg(row).fill }"></div>
                                        <div class="absolute right-0 top-0 bottom-0 w-2.5 cursor-ew-resize flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10"
                                             style="background:rgba(0,0,0,0.1)"
                                             @mousedown.stop.prevent="startResize($event,row)">
                                            <div class="w-px h-3/4 bg-white/80 rounded"></div>
                                        </div>
                                    </div>
                                </template>

                                {{-- TASK bar (todo: dashed outline only) --}}
                                <template x-if="!row.isMilestone && row.status==='todo' && row.barLeft!==null">
                                    <div class="absolute rounded cursor-grab active:cursor-grabbing"
                                         style="top:9px;bottom:9px;z-index:4;border:1.5px dashed #9ca3af;background:rgba(156,163,175,0.08)"
                                         :style="{ left: row.barLeft + 'px', width: Math.max(row.barWidth, 8) + 'px' }"
                                         @click.prevent="!_barDragging && openDrawer(row.taskId)"
                                         @mousedown.prevent="startDrag($event,row)"
                                         :title="`${row.name} | ${fmtDate(row.startDate)} – ${fmtDate(row.endDate)} | ยังไม่เริ่ม`">
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- /gantt outer x-show --}}

        {{-- ═══ TAB: CALENDAR ═══ --}}
        <div x-show="activeTab === 'calendar'" class="p-4" x-data="projectCalendar()">
            <div class="flex items-center justify-between mb-4">
                <button @click="prevMonth()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <h3 class="font-semibold" x-text="monthLabel()"></h3>
                <button @click="nextMonth()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>

            {{-- Day headers --}}
            <div class="grid grid-cols-7 gap-1 mb-1">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="text-center text-xs font-medium text-gray-400 py-1">{{ $day }}</div>
                @endforeach
            </div>

            {{-- Calendar grid --}}
            <div class="grid grid-cols-7 gap-1">
                <template x-for="cell in calendarCells" :key="cell.key">
                    <div class="min-h-[70px] rounded-lg p-1 text-xs border border-transparent"
                         :class="cell.isToday ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700' : (cell.isCurrentMonth ? 'bg-gray-50 dark:bg-gray-700/30' : 'opacity-40')">
                        <div class="font-medium mb-1"
                             :class="cell.isToday ? 'text-primary font-bold' : 'text-gray-600 dark:text-gray-400'"
                             x-text="cell.day"></div>
                        <template x-for="ev in cell.events" :key="ev.id">
                            <div class="rounded px-1 py-0.5 mb-0.5 truncate cursor-pointer text-white text-xs"
                                 style="background-color: {{ $project->color }}"
                                 x-text="ev.title"
                                 @click="openDrawer(ev.task_id)"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- ═══ TAB: MEMBERS ═══ --}}
        <div x-show="activeTab === 'members'" class="p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300">Team Members ({{ $project->members->count() }})</h3>
                <button @click="showAddMember = !showAddMember"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium btn-primary">
                    <i class="ti ti-user-plus"></i> Add Member
                </button>
            </div>

            {{-- Add Member form --}}
            <div x-show="showAddMember" x-transition class="mb-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                <form method="POST" action="{{ route('projects.members.add', $project) }}" class="flex gap-3 flex-wrap">
                    @csrf
                    <select name="user_id" class="form-select text-sm flex-1" required>
                        <option value="">— Select User —</option>
                        @foreach($allUsers as $u)
                        @if(!$project->members->pluck('user_id')->contains($u->id))
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->employee_code }})</option>
                        @endif
                        @endforeach
                    </select>
                    <select name="role" class="form-select text-sm w-32" required>
                        @foreach(['member' => 'Member', 'lead' => 'Lead', 'reviewer' => 'Reviewer'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-1.5 text-sm rounded-xl btn-primary">Add</button>
                </form>
            </div>

            {{-- Members table --}}
            <div class="space-y-2">
                @foreach($project->members as $m)
                @if($m->user)
                <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background-color: var(--color-primary)">
                        {{ strtoupper(substr($m->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium">{{ $m->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $m->user->factory?->name_th }} · {{ $m->user->employee_code }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">
                        {{ ucfirst($m->role) }}
                    </span>
                    @php $taskCount = $project->tasks->where('assignee_id', $m->user_id)->whereNotIn('status', ['done', 'cancelled'])->count(); @endphp
                    <span class="text-xs text-gray-400 w-20 text-right">{{ $taskCount }} tasks</span>
                    @if($m->role !== 'manager')
                    <form method="POST" action="{{ route('projects.members.remove', [$project, $m->user]) }}" onsubmit="return confirm('Remove this member?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                            <i class="ti ti-x text-sm"></i>
                        </button>
                    </form>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </div>

        {{-- ═══ TAB: REPORTS ═══ --}}
        <div x-show="activeTab === 'reports'" class="p-5 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Burndown Chart --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4">
                    <h3 class="text-sm font-semibold mb-3">Burndown Chart</h3>
                    <canvas id="burndown-chart" height="200"></canvas>
                </div>
                {{-- Workload Chart --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4">
                    <h3 class="text-sm font-semibold mb-3">Workload per Member</h3>
                    <canvas id="workload-chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ TASK DRAWER ═══ --}}
    @include('projects._task_drawer')

</div>
@endsection

@push('scripts')
<script>
const PROJECT_ID = {{ $project->id }};
const PROJECT_COLOR = '{{ $project->color }}';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const TASK_DATA = {!! json_encode(
    $project->tasks->map(fn($t) => [
        'id'           => $t->id,
        'title'        => $t->title,
        'description'  => $t->description,
        'status'       => $t->status,
        'priority'     => $t->priority,
        'assignee_id'  => $t->assignee_id,
        'assignee'     => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
        'start_date'   => $t->start_date?->format('Y-m-d'),
        'due_date'     => $t->due_date?->format('Y-m-d'),
        'estimated_hours' => $t->estimated_hours,
        'actual_hours' => $t->actual_hours,
        'progress_pct' => $t->progress_pct,
        'milestone_id' => $t->milestone_id,
        'checklists'   => $t->checklists->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'is_completed' => $c->is_completed])->toArray(),
        'subtasks'     => $t->subtasks->map(fn($s) => ['id' => $s->id, 'title' => $s->title, 'status' => $s->status])->toArray(),
    ])
) !!};

const MILESTONE_DATA = {!! json_encode(
    $project->milestones->map(fn($m) => [
        'id'       => $m->id,
        'name'     => $m->name,
        'due_date' => $m->due_date?->format('Y-m-d'),
    ])
) !!};

const MEMBER_USERS = {!! json_encode($memberUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name])) !!};

function projectShow() {
    return {
        activeTab:     'overview',
        showAddTask:   false,
        showAddMember: false,
        drawerOpen:    false,
        drawerTask:    null,
        isDragging:    false,
        savedFlash:    false,
        saveError:     false,
        saveBtn:       'idle',
        newTask: { title: '', status: 'todo', priority: 'medium', assignee_id: '', due_date: '' },

        async addTask() {
            const res = await fetch(`/projects/${PROJECT_ID}/tasks`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(this.newTask),
            });
            if (res.ok) { window.location.reload(); }
        },

        openDrawer(taskId) {
            this.drawerTask = TASK_DATA.find(t => t.id === taskId) || null;
            if (this.drawerTask) this.drawerOpen = true;
        },

        closeDrawer() {
            this.drawerOpen = false;
            this.drawerTask = null;
        },

        async saveTask() {
            if (!this.drawerTask || this.saveBtn === 'saving') return;
            this.saveBtn = 'saving';
            try {
                const res = await fetch(`/project-tasks/${this.drawerTask.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        title:            this.drawerTask.title,
                        description:      this.drawerTask.description,
                        status:           this.drawerTask.status,
                        priority:         this.drawerTask.priority,
                        assignee_id:      this.drawerTask.assignee_id || null,
                        start_date:       this.drawerTask.start_date || null,
                        due_date:         this.drawerTask.due_date || null,
                        estimated_hours:  this.drawerTask.estimated_hours || null,
                        progress_pct:     this.drawerTask.progress_pct,
                    }),
                });
                if (res.ok) {
                    const idx = TASK_DATA.findIndex(t => t.id === this.drawerTask.id);
                    if (idx >= 0) Object.assign(TASK_DATA[idx], {
                        title:           this.drawerTask.title,
                        description:     this.drawerTask.description,
                        status:          this.drawerTask.status,
                        priority:        this.drawerTask.priority,
                        assignee_id:     this.drawerTask.assignee_id,
                        start_date:      this.drawerTask.start_date,
                        due_date:        this.drawerTask.due_date,
                        estimated_hours: this.drawerTask.estimated_hours,
                        progress_pct:    this.drawerTask.progress_pct,
                    });
                    this.$dispatch('task-saved', {
                        id:           this.drawerTask.id,
                        title:        this.drawerTask.title,
                        status:       this.drawerTask.status,
                        priority:     this.drawerTask.priority,
                        due_date:     this.drawerTask.due_date,
                        assignee_id:  this.drawerTask.assignee_id,
                        assignee:     this.drawerTask.assignee,
                        progress_pct: this.drawerTask.progress_pct,
                        start_date:   this.drawerTask.start_date,
                    });
                    this.saveBtn    = 'saved';
                    this.savedFlash = true;
                    setTimeout(() => { this.saveBtn = 'idle'; this.savedFlash = false; }, 1500);
                } else {
                    this.saveBtn   = 'error';
                    this.saveError = true;
                    setTimeout(() => { this.saveBtn = 'idle'; this.saveError = false; }, 2500);
                }
            } catch (_) {
                this.saveBtn   = 'error';
                this.saveError = true;
                setTimeout(() => { this.saveBtn = 'idle'; this.saveError = false; }, 2500);
            }
        },

        async updateTaskField(taskId, field, value) {
            await fetch(`/project-tasks/${taskId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ [field]: value }),
            });
        },

        async loadReports() {
            const [bd, wl] = await Promise.all([
                fetch(`/projects/${PROJECT_ID}/reports/burndown`).then(r => r.json()),
                fetch(`/projects/${PROJECT_ID}/reports/workload`).then(r => r.json()),
            ]);
            new Chart(document.getElementById('burndown-chart'), {
                type: 'line',
                data: {
                    labels: bd.days,
                    datasets: [
                        { label: 'Ideal', data: bd.ideal, borderColor: '#6366f1', borderDash: [5,5], fill: false, tension: 0.1 },
                        { label: 'Actual', data: bd.actual, borderColor: '#ef4444', fill: false, tension: 0.1 },
                    ],
                },
                options: { plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } },
            });
            new Chart(document.getElementById('workload-chart'), {
                type: 'bar',
                data: {
                    labels: wl.labels,
                    datasets: [{ label: 'Open Tasks', data: wl.counts, backgroundColor: PROJECT_COLOR + 'aa' }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } },
            });
        },

        init() {
            this.$watch('activeTab', tab => {
                if (tab === 'reports') this.$nextTick(() => this.loadReports());
            });

            // Kanban drag & drop with SortableJS
            this.$nextTick(() => {
                ['todo', 'in_progress', 'review', 'done'].forEach(status => {
                    const el = document.getElementById('list-' + status);
                    if (!el || typeof Sortable === 'undefined') return;
                    Sortable.create(el, {
                        group:      'kanban',
                        animation:  150,
                        ghostClass: 'opacity-40',
                        onStart: () => { this.isDragging = true; },
                        onEnd: async (evt) => {
                            setTimeout(() => { this.isDragging = false; }, 100);
                            const newStatus = evt.to.closest('[data-status]')?.dataset.status || status;
                            const taskId    = parseInt(evt.item.dataset.taskId);
                            const items     = [...evt.to.children].map((el, i) => ({
                                id: parseInt(el.dataset.taskId), sort: i, status: newStatus,
                            }));
                            await fetch('/project-tasks/reorder', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                                body: JSON.stringify({ tasks: items }),
                            });
                            if (this.drawerTask?.id === taskId) this.drawerTask.status = newStatus;
                            const t = TASK_DATA.find(t => t.id === taskId);
                            if (t) t.status = newStatus;
                        },
                    });
                });
            });
        },
    };
}

function projectCalendar() {
    const now = new Date();
    return {
        year:  now.getFullYear(),
        month: now.getMonth(),
        get calendarCells() {
            const first   = new Date(this.year, this.month, 1);
            const last    = new Date(this.year, this.month + 1, 0);
            const startDow = first.getDay();
            const cells   = [];
            const today   = new Date().toDateString();

            const allEvents = TASK_DATA.filter(t => t.due_date).map(t => ({
                id:      t.id,
                task_id: t.id,
                title:   t.title,
                date:    t.due_date,
            }));

            for (let i = 0; i < 42; i++) {
                const d       = new Date(this.year, this.month, i - startDow + 1);
                const dateStr = d.toISOString().slice(0, 10);
                cells.push({
                    key:            dateStr,
                    day:            d.getDate(),
                    isCurrentMonth: d.getMonth() === this.month,
                    isToday:        d.toDateString() === today,
                    events:         allEvents.filter(e => e.date === dateStr),
                });
            }
            return cells;
        },
        monthLabel() {
            return new Date(this.year, this.month).toLocaleString('default', { month: 'long', year: 'numeric' });
        },
        prevMonth() { if (this.month === 0) { this.month = 11; this.year--; } else this.month--; },
        nextMonth() { if (this.month === 11) { this.month = 0; this.year++; } else this.month++; },
        openDrawer(taskId) { this.$dispatch('open-drawer', { taskId }); },
    };
}

function ganttChart() {
    return {
        viewMode:  'Week',
        ppd:       28,
        rows:      [],
        cols:      [],
        minDate:   null,
        totalDays: 0,
        todayLeft: 0,

        get timelineW() { return this.totalDays * this.ppd; },

        init() {
            this._syncing = false;
            this._barDragging = false;
            this.build();
        },

        setView(mode) {
            this.viewMode = mode;
            this.ppd = { Day: 36, Week: 28, Month: 8 }[mode] ?? 28;
            this.build();
            this.$nextTick(() => this.scrollToday());
        },

        scrollToday() {
            if (this.$refs.ganttRight)
                this.$refs.ganttRight.scrollLeft = Math.max(0, this.todayLeft - 250);
        },

        fitAll() {
            const R = this.$refs.ganttRight;
            if (!R || !this.totalDays) return;
            const availW = Math.max(R.clientWidth, 300);
            const newPpd = Math.max(4, Math.min(28, Math.floor(availW / this.totalDays)));
            if (newPpd !== this.ppd) {
                this.ppd     = newPpd;
                this.viewMode = '';
                this.build();
            }
            this.$nextTick(() => {
                if (this.$refs.ganttRight) this.$refs.ganttRight.scrollLeft = 0;
            });
        },

        syncScroll(from) {
            if (this._syncing) return;
            this._syncing = true;
            const L = this.$refs.ganttLeft, R = this.$refs.ganttRight;
            if (from === 'left'  && L && R) R.scrollTop = L.scrollTop;
            if (from === 'right' && L && R) L.scrollTop = R.scrollTop;
            requestAnimationFrame(() => { this._syncing = false; });
        },

        openDrawer(id) { this.$dispatch('open-drawer', { taskId: id }); },

        fmtDate(str) {
            if (!str) return '-';
            const d = new Date(str + 'T00:00:00');
            return d.getDate() + '/' + (d.getMonth() + 1);
        },

        statusTh(s) {
            return { done:'เสร็จแล้ว', in_progress:'กำลังทำ', review:'Review', todo:'ยังไม่เริ่ม', cancelled:'ยกเลิก' }[s] ?? s;
        },

        dotColor(s) {
            return { done:'#16a34a', in_progress:'#2563eb', review:'#d97706', todo:'#9ca3af', cancelled:'#ef4444' }[s] ?? '#9ca3af';
        },

        barBg(row) {
            const p = row.pct || 0;
            if (row.status === 'done' || p >= 100) return { base:'rgba(22,163,74,0.18)',   fill:'#15803d' };
            if (p >= 70)  return { base:'rgba(37,99,235,0.18)',  fill:'#1d4ed8' };
            if (p >= 30)  return { base:'rgba(217,119,6,0.18)',  fill:'#b45309' };
            if (p > 0)    return { base:'rgba(220,38,38,0.18)',  fill:'#b91c1c' };
            return { base:'rgba(156,163,175,0.15)', fill:'#9ca3af' };
        },

        build() {
            const ppd = this.ppd;
            const all = TASK_DATA.filter(t => t.start_date && t.due_date);
            if (!all.length) { this.rows = []; this.cols = []; return; }

            const dts  = all.flatMap(t => [new Date(t.start_date+'T00:00:00'), new Date(t.due_date+'T00:00:00')]);
            const minD = new Date(Math.min(...dts.map(d => d.getTime())));
            const maxD = new Date(Math.max(...dts.map(d => d.getTime())));
            minD.setDate(minD.getDate() - 3);
            maxD.setDate(maxD.getDate() + 14);
            this.minDate   = minD;
            this.totalDays = Math.ceil((maxD - minD) / 86400000) + 1;

            const today = new Date(); today.setHours(0,0,0,0);
            this.todayLeft = Math.floor((today - minD) / 86400000) * ppd;

            this.buildCols(minD, maxD, ppd);

            const rows = [];

            (MILESTONE_DATA || []).forEach(ms => {
                const msTasks = all.filter(t => t.milestone_id === ms.id);
                let msS = null, msE = null;
                msTasks.forEach(t => {
                    const s = new Date(t.start_date+'T00:00:00'), e = new Date(t.due_date+'T00:00:00');
                    if (!msS || s < msS) msS = s;
                    if (!msE || e > msE) msE = e;
                });
                if (!msS && ms.due_date) { msS = new Date(ms.due_date+'T00:00:00'); msE = msS; }

                const bL = msS ? Math.floor((msS - minD) / 86400000) * ppd : null;
                const bW = (msS && msE) ? Math.max(Math.ceil((msE - msS) / 86400000) * ppd, ppd) : null;
                const doneCnt = msTasks.filter(t => t.status==='done').length;
                const msPct   = msTasks.length ? Math.round(doneCnt / msTasks.length * 100) : 0;

                rows.push({
                    id: 'ms-'+ms.id, isMilestone: true, name: ms.name,
                    startDate: msS ? msS.toISOString().slice(0,10) : null,
                    endDate:   msE ? msE.toISOString().slice(0,10) : (ms.due_date || null),
                    duration:  (msS && msE) ? Math.ceil((msE - msS) / 86400000) + 1 : 0,
                    pct: msPct,
                    status: msTasks.every(t=>t.status==='done') ? 'done' : msTasks.some(t=>['in_progress','review'].includes(t.status)) ? 'in_progress' : 'todo',
                    barLeft: bL, barWidth: bW, taskId: null,
                });
                msTasks.forEach(t => rows.push(this.mkRow(t, minD, ppd)));
            });

            const knownMsIds = new Set((MILESTONE_DATA || []).map(m => m.id));
            const free = all.filter(t => !t.milestone_id || !knownMsIds.has(t.milestone_id));
            if (free.length) {
                rows.push({ id:'ms-none', isMilestone:true, name:'ไม่มี Phase',
                    startDate:null, endDate:null, duration:0, pct:0, status:'todo',
                    barLeft:null, barWidth:null, taskId:null });
                free.forEach(t => rows.push(this.mkRow(t, minD, ppd)));
            }

            this.rows = rows;
        },

        mkRow(t, minD, ppd) {
            const s = new Date(t.start_date+'T00:00:00'), e = new Date(t.due_date+'T00:00:00');
            return {
                id: 'task-'+t.id, taskId: t.id, isMilestone: false,
                name: t.title, startDate: t.start_date, endDate: t.due_date,
                duration: Math.ceil((e - s) / 86400000) + 1,
                pct: t.progress_pct ?? 0, status: t.status,
                barLeft:  Math.floor((s - minD) / 86400000) * ppd,
                barWidth: Math.max(Math.ceil((e - s) / 86400000) * ppd, ppd),
            };
        },

        buildCols(minD, maxD, ppd) {
            const cols = [];
            if (this.viewMode === 'Day') {
                let d = new Date(minD);
                while (d <= maxD) {
                    const dow = d.getDay();
                    cols.push({ key: d.toISOString().slice(0,10),
                        label: d.getDate()+'/'+(d.getMonth()+1),
                        left: Math.floor((d-minD)/86400000)*ppd,
                        width: ppd, weekend: dow===0||dow===6 });
                    d = new Date(d); d.setDate(d.getDate()+1);
                }
            } else if (this.viewMode === 'Week') {
                let d = new Date(minD);
                const dow = d.getDay(); d.setDate(d.getDate()-(dow===0?6:dow-1));
                while (d <= maxD) {
                    cols.push({ key: d.toISOString().slice(0,10),
                        label: d.getDate()+'/'+(d.getMonth()+1),
                        left: Math.max(0, Math.floor((d-minD)/86400000)*ppd),
                        width: 7*ppd, weekend: false });
                    d = new Date(d); d.setDate(d.getDate()+7);
                }
            } else {
                let d = new Date(minD.getFullYear(), minD.getMonth(), 1);
                while (d <= maxD) {
                    const days = new Date(d.getFullYear(), d.getMonth()+1, 0).getDate();
                    cols.push({ key: d.toISOString().slice(0,10),
                        label: d.toLocaleString('th-TH',{month:'short',year:'2-digit'}),
                        left: Math.max(0, Math.floor((d-minD)/86400000)*ppd),
                        width: days*ppd, weekend: false });
                    d = new Date(d.getFullYear(), d.getMonth()+1, 1);
                }
            }
            this.cols = cols;
        },

        updateGanttRow(detail) {
            const row = this.rows.find(r => r.taskId === detail.id);
            if (!row) return;
            if (detail.status       !== undefined) row.status = detail.status;
            if (detail.progress_pct !== undefined) row.pct    = detail.progress_pct;
            if (detail.title        !== undefined) row.name   = detail.title;
            if (detail.start_date   !== undefined && detail.start_date) {
                row.startDate = detail.start_date;
                const s = new Date(detail.start_date + 'T00:00:00');
                row.barLeft   = Math.floor((s - this.minDate) / 86400000) * this.ppd;
            }
            if (detail.due_date !== undefined && detail.due_date) {
                row.endDate = detail.due_date;
                const s = new Date(row.startDate + 'T00:00:00');
                const e = new Date(detail.due_date  + 'T00:00:00');
                row.barWidth  = Math.max(Math.ceil((e - s) / 86400000) * this.ppd, this.ppd);
                row.duration  = Math.ceil((e - s) / 86400000) + 1;
            }
        },

        startDrag(e, row) {
            if (e.button!==0) return;
            const startX=e.clientX, origL=row.barLeft, ppd=this.ppd;
            let hasMoved=false;
            const snap = v => Math.round(v/ppd)*ppd;
            const onMove = ev => {
                if (Math.abs(ev.clientX-startX)>3) hasMoved=true;
                row.barLeft = Math.max(0, snap(origL+(ev.clientX-startX)));
            };
            const onUp = async () => {
                window.removeEventListener('mousemove',onMove); window.removeEventListener('mouseup',onUp);
                if (!hasMoved) return;
                this._barDragging=true; setTimeout(()=>{ this._barDragging=false; },100);
                const ns=new Date(this.minDate.getTime()+(row.barLeft/ppd)*86400000);
                const ne=new Date(this.minDate.getTime()+((row.barLeft+row.barWidth)/ppd)*86400000);
                const ss=ns.toISOString().slice(0,10), ee=ne.toISOString().slice(0,10);
                row.startDate=ss; row.endDate=ee;
                await fetch(`/project-tasks/${row.taskId}`,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({start_date:ss,due_date:ee})});
                const t=TASK_DATA.find(t=>t.id===row.taskId); if(t){t.start_date=ss;t.due_date=ee;}
            };
            window.addEventListener('mousemove',onMove); window.addEventListener('mouseup',onUp);
        },

        startResize(e, row) {
            if (e.button!==0) return;
            const startX=e.clientX, origW=row.barWidth, ppd=this.ppd;
            const snap = v => Math.max(ppd, Math.round(v/ppd)*ppd);
            const onMove = e => { row.barWidth = snap(origW+(e.clientX-startX)); };
            const onUp = async () => {
                window.removeEventListener('mousemove',onMove); window.removeEventListener('mouseup',onUp);
                const ne=new Date(this.minDate.getTime()+((row.barLeft+row.barWidth)/ppd)*86400000);
                const ee=ne.toISOString().slice(0,10);
                row.endDate=ee;
                await fetch(`/project-tasks/${row.taskId}`,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({due_date:ee})});
                const t=TASK_DATA.find(t=>t.id===row.taskId); if(t) t.due_date=ee;
            };
            window.addEventListener('mousemove',onMove); window.addEventListener('mouseup',onUp);
        },
    };
}

function taskCard(id) {
    const t = TASK_DATA.find(t => t.id === id) || {};
    const STATUS_COLORS = {
        todo:'#9ca3af', in_progress:'#2563eb', review:'#d97706', done:'#16a34a', cancelled:'#ef4444',
    };
    const PRIORITY_CLASSES = {
        critical: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        high:     'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        medium:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        low:      'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return {
        taskId:     id,
        title:      t.title      || '',
        status:     t.status     || 'todo',
        priority:   t.priority   || 'medium',
        dueDate:    t.due_date   || null,
        assignee:   t.assignee   || null,
        checklists: t.checklists || [],

        get statusColor()  { return STATUS_COLORS[this.status] ?? '#9ca3af'; },
        get priorityClass(){ return PRIORITY_CLASSES[this.priority] ?? PRIORITY_CLASSES.medium; },
        get priorityLabel(){ return this.priority.charAt(0).toUpperCase() + this.priority.slice(1); },
        get assigneeName() { return this.assignee?.name ?? ''; },
        get dueDateFmt() {
            if (!this.dueDate) return '';
            const d = new Date(this.dueDate + 'T00:00:00');
            return d.getDate() + '/' + (d.getMonth() + 1);
        },
        get isOverdue() {
            if (!this.dueDate || this.status === 'done' || this.status === 'cancelled') return false;
            return new Date(this.dueDate + 'T00:00:00') < new Date(new Date().toDateString());
        },

        onSaved(detail) {
            if (detail.id !== this.taskId) return;
            if (detail.title    !== undefined) this.title    = detail.title;
            if (detail.status   !== undefined) this.status   = detail.status;
            if (detail.priority !== undefined) this.priority = detail.priority;
            if (detail.due_date !== undefined) this.dueDate  = detail.due_date;
            if (detail.assignee !== undefined) this.assignee = detail.assignee;
        },
    };
}
</script>
@endpush
