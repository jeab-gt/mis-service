@extends('layouts.app')
@section('title', 'Team Schedule')
@section('breadcrumb')
<a href="{{ route('tasks.index') }}" class="hover:text-indigo-600">{{ __('menu.my_tasks') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>Schedule</span>
@endsection

@section('content')
@php
    $totalDays  = $days->count();
    $startTs    = $startDate->timestamp;
    $endTs      = $endDate->copy()->endOfDay()->timestamp;
    $rangeSpan  = $endTs - $startTs;
@endphp
<div class="space-y-4">
    <!-- Header + filters -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">
            {{ app()->getLocale() === 'th' ? 'ตารางงานทีม' : 'Team Schedule' }}
            <span class="text-base font-normal text-gray-500">
                {{ $startDate->format('d M') }}–{{ $endDate->format('d M Y') }}
            </span>
        </h1>

        <form method="GET" action="{{ route('tasks.schedule') }}" class="flex flex-wrap items-center gap-2">
            <!-- View toggle -->
            <div class="flex rounded-xl overflow-hidden border border-gray-300 dark:border-gray-600">
                <a href="{{ route('tasks.schedule', array_merge(request()->except('view'), ['view'=>'week'])) }}"
                   class="px-3 py-1.5 text-sm {{ $view === 'week' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    Week
                </a>
                <a href="{{ route('tasks.schedule', array_merge(request()->except('view'), ['view'=>'month'])) }}"
                   class="px-3 py-1.5 text-sm border-l border-gray-200 dark:border-gray-600 {{ $view === 'month' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    Month
                </a>
            </div>

            @if($view === 'week')
            <input type="hidden" name="view" value="week">
            <input type="date" name="start" value="{{ $startDate->toDateString() }}" class="form-input w-auto text-sm">
            @else
            <input type="hidden" name="view" value="month">
            <select name="month" class="form-select w-auto text-sm">
                @for($m=1; $m<=12; $m++)
                <option value="{{ $m }}" {{ $m == request('month', now()->month) ? 'selected' : '' }}>{{ date('M', mktime(0,0,0,$m)) }}</option>
                @endfor
            </select>
            <input type="number" name="year" value="{{ request('year', now()->year) }}" class="form-input w-20 text-sm">
            @endif

            @if($sections->count())
            <select name="section_id" class="form-select w-auto text-sm">
                <option value="">{{ app()->getLocale() === 'th' ? 'ทุกแผนก' : 'All Sections' }}</option>
                @foreach($sections as $sec)
                <option value="{{ $sec->id }}" {{ $sectionId == $sec->id ? 'selected' : '' }}>{{ app()->getLocale() === 'th' ? $sec->name_th : ($sec->name_en ?? $sec->name_th) }}</option>
                @endforeach
            </select>
            @endif

            <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
            <a href="{{ route('tasks.index') }}" class="btn-secondary text-sm">
                <i class="ti ti-list mr-1"></i>Kanban
            </a>
        </form>
    </div>

    <!-- Legend -->
    <div class="flex items-center space-x-4 text-xs text-gray-500">
        <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span><span>&lt;30%</span></span>
        <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-sm bg-amber-400 inline-block"></span><span>30–70%</span></span>
        <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-sm bg-green-400 inline-block"></span><span>&gt;70%</span></span>
        <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-sm bg-green-600 inline-block"></span><span>100%</span></span>
        <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-sm border border-red-400 border-dashed inline-block"></span><span>Overdue</span></span>
    </div>

    <!-- Gantt Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden mis-card">
        <div class="overflow-x-auto">
            <div style="min-width: {{ max(800, 160 + $totalDays * 36) }}px">
                <!-- Day headers -->
                <div class="flex border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-750 sticky top-0 z-10">
                    <div class="w-40 flex-shrink-0 px-3 py-2 text-xs font-semibold text-gray-500 border-r border-gray-200 dark:border-gray-600">
                        {{ app()->getLocale() === 'th' ? 'ผู้รับผิดชอบ' : 'Assignee' }}
                    </div>
                    <div class="flex flex-1">
                        @foreach($days as $day)
                        <div class="flex-1 text-center px-1 py-2 border-r border-gray-200 dark:border-gray-600 last:border-r-0"
                             style="min-width: 32px">
                            <div class="text-xs font-medium {{ $day->isToday() ? 'text-indigo-600' : 'text-gray-400' }}">
                                {{ $day->format($view === 'week' ? 'D' : 'd') }}
                            </div>
                            <div class="text-xs {{ $day->isToday() ? 'text-indigo-500 font-bold' : 'text-gray-300' }}">
                                {{ $day->format($view === 'week' ? 'd' : '') }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                @forelse($byAssignee as $userId => $userAssignments)
                @php $assignee = $userAssignments->first()->assignee; @endphp
                <div class="flex border-b border-gray-200 dark:border-gray-600 last:border-b-0 hover:bg-gray-50/50 dark:hover:bg-gray-750/50">
                    <!-- Assignee name column -->
                    <div class="w-40 flex-shrink-0 px-3 py-3 border-r border-gray-200 dark:border-gray-600 flex items-start">
                        <div>
                            <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0 mb-1">
                                <span class="text-xs font-bold text-indigo-600">{{ strtoupper(substr($assignee?->name ?? '?', 0, 2)) }}</span>
                            </div>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 leading-tight">{{ $assignee?->name ?? 'Unknown' }}</p>
                            @php $roleName = $assignee?->getRoleNames()->first(); @endphp
                            @if($roleName)
                            <span class="inline-block text-xs px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 mt-0.5">{{ $roleName }}</span>
                            @endif
                            <p class="text-xs text-gray-400">{{ $assignee?->section?->name_th ?? '' }}</p>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="flex-1 relative py-2 px-1" style="min-height: {{ max(48, $userAssignments->count() * 36 + 16) }}px">
                        @php $rowIndex = 0; @endphp
                        @foreach($userAssignments as $a)
                        @php
                            $pct = $a->submission->dailyLogs->first()?->progress_pct ?? 0;
                            $aStart = max($a->assigned_at->startOfDay()->timestamp, $startTs);
                            $aEnd   = $a->due_date
                                ? min($a->due_date->endOfDay()->timestamp, $endTs)
                                : min($a->assigned_at->copy()->addDays(3)->endOfDay()->timestamp, $endTs);

                            $leftPct  = max(0, ($aStart - $startTs) / $rangeSpan * 100);
                            $widthPct = max(2, ($aEnd - $aStart) / $rangeSpan * 100);
                            $isOverdue = $a->due_date && $a->due_date->isPast()
                                && !in_array($a->submission->status, ['approved','closed','rejected']);

                            $barColor = $pct >= 100 ? 'bg-green-600'
                                : ($pct >= 70 ? 'bg-green-400'
                                : ($pct >= 30 ? 'bg-amber-400' : 'bg-red-400'));

                            $tooltipText = ($a->submission->app->name ?? '-') . ' #' . $a->submission_id
                                . ' | ' . $pct . '% | Due: ' . ($a->due_date?->format('d/m/Y') ?? '-');
                        @endphp
                        <div class="absolute flex items-center"
                             style="left: calc({{ $leftPct }}%); width: calc({{ $widthPct }}%); top: {{ 4 + $rowIndex * 34 }}px; height: 28px">
                            <a href="{{ route('submissions.show', $a->submission_id) }}"
                               title="{{ $tooltipText }}"
                               class="gantt-bar group relative w-full h-full rounded-lg flex items-center px-2 overflow-hidden shadow-sm
                                      {{ $barColor }} {{ $isOverdue ? 'ring-2 ring-red-500 ring-offset-0' : '' }}
                                      hover:brightness-110 transition-all">
                                <span class="text-white text-xs font-medium truncate">{{ $a->submission->app->name ?? '' }}</span>
                                <!-- Tooltip -->
                                <div class="gantt-tooltip absolute bottom-full left-0 mb-1 w-52 bg-gray-900 text-white text-xs rounded-lg p-2.5 shadow-xl z-20 opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity">
                                    <p class="font-semibold mb-1">{{ $a->submission->app->name ?? '-' }} #{{ $a->submission_id }}</p>
                                    <p class="text-gray-300">{{ $a->submission->title }}</p>
                                    <div class="flex justify-between mt-1.5">
                                        <span class="text-gray-400">Progress:</span>
                                        <span class="font-medium">{{ $pct }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Due:</span>
                                        <span class="{{ $isOverdue ? 'text-red-400 font-medium' : '' }}">{{ $a->due_date?->format('d/m/Y') ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Status:</span>
                                        <span>{{ $a->submission->status }}</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @php $rowIndex++; @endphp
                        @endforeach
                    </div>
                </div>
                @empty
                <div class="px-4 py-12 text-center text-gray-400">
                    <i class="ti ti-calendar-off text-4xl mb-3 block"></i>
                    <p>{{ __('common.no_data') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Today's line indicator CSS hack -->
    <style>
        .gantt-tooltip { min-width: 200px; }
    </style>
</div>
@endsection
