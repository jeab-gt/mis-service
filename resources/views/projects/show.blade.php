@extends('layouts.app')
@section('title', $project->name)
@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500">Projects</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>{{ $project->name }}</span>
@endsection

@section('content')
<div x-data="projectShow()" class="space-y-4">

    {{-- Project Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-3">
                <div class="w-3 h-3 rounded-full mt-2 flex-shrink-0" style="background-color: {{ $project->color }}"></div>
                <div>
                    <h1 class="text-xl font-bold">{{ $project->name }}</h1>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                     bg-{{ $project->status_badge_color }}-100 text-{{ $project->status_badge_color }}-700
                                     dark:bg-{{ $project->status_badge_color }}-900/30 dark:text-{{ $project->status_badge_color }}-400">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                     bg-{{ $project->priority_badge_color }}-100 text-{{ $project->priority_badge_color }}-700
                                     dark:bg-{{ $project->priority_badge_color }}-900/30 dark:text-{{ $project->priority_badge_color }}-400">
                            {{ ucfirst($project->priority) }} Priority
                        </span>
                        @if($project->is_cross_factory)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                            <i class="ti ti-building-factory-2 mr-1"></i> Cross-Factory
                        </span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $project->factory?->name_th }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.edit', $project) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                    <i class="ti ti-edit"></i> Edit
                </a>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mt-4">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>Overall Progress</span>
                <span class="font-semibold">{{ $project->progress_pct }}%</span>
            </div>
            <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $project->progress_pct }}%; background-color: {{ $project->color }}"></div>
            </div>
        </div>

        {{-- Dates --}}
        @if($project->start_date || $project->end_date)
        <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
            @if($project->start_date)<span><i class="ti ti-calendar-event mr-1"></i>Start: {{ $project->start_date->format('d M Y') }}</span>@endif
            @if($project->end_date)<span><i class="ti ti-calendar-due mr-1"></i>End: {{ $project->end_date->format('d M Y') }}</span>@endif
        </div>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
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
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-3 shadow-sm cursor-pointer hover:shadow-md transition-shadow task-card"
                             data-task-id="{{ $task->id }}"
                             @click="!isDragging && openDrawer({{ $task->id }})">
                            <p class="text-sm font-medium leading-snug line-clamp-2">{{ $task->title }}</p>

                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs px-1.5 py-0.5 rounded-md
                                             bg-{{ $task->priority_badge_color }}-100 text-{{ $task->priority_badge_color }}-600
                                             dark:bg-{{ $task->priority_badge_color }}-900/30 dark:text-{{ $task->priority_badge_color }}-400">
                                    {{ ucfirst($task->priority) }}
                                </span>
                                @if($task->due_date)
                                <span class="text-xs {{ $task->isOverdue() ? 'text-red-500' : 'text-gray-400' }}">
                                    {{ $task->due_date->format('d/m') }}
                                </span>
                                @endif
                            </div>

                            @if($task->checklists->count())
                            <div class="flex items-center gap-1 mt-2 text-xs text-gray-400">
                                <i class="ti ti-checkbox"></i>
                                {{ $task->checklists->where('is_completed', true)->count() }}/{{ $task->checklists->count() }}
                            </div>
                            @endif

                            @if($task->assignee)
                            <div class="flex items-center gap-1 mt-2">
                                <div class="w-5 h-5 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0"
                                     style="background-color: var(--color-primary)"
                                     title="{{ $task->assignee->name }}">
                                    {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                                </div>
                                <span class="text-xs text-gray-400 truncate">{{ $task->assignee->name }}</span>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ═══ TAB: GANTT ═══ --}}
        <div x-show="activeTab === 'gantt'" x-data="ganttChart()" class="p-0">

            {{-- Controls --}}
            <div class="flex items-center gap-2 p-3 border-b border-gray-100 dark:border-gray-700 flex-wrap">
                <div class="flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                    <template x-for="mode in ['Day','Week','Month']" :key="mode">
                        <button @click="setView(mode)"
                                :class="viewMode === mode ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 text-xs font-medium border-r border-gray-200 dark:border-gray-600 last:border-r-0 transition-colors"
                                x-text="mode"></button>
                    </template>
                </div>
                <button @click="scrollToToday()"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="ti ti-calendar-event mr-1"></i>Today
                </button>
                <span class="text-xs text-gray-400 ml-auto" x-show="ganttRows.length"
                      x-text="`${ganttRows.filter(r=>!r.isMilestone).length} tasks`"></span>
            </div>

            {{-- Empty state --}}
            <div x-show="!ganttRows.length" class="py-16 text-center text-gray-400">
                <i class="ti ti-chart-gantt text-4xl mb-3 block"></i>
                <p class="text-sm">No tasks with date ranges to display</p>
            </div>

            {{-- Gantt grid --}}
            <div x-show="ganttRows.length"
                 class="gantt-scroll overflow-x-auto"
                 style="max-height:520px; overflow-y:auto">
                <div :style="`min-width:${250+timelineWidth}px`">

                    {{-- Header row --}}
                    <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80"
                         style="position:sticky;top:0;z-index:30;height:36px">
                        <div class="flex items-center px-3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80"
                             style="position:sticky;left:0;z-index:40;width:250px;min-width:250px">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Task / Phase</span>
                        </div>
                        <div class="relative" :style="`width:${timelineWidth}px`">
                            <template x-for="col in ganttColumns" :key="col.key">
                                <div class="absolute top-0 bottom-0 flex items-center justify-center text-xs text-gray-500 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700/50 select-none"
                                     :style="`left:${col.left}px;width:${col.width}px`"
                                     x-text="col.label"></div>
                            </template>
                            <div x-show="todayLeft>=0"
                                 class="absolute top-0 bottom-0 bg-red-400"
                                 style="width:2px;z-index:20"
                                 :style="`left:${todayLeft}px`"></div>
                        </div>
                    </div>

                    {{-- Task/milestone rows --}}
                    <template x-for="row in ganttRows" :key="row.id">
                        <div class="flex border-b border-gray-100 dark:border-gray-700/50"
                             :style="`height:${row.isMilestone?30:44}px`">

                            {{-- Name cell (sticky left) --}}
                            <div class="flex items-center px-3 border-r border-gray-200 dark:border-gray-700 overflow-hidden"
                                 :class="row.isMilestone
                                     ? 'bg-blue-50 dark:bg-blue-900/25'
                                     : 'bg-white dark:bg-gray-800'"
                                 style="position:sticky;left:0;z-index:20;width:250px;min-width:250px">
                                <template x-if="row.isMilestone">
                                    <div class="flex items-center gap-1.5 w-full">
                                        <i class="ti ti-flag-3 text-blue-500 text-sm flex-shrink-0"></i>
                                        <span class="text-xs font-bold text-blue-700 dark:text-blue-300 truncate" x-text="row.name"></span>
                                    </div>
                                </template>
                                <template x-if="!row.isMilestone">
                                    <div class="flex items-center gap-1.5 w-full cursor-pointer" @click="openDrawer(row.taskId)">
                                        <div class="w-2 h-2 rounded-full flex-shrink-0" :class="dotColor(row.status)"></div>
                                        <span class="text-xs text-gray-700 dark:text-gray-300 truncate" x-text="row.name"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Timeline cell --}}
                            <div class="relative"
                                 :class="row.isMilestone ? 'bg-blue-50/40 dark:bg-blue-900/10' : 'bg-white dark:bg-gray-800'"
                                 :style="`width:${timelineWidth}px`">

                                {{-- Grid lines --}}
                                <template x-for="col in ganttColumns" :key="col.key">
                                    <div class="absolute top-0 bottom-0 border-r border-gray-100 dark:border-gray-700/30"
                                         :style="`left:${col.left+col.width-1}px;width:1px`"></div>
                                </template>

                                {{-- Today line --}}
                                <div x-show="todayLeft>=0"
                                     class="absolute top-0 bottom-0 bg-red-400/50"
                                     style="width:1px;z-index:10"
                                     :style="`left:${todayLeft}px`"></div>

                                {{-- Task bar --}}
                                <template x-if="!row.isMilestone && row.barLeft!==null">
                                    <div class="absolute rounded-md cursor-pointer flex items-center overflow-hidden group select-none"
                                         :style="`left:${row.barLeft}px;width:${Math.max(row.barWidth,12)}px;top:7px;bottom:7px`"
                                         :class="barColor(row.status)"
                                         @click="openDrawer(row.taskId)"
                                         @mousedown.prevent="startDrag($event,row)">
                                        <div class="absolute top-0 left-0 bottom-0 bg-black/20 rounded-l-md pointer-events-none"
                                             :style="`width:${row.progress_pct}%`"></div>
                                        <span x-show="row.barWidth>50"
                                              class="relative z-10 text-white text-xs px-1.5 truncate leading-none pointer-events-none"
                                              x-text="row.name"></span>
                                        <div class="absolute right-0 top-0 bottom-0 w-3 cursor-ew-resize flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                             @mousedown.stop.prevent="startResize($event,row)">
                                            <div class="w-0.5 h-4 bg-white/70 rounded"></div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Milestone diamond --}}
                                <template x-if="row.isMilestone && row.dueLeft!==null">
                                    <div class="absolute w-3 h-3 rotate-45 bg-blue-500 border-2 border-white dark:border-gray-800"
                                         style="top:50%;transform:translateY(-50%) rotate(45deg);z-index:10"
                                         :style="`left:${row.dueLeft-6}px`"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

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
            if (!this.drawerTask) return;
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
                    title: this.drawerTask.title, description: this.drawerTask.description,
                    status: this.drawerTask.status, priority: this.drawerTask.priority,
                    assignee_id: this.drawerTask.assignee_id,
                    start_date: this.drawerTask.start_date, due_date: this.drawerTask.due_date,
                    estimated_hours: this.drawerTask.estimated_hours,
                    progress_pct: this.drawerTask.progress_pct,
                });
                this.savedFlash = true;
                setTimeout(() => { this.savedFlash = false; }, 1500);
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
        viewMode:     'Week',
        pixelsPerDay: 18,
        ganttRows:    [],
        ganttColumns: [],
        minDate:      null,
        todayLeft:    0,
        totalDays:    0,

        get timelineWidth() { return this.totalDays * this.pixelsPerDay; },

        init() { this.build(); this.$nextTick(() => this.scrollToToday()); },

        setView(mode) { this.viewMode = mode; this.build(); this.$nextTick(() => this.scrollToToday()); },

        scrollToToday() {
            const el = this.$el.querySelector('.gantt-scroll');
            if (el) el.scrollLeft = Math.max(0, this.todayLeft - 200);
        },

        openDrawer(taskId) { this.$dispatch('open-drawer', { taskId }); },

        build() {
            const ppd = { Day: 40, Week: 18, Month: 6 }[this.viewMode] ?? 18;
            this.pixelsPerDay = ppd;

            const withDates = TASK_DATA.filter(t => t.start_date && t.due_date);
            if (!withDates.length) { this.ganttRows = []; this.ganttColumns = []; return; }

            const allD = withDates.flatMap(t => [new Date(t.start_date + 'T00:00:00'), new Date(t.due_date + 'T00:00:00')]);
            const minD = new Date(Math.min(...allD.map(d => d.getTime())));
            const maxD = new Date(Math.max(...allD.map(d => d.getTime())));
            minD.setDate(minD.getDate() - 5);
            maxD.setDate(maxD.getDate() + 14);
            this.minDate   = minD;
            this.totalDays = Math.ceil((maxD - minD) / 86400000) + 1;

            const today = new Date(); today.setHours(0,0,0,0);
            this.todayLeft = Math.floor((today - minD) / 86400000) * ppd;

            this.buildColumns(minD, maxD, ppd);

            const rows = [];
            const used = new Set();

            (MILESTONE_DATA || []).forEach(ms => {
                const dl = ms.due_date
                    ? Math.floor((new Date(ms.due_date + 'T00:00:00') - minD) / 86400000) * ppd
                    : null;
                rows.push({ id: 'ms-' + ms.id, name: ms.name, isMilestone: true, dueLeft: dl });
                withDates.filter(t => t.milestone_id === ms.id).forEach(t => {
                    used.add(t.id);
                    rows.push(this.makeRow(t, minD, ppd));
                });
            });

            const free = withDates.filter(t => !t.milestone_id);
            if (free.length) {
                rows.push({ id: 'ms-none', name: 'No Milestone', isMilestone: true, dueLeft: null });
                free.forEach(t => rows.push(this.makeRow(t, minD, ppd)));
            }

            this.ganttRows = rows;
        },

        makeRow(t, minD, ppd) {
            const s = new Date(t.start_date + 'T00:00:00');
            const e = new Date(t.due_date   + 'T00:00:00');
            return {
                id: 'task-' + t.id, taskId: t.id, name: t.title, isMilestone: false,
                status:       t.status,
                barLeft:      Math.floor((s - minD) / 86400000) * ppd,
                barWidth:     Math.max(Math.ceil((e - s) / 86400000) * ppd, ppd),
                progress_pct: t.progress_pct ?? 0,
                dueLeft:      null,
            };
        },

        buildColumns(minD, maxD, ppd) {
            const cols = [];
            if (this.viewMode === 'Day') {
                let d = new Date(minD);
                while (d <= maxD) {
                    cols.push({ key: d.toISOString().slice(0,10), label: d.getDate() + '/' + (d.getMonth()+1), left: Math.floor((d - minD) / 86400000) * ppd, width: ppd });
                    d = new Date(d); d.setDate(d.getDate() + 1);
                }
            } else if (this.viewMode === 'Week') {
                let d = new Date(minD);
                const dow = d.getDay(); d.setDate(d.getDate() - (dow === 0 ? 6 : dow - 1));
                while (d <= maxD) {
                    const left = Math.floor((d - minD) / 86400000) * ppd;
                    const wn   = this.weekNum(d);
                    const label = 'W' + wn + ' ' + d.toLocaleDateString('en', {day:'2-digit', month:'short'});
                    cols.push({ key: d.toISOString().slice(0,10), label, left: Math.max(0, left), width: 7 * ppd });
                    d = new Date(d); d.setDate(d.getDate() + 7);
                }
            } else {
                let d = new Date(minD.getFullYear(), minD.getMonth(), 1);
                while (d <= maxD) {
                    const days = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
                    cols.push({ key: d.toISOString().slice(0,10), label: d.toLocaleString('en', {month:'short', year:'2-digit'}), left: Math.max(0, Math.floor((d - minD) / 86400000) * ppd), width: days * ppd });
                    d = new Date(d.getFullYear(), d.getMonth() + 1, 1);
                }
            }
            this.ganttColumns = cols;
        },

        weekNum(date) {
            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
            return Math.ceil(((d - new Date(Date.UTC(d.getUTCFullYear(), 0, 1))) / 86400000 + 1) / 7);
        },

        barColor(s) {
            return { todo:'bg-gray-400 dark:bg-gray-500', in_progress:'bg-blue-500', review:'bg-amber-400', done:'bg-green-500', cancelled:'bg-red-400' }[s] ?? 'bg-gray-400';
        },

        dotColor(s) {
            return { todo:'bg-gray-400', in_progress:'bg-blue-500', review:'bg-amber-400', done:'bg-green-500', cancelled:'bg-red-400' }[s] ?? 'bg-gray-400';
        },

        startDrag(e, row) {
            if (e.button !== 0) return;
            const startX = e.clientX, origLeft = row.barLeft;
            const onMove = e => { row.barLeft = Math.max(0, origLeft + (e.clientX - startX)); };
            const onUp   = async () => {
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup',   onUp);
                const newS = new Date(this.minDate.getTime() + (row.barLeft / this.pixelsPerDay) * 86400000);
                const newE = new Date(this.minDate.getTime() + ((row.barLeft + row.barWidth) / this.pixelsPerDay) * 86400000);
                const ss = newS.toISOString().slice(0,10), ee = newE.toISOString().slice(0,10);
                await fetch(`/project-tasks/${row.taskId}`, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body: JSON.stringify({ start_date: ss, due_date: ee }) });
                const t = TASK_DATA.find(t => t.id === row.taskId);
                if (t) { t.start_date = ss; t.due_date = ee; }
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup',   onUp);
        },

        startResize(e, row) {
            if (e.button !== 0) return;
            const startX = e.clientX, origW = row.barWidth;
            const onMove = e => { row.barWidth = Math.max(this.pixelsPerDay, origW + (e.clientX - startX)); };
            const onUp   = async () => {
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup',   onUp);
                const newE = new Date(this.minDate.getTime() + ((row.barLeft + row.barWidth) / this.pixelsPerDay) * 86400000);
                const ee = newE.toISOString().slice(0,10);
                await fetch(`/project-tasks/${row.taskId}`, { method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body: JSON.stringify({ due_date: ee }) });
                const t = TASK_DATA.find(t => t.id === row.taskId);
                if (t) t.due_date = ee;
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup',   onUp);
        },
    };
}
</script>
@endpush
