@extends('layouts.app')
@section('title', $dashboard->name)
@section('breadcrumb')
<a href="{{ route('dashboards.index') }}" class="hover:text-indigo-500">Dashboards</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>{{ $dashboard->name }}</span>
@endsection

@section('content')
<div x-data="dashboardView('{{ $dashboard->slug }}')" class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $dashboard->name }}</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboards.edit', $dashboard) }}" class="btn-secondary flex items-center gap-1.5 text-sm">
                <i class="ti ti-settings"></i><span>Edit Layout</span>
            </a>
        </div>
    </div>

    {{-- Fixed exit-fullscreen overlay — top-right corner, below widget modal (z-999) --}}
    <button x-show="isFullscreen"
            @click="toggleFullscreen()"
            class="fixed top-2 right-2 z-[998] flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/50 hover:bg-black/70 text-white text-sm backdrop-blur-sm shadow-lg transition-colors"
            title="ออกจากเต็มจอ (Esc)"
            style="display:none;">
        <i class="ti ti-arrows-minimize text-sm"></i>
        <span>ออกจากเต็มจอ</span>
    </button>

    {{-- Date Range Filter Bar --}}
    @if(!$dashboard->widgets->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 px-4 py-2.5 flex flex-wrap items-center gap-3">

        {{-- Label --}}
        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider shrink-0">ช่วงเวลา</span>

        {{-- Button group + dropdown panel --}}
        {{-- ⚠ NO overflow-hidden here — it would clip the absolute dropdown panel --}}
        {{-- ⚠ Buttons written explicitly (not loop) — Tailwind JIT needs static class strings --}}
        <div class="relative" @click.outside="showPanel = false">

            {{-- 5-button segmented strip (border + -ml-px pattern, no overflow-hidden) --}}
            <div class="flex text-sm font-medium">
                <button type="button" @click="setPreset('today')"
                        :class="mode === 'today' ? 'btn-date-active relative z-10' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'"
                        class="px-4 py-1.5 border rounded-l-lg whitespace-nowrap transition-colors">
                    วันนี้
                </button>
                <button type="button" @click="setPreset('last_7_days')"
                        :class="mode === 'last_7_days' ? 'btn-date-active relative z-10' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'"
                        class="px-4 py-1.5 border -ml-px whitespace-nowrap transition-colors">
                    7 วัน
                </button>
                <button type="button" @click="setPreset('last_30_days')"
                        :class="mode === 'last_30_days' ? 'btn-date-active relative z-10' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'"
                        class="px-4 py-1.5 border -ml-px whitespace-nowrap transition-colors">
                    30 วัน
                </button>
                <button type="button" @click="setPreset('this_month')"
                        :class="mode === 'this_month' ? 'btn-date-active relative z-10' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'"
                        class="px-4 py-1.5 border -ml-px whitespace-nowrap transition-colors">
                    เดือนนี้
                </button>
                <button type="button" @click="setPreset('custom')"
                        :class="mode === 'custom' ? 'btn-date-active relative z-10' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'"
                        class="px-4 py-1.5 border -ml-px rounded-r-lg whitespace-nowrap transition-colors">
                    กำหนดเอง ▾
                </button>
            </div>

            {{-- Custom date dropdown panel — absolute, z-50, outside any overflow:hidden --}}
            <div x-show="showPanel"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-1"
                 class="absolute left-0 top-full mt-2 z-50 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-300 dark:border-gray-600 p-4"
                 style="display:none;">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">เลือกช่วงวันที่</span>
                    <button type="button" @click="showPanel = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <i class="ti ti-x text-base"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">จาก</label>
                        <input type="date" x-model="customFrom"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ถึง</label>
                        <input type="date" x-model="customTo"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                    <button type="button" @click="showPanel = false"
                            class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium">
                        ยกเลิก
                    </button>
                    <button type="button" @click="applyCustom()"
                            class="text-xs px-4 py-1.5 rounded-lg text-white font-medium transition-colors shadow-sm"
                            style="background-color: var(--color-primary);"
                            onmouseover="this.style.backgroundColor='var(--color-primary-dark)'"
                            onmouseout="this.style.backgroundColor='var(--color-primary)'">
                        Apply
                    </button>
                </div>
            </div>
        </div>

        {{-- Active range badge --}}
        <div class="ml-auto shrink-0">
            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium badge-primary">
                <i class="ti ti-calendar-event text-sm"></i>
                <span x-text="rangeLabel"></span>
            </span>
        </div>
    </div>
    @endif

    {{-- Widgets --}}
    @if($dashboard->widgets->isEmpty())
    <div class="text-center py-16 text-gray-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600">
        <i class="ti ti-layout-dashboard text-6xl block mb-4"></i>
        <p>ยังไม่มี Widget ใน Dashboard นี้</p>
        <a href="{{ route('dashboards.edit', $dashboard) }}" class="mt-4 inline-block btn-primary text-sm">
            <i class="ti ti-plus mr-1"></i>เพิ่ม Widget
        </a>
    </div>
    @else
    @php
        // Convert old grid units → pixels (same logic as edit.blade.php JS)
        $COL_W = 1160 / 12;  // ~96.7px
        $ROW_H = 70;
        $icons  = [
            'line_chart' => 'ti-chart-line',
            'bar_chart'  => 'ti-chart-bar',
            'gauge'      => 'ti-gauge',
            'heatmap'    => 'ti-grid-4x4',
            'kpi_card'   => 'ti-brand-speedtest',
            'data_table' => 'ti-table',
        ];
        $pixelWidgets = $dashboard->widgets->map(function ($w) use ($COL_W, $ROW_H) {
            $isGrid = ($w->width <= 12) && ($w->pos_x <= 11);
            if ($isGrid) {
                return [
                    'model' => $w,
                    'x'  => (int) round($w->pos_x * $COL_W),
                    'y'  => (int) round($w->pos_y * $ROW_H),
                    'pw' => (int) round($w->width  * $COL_W),
                    'ph' => (int) max(150, round($w->height * $ROW_H)),
                ];
            }
            return [
                'model' => $w,
                'x'  => (int) $w->pos_x,
                'y'  => (int) $w->pos_y,
                'pw' => (int) $w->width,
                'ph' => (int) $w->height,
            ];
        });
        $canvasH = $pixelWidgets->max(fn($p) => $p['y'] + $p['ph'] + 20);
        $canvasH = max($canvasH, 400);
        $canvasW = $pixelWidgets->max(fn($p) => $p['x'] + $p['pw'] + 20);
        $canvasW = max($canvasW, 800);
    @endphp

    {{-- Canvas — outer clips to scaled visual height, inner is fixed logical size --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-3">
        <div id="view-canvas-outer" style="overflow:hidden; width:100%; height:{{ $canvasH }}px;">
        <div id="view-canvas-inner" style="position:relative; width:{{ $canvasW }}px; height:{{ $canvasH }}px; transform-origin:top left;">
            @foreach($pixelWidgets as $pw)
            @php $widget = $pw['model']; @endphp
            <div x-data="widgetComponent({{ $widget->id }}, '{{ $widget->widget_type }}', {!! htmlspecialchars(json_encode($widget->title), ENT_QUOTES, 'UTF-8') !!})"
                 class="bg-white dark:bg-gray-800 rounded-xl border border-gray-300 dark:border-gray-600 shadow-sm overflow-hidden"
                 style="position:absolute; left:{{ $pw['x'] }}px; top:{{ $pw['y'] }}px; width:{{ $pw['pw'] }}px; height:{{ $pw['ph'] }}px;">

                <div class="flex flex-col h-full p-3">
                    <h3 class="font-semibold text-sm mb-2 flex items-center gap-2 flex-shrink-0">
                        <i class="ti {{ $icons[$widget->widget_type] ?? 'ti-chart-bar' }} text-primary flex-shrink-0"></i>
                        <span class="truncate flex-1">{{ $widget->title }}</span>
                        <span x-show="loading" class="flex-shrink-0">
                            <i class="ti ti-loader-2 animate-spin text-gray-400 text-base"></i>
                        </span>
                        <button x-show="!loading && data && !isEmpty"
                                @click="$dispatch('fullscreen-request', { id: widgetId, type: widgetType, title: widgetTitle, data: data })"
                                class="flex-shrink-0 p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                title="เปิดเต็มจอ">
                            <i class="ti ti-arrows-maximize text-sm"></i>
                        </button>
                    </h3>

                    <div class="flex-1 min-h-0 relative">
                        {{-- Error --}}
                        <div x-show="error && !loading" class="text-red-500 text-xs text-center py-4" x-text="'เกิดข้อผิดพลาด: ' + error"></div>
                        {{-- Empty state --}}
                        <div x-show="!loading && !error && isEmpty" class="text-center py-4 text-gray-400">
                            <i class="ti ti-database-off text-2xl block mb-1"></i>
                            <p class="text-xs">ไม่มีข้อมูล</p>
                        </div>

                        {{-- Line / Bar Chart --}}
                        @if(in_array($widget->widget_type, ['line_chart', 'bar_chart']))
                        <div class="absolute inset-0" :style="(data && !loading && !isEmpty) ? '' : 'opacity:0; pointer-events:none;'">
                            <canvas id="chart-{{ $widget->id }}" style="width:100%; height:100%;"></canvas>
                        </div>
                        @endif

                        {{-- KPI Card --}}
                        @if($widget->widget_type === 'kpi_card')
                        <div x-show="data && !loading && !isEmpty" class="text-center py-3">
                            <div class="text-3xl font-bold" style="color: var(--color-primary)"
                                 x-text="data?.latest_value != null ? parseFloat(data.latest_value).toFixed(2) : '—'"></div>
                            <div class="flex items-center justify-center gap-3 mt-2 text-xs text-gray-500">
                                <span>Avg: <b x-text="data?.avg != null ? parseFloat(data.avg).toFixed(2) : '—'"></b></span>
                                <span>Min: <b x-text="data?.min != null ? parseFloat(data.min).toFixed(2) : '—'"></b></span>
                                <span>Max: <b x-text="data?.max != null ? parseFloat(data.max).toFixed(2) : '—'"></b></span>
                            </div>
                        </div>
                        @endif

                        {{-- Gauge --}}
                        @if($widget->widget_type === 'gauge')
                        <div x-show="data && !loading && !isEmpty" class="flex flex-col items-center py-2">
                            <svg viewBox="0 0 200 120" class="w-40">
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e5e7eb" stroke-width="16" stroke-linecap="round"/>
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke-width="16" stroke-linecap="round" stroke-dasharray="0 251" style="stroke: var(--color-primary)"/>
                                <text x="100" y="105" text-anchor="middle" font-size="24" fill="currentColor"
                                      x-text="data ? parseFloat(data.value).toFixed(1) : '—'"></text>
                            </svg>
                        </div>
                        @endif

                        {{-- Heatmap --}}
                        @if($widget->widget_type === 'heatmap')
                        <div x-show="data && !loading && !isEmpty" class="overflow-auto h-full">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr>
                                        <th class="py-1 pr-2 text-left text-gray-500 font-normal">Parameter</th>
                                        <template x-for="date in (data ? data.dates : [])" :key="date">
                                            <th class="py-1 px-1 text-center text-gray-500 font-normal whitespace-nowrap" x-text="date.slice(5)"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(paramName, paramId) in (data ? data.parameters : {})" :key="paramId">
                                        <tr>
                                            <td class="py-1 pr-2 font-medium whitespace-nowrap" x-text="paramName"></td>
                                            <template x-for="date in (data ? data.dates : [])" :key="date">
                                                <td class="py-1 px-1 text-center">
                                                    <div class="w-5 h-5 rounded mx-auto"
                                                         :class="{
                                                            'bg-red-400':    data.cells[date]?.[paramId]?.level === 'critical',
                                                            'bg-yellow-300': data.cells[date]?.[paramId]?.level === 'warning',
                                                            'bg-green-300':  data.cells[date]?.[paramId]?.level === 'ok',
                                                            'bg-gray-100 dark:bg-gray-600': !data.cells[date]?.[paramId],
                                                         }"></div>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Data Table --}}
                        @if($widget->widget_type === 'data_table')
                        <div x-show="data && !isEmpty" class="absolute inset-0 flex flex-col">
                            {{-- Loading overlay: shows during page/sort change while old data stays visible --}}
                            <div x-show="loading"
                                 class="absolute inset-0 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center z-20 rounded-lg">
                                <i class="ti ti-loader-2 animate-spin text-indigo-400 text-lg"></i>
                            </div>

                            {{-- Scrollable table area --}}
                            <div class="flex-1 overflow-auto min-h-0">
                                <table class="w-full text-xs border-collapse">
                                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                        <tr>
                                            {{-- วันที่ — sortable --}}
                                            <th @click="toggleSort('record_date')"
                                                class="px-2 py-1.5 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 select-none">
                                                <span class="inline-flex items-center gap-1">วันที่
                                                    <i class="ti text-xs"
                                                       :class="sortBy==='record_date'
                                                           ? (sortDir==='asc' ? 'ti-arrow-up text-primary' : 'ti-arrow-down text-primary')
                                                           : 'ti-arrows-sort text-gray-300 dark:text-gray-600'"></i>
                                                </span>
                                            </th>
                                            <th class="px-2 py-1.5 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Slot</th>
                                            <template x-for="col in (data ? data.columns : [])" :key="col.id">
                                                <th class="px-2 py-1.5 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                    <span x-text="col.name"></span>
                                                    <template x-if="col.unit">
                                                        <span class="text-gray-400 font-normal ml-0.5" x-text="'(' + col.unit + ')'"></span>
                                                    </template>
                                                </th>
                                            </template>
                                            {{-- Status — sortable --}}
                                            <th @click="toggleSort('status')"
                                                class="px-2 py-1.5 text-center font-medium text-gray-600 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 select-none">
                                                <span class="inline-flex items-center justify-center gap-1">Status
                                                    <i class="ti text-xs"
                                                       :class="sortBy==='status'
                                                           ? (sortDir==='asc' ? 'ti-arrow-up text-primary' : 'ti-arrow-down text-primary')
                                                           : 'ti-arrows-sort text-gray-300 dark:text-gray-600'"></i>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <template x-for="row in (data ? data.records : [])" :key="row.id">
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-2 py-1.5 font-medium whitespace-nowrap" x-text="row.record_date"></td>
                                                <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap" x-text="row.time_slot || '-'"></td>
                                                <template x-for="col in (data ? data.columns : [])" :key="col.id">
                                                    <td class="px-2 py-1.5 text-right">
                                                        <template x-if="row.values && row.values[col.id] != null">
                                                            <span class="inline-block px-1.5 py-0.5 rounded font-mono"
                                                                  :class="{
                                                                      'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400':
                                                                          row.values[col.id].is_alert && row.values[col.id].alert_level === 'critical',
                                                                      'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400':
                                                                          row.values[col.id].is_alert && row.values[col.id].alert_level === 'warning',
                                                                      'text-gray-700 dark:text-gray-300': !row.values[col.id].is_alert,
                                                                  }"
                                                                  x-text="row.values[col.id].value ?? '—'"></span>
                                                        </template>
                                                        <template x-if="!row.values || row.values[col.id] == null">
                                                            <span class="text-gray-300">—</span>
                                                        </template>
                                                    </td>
                                                </template>
                                                <td class="px-2 py-1.5 text-center whitespace-nowrap">
                                                    <span class="px-1.5 py-0.5 rounded-full text-xs"
                                                          :class="{
                                                            'bg-blue-100 text-blue-600':   row.status === 'submitted',
                                                            'bg-green-100 text-green-600': row.status === 'approved',
                                                            'bg-red-100 text-red-600':     row.status === 'rejected',
                                                            'bg-gray-100 text-gray-500':   row.status === 'draft',
                                                          }"
                                                          x-text="row.status"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination controls --}}
                            <div x-show="totalPages > 1"
                                 class="flex-shrink-0 flex items-center justify-between px-2 py-1.5 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800">
                                <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                    <span x-text="tableFrom"></span>–<span x-text="tableTo"></span>
                                    / <span x-text="totalRows"></span>
                                </span>
                                <div class="flex items-center gap-0.5">
                                    <button @click="fetchTablePage(1)" :disabled="currentPage <= 1"
                                            class="w-6 h-6 flex items-center justify-center rounded text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">«</button>
                                    <button @click="fetchTablePage(currentPage - 1)" :disabled="currentPage <= 1"
                                            class="w-6 h-6 flex items-center justify-center rounded text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">‹</button>
                                    <template x-for="(p, i) in pageRange" :key="i">
                                        <button @click="typeof p === 'number' && fetchTablePage(p)"
                                                :disabled="p === '…'"
                                                :class="p === currentPage
                                                    ? 'page-active'
                                                    : p === '…'
                                                        ? 'cursor-default text-gray-400 dark:text-gray-600'
                                                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                class="min-w-[24px] h-6 px-1 flex items-center justify-center rounded text-xs transition-colors"
                                                x-text="p">
                                        </button>
                                    </template>
                                    <button @click="fetchTablePage(currentPage + 1)" :disabled="currentPage >= totalPages"
                                            class="w-6 h-6 flex items-center justify-center rounded text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">›</button>
                                    <button @click="fetchTablePage(totalPages)" :disabled="currentPage >= totalPages"
                                            class="w-6 h-6 flex items-center justify-center rounded text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">»</button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>{{-- /view-canvas-inner --}}
        </div>{{-- /view-canvas-outer --}}
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════
     Fullscreen Modal — lives OUTSIDE the scaled canvas so that
     position:fixed is relative to the viewport, not any transform.
     ═══════════════════════════════════════════════════════════ --}}
<div x-data="fullscreenModal()"
     @fullscreen-request.window="open($event.detail)"
     @keydown.escape.window="if(isOpen) close()"
     x-show="isOpen"
     x-cloak
     style="display:none;"
     class="fixed inset-0 z-[999] bg-white dark:bg-gray-900 flex flex-col">

    {{-- Header bar --}}
    <div class="flex items-center h-14 px-6 border-b border-gray-200 dark:border-gray-600 flex-shrink-0">
        <i class="ti ti-arrows-maximize mr-3 text-primary text-lg flex-shrink-0"></i>
        <h2 class="font-semibold text-lg flex-1 truncate" x-text="widgetTitle"></h2>
        <button @click="close()"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ml-4"
                title="ปิด (Esc)">
            <i class="ti ti-x text-xl"></i>
        </button>
    </div>

    {{-- Content --}}
    <div class="flex-1 min-h-0 p-6 overflow-auto">

        {{-- Line / Bar Chart --}}
        <div x-show="widgetType === 'line_chart' || widgetType === 'bar_chart'"
             class="w-full h-full" style="min-height:400px;">
            <canvas id="chart-fullscreen" style="width:100%;height:100%;"></canvas>
        </div>

        {{-- KPI Card --}}
        <div x-show="widgetType === 'kpi_card'"
             class="flex items-center justify-center h-full" style="min-height:300px;">
            <div class="text-center">
                <div class="text-7xl font-bold mb-6" style="color:var(--color-primary)"
                     x-text="data?.latest_value != null ? parseFloat(data.latest_value).toFixed(2) : '—'"></div>
                <div class="flex items-center justify-center gap-12">
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Avg</div>
                        <div class="text-2xl font-semibold text-gray-700 dark:text-gray-200"
                             x-text="data?.avg != null ? parseFloat(data.avg).toFixed(2) : '—'"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Min</div>
                        <div class="text-2xl font-semibold text-gray-700 dark:text-gray-200"
                             x-text="data?.min != null ? parseFloat(data.min).toFixed(2) : '—'"></div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Max</div>
                        <div class="text-2xl font-semibold text-gray-700 dark:text-gray-200"
                             x-text="data?.max != null ? parseFloat(data.max).toFixed(2) : '—'"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gauge --}}
        <div x-show="widgetType === 'gauge'"
             class="flex items-center justify-center h-full" style="min-height:300px;">
            <svg viewBox="0 0 200 120" class="w-72">
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e5e7eb" stroke-width="16" stroke-linecap="round"/>
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke-width="16" stroke-linecap="round"
                      stroke-dasharray="0 251" style="stroke: var(--color-primary)"></path>
                <text x="100" y="105" text-anchor="middle" font-size="24" fill="currentColor"
                      x-text="data ? parseFloat(data.value).toFixed(1) : '—'"></text>
            </svg>
        </div>

        {{-- Heatmap --}}
        <div x-show="widgetType === 'heatmap'" class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th class="py-2 pr-4 text-left text-gray-500 font-normal">Parameter</th>
                        <template x-for="date in (data ? data.dates : [])" :key="date">
                            <th class="py-2 px-2 text-center text-gray-500 font-normal whitespace-nowrap" x-text="date.slice(5)"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(paramName, paramId) in (data ? data.parameters : {})" :key="paramId">
                        <tr>
                            <td class="py-2 pr-4 font-medium whitespace-nowrap" x-text="paramName"></td>
                            <template x-for="date in (data ? data.dates : [])" :key="date">
                                <td class="py-2 px-2 text-center">
                                    <div class="w-7 h-7 rounded mx-auto"
                                         :class="{
                                             'bg-red-400':    data.cells[date]?.[paramId]?.level === 'critical',
                                             'bg-yellow-300': data.cells[date]?.[paramId]?.level === 'warning',
                                             'bg-green-300':  data.cells[date]?.[paramId]?.level === 'ok',
                                             'bg-gray-100 dark:bg-gray-600': !data.cells[date]?.[paramId],
                                         }"></div>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Data Table --}}
        <div x-show="widgetType === 'data_table'" class="overflow-auto h-full">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">วันที่</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Slot</th>
                        <template x-for="col in (data ? data.columns : [])" :key="col.id">
                            <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <span x-text="col.name"></span>
                                <template x-if="col.unit">
                                    <span class="text-gray-400 font-normal ml-1" x-text="'(' + col.unit + ')'"></span>
                                </template>
                            </th>
                        </template>
                        <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="row in (data ? data.records : [])" :key="row.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-3 py-2 font-medium whitespace-nowrap" x-text="row.record_date"></td>
                            <td class="px-3 py-2 text-gray-500 whitespace-nowrap" x-text="row.time_slot || '-'"></td>
                            <template x-for="col in (data ? data.columns : [])" :key="col.id">
                                <td class="px-3 py-2 text-right">
                                    <template x-if="row.values && row.values[col.id] != null">
                                        <span class="inline-block px-2 py-0.5 rounded font-mono"
                                              :class="{
                                                  'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400':
                                                      row.values[col.id].is_alert && row.values[col.id].alert_level === 'critical',
                                                  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400':
                                                      row.values[col.id].is_alert && row.values[col.id].alert_level === 'warning',
                                                  'text-gray-700 dark:text-gray-300': !row.values[col.id].is_alert,
                                              }"
                                              x-text="row.values[col.id].value ?? '—'"></span>
                                    </template>
                                    <template x-if="!row.values || row.values[col.id] == null">
                                        <span class="text-gray-300">—</span>
                                    </template>
                                </td>
                            </template>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs"
                                      :class="{
                                          'bg-blue-100 text-blue-600':   row.status === 'submitted',
                                          'bg-green-100 text-green-600': row.status === 'approved',
                                          'bg-red-100 text-red-600':     row.status === 'rejected',
                                          'bg-gray-100 text-gray-500':   row.status === 'draft',
                                      }"
                                      x-text="row.status"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// Scale view canvas to fit container width (no horizontal scroll)
(function () {
    const CANVAS_W = {{ $canvasW ?? 1160 }};
    const CANVAS_H = {{ $canvasH ?? 400 }};

    function applyViewScale() {
        const outer = document.getElementById('view-canvas-outer');
        const inner = document.getElementById('view-canvas-inner');
        if (!outer || !inner) return;
        const avail = outer.clientWidth;
        if (avail <= 0) return;                       // not laid out yet
        // In fullscreen the canvas should fill the available width exactly (scale can exceed 1).
        // In normal view cap at 1 so widgets are never shown larger than their design size.
        const scale = document.fullscreenElement
            ? avail / CANVAS_W
            : Math.min(1, avail / CANVAS_W);
        inner.style.transform = 'scale(' + scale + ')';
        outer.style.height    = Math.ceil(CANVAS_H * scale) + 'px';
    }

    // Double RAF ensures sidebar is at its persisted localStorage width before we measure.
    // (matches edit.blade.php's requestAnimationFrame pattern)
    document.addEventListener('DOMContentLoaded', () =>
        requestAnimationFrame(() => requestAnimationFrame(applyViewScale))
    );
    // app.blade.php dispatches 'resize' 350ms after fullscreen change (post-sidebar transition)
    window.addEventListener('resize', applyViewScale);
})();

// Initialise shared date store before Alpine boots components
document.addEventListener('alpine:init', () => {
    const KEY   = 'dash-date-{{ $dashboard->slug }}';
    const saved = (() => { try { return JSON.parse(localStorage.getItem(KEY)); } catch(e) { return null; } })();
    const iso   = d => d.toISOString().slice(0, 10);
    const today = new Date();

    Alpine.store('dashDate', {
        dateFrom: saved?.dateFrom || iso(new Date(today - 29 * 86400000)),
        dateTo:   saved?.dateTo   || iso(today),
    });
});

// ─── Dashboard-level date range controller ───────────────────────────────────
function dashboardView(slug) {
    const KEY   = 'dash-date-' + slug;
    const iso   = d => d.toISOString().slice(0, 10);

    function persist(from, to, mode) {
        try { localStorage.setItem(KEY, JSON.stringify({ dateFrom: from, dateTo: to, mode })); } catch(e) {}
    }

    return {
        slug,
        mode:       null,
        showPanel:  false,
        customFrom: '',
        customTo:   '',

        init() {
            const saved     = (() => { try { return JSON.parse(localStorage.getItem(KEY)); } catch(e) { return null; } })();
            const today     = new Date();
            this.mode       = saved?.mode || 'last_30_days';
            this.customFrom = saved?.dateFrom || iso(new Date(today - 29 * 86400000));
            this.customTo   = saved?.dateTo   || iso(today);
        },

        setPreset(mode) {
            this.mode = mode;
            if (mode === 'custom') { this.showPanel = !this.showPanel; return; }
            this.showPanel = false;
            const today = new Date();
            let from, to = iso(today);
            switch (mode) {
                case 'today':        from = iso(today); break;
                case 'last_7_days':  from = iso(new Date(today - 6  * 86400000)); break;
                case 'last_30_days': from = iso(new Date(today - 29 * 86400000)); break;
                case 'this_month':   from = iso(new Date(today.getFullYear(), today.getMonth(), 1)); break;
                default:             from = iso(new Date(today - 29 * 86400000));
            }
            this._dispatch(from, to);
        },

        applyCustom() {
            if (!this.customFrom || !this.customTo) return;
            this.showPanel = false;
            this._dispatch(this.customFrom, this.customTo);
        },

        _dispatch(from, to) {
            Alpine.store('dashDate').dateFrom = from;
            Alpine.store('dashDate').dateTo   = to;
            persist(from, to, this.mode);
            window.dispatchEvent(new CustomEvent('dashboard-date-change', {
                detail: { dateFrom: from, dateTo: to },
            }));
        },

        get rangeLabel() {
            const s   = Alpine.store('dashDate');
            const fmt = d => new Date(d + 'T00:00:00').toLocaleDateString('th-TH', {
                day: 'numeric', month: 'short', year: 'numeric',
            });
            return `${fmt(s.dateFrom)} – ${fmt(s.dateTo)}`;
        },
    };
}

// ─── Fullscreen modal ─────────────────────────────────────────────────────────
function fullscreenModal() {
    return {
        isOpen:      false,
        widgetId:    null,
        widgetType:  null,
        widgetTitle: null,
        data:        null,
        _chart:      null,

        open(detail) {
            this.widgetId    = detail.id;
            this.widgetType  = detail.type;
            this.widgetTitle = detail.title;
            this.data        = detail.data;
            this.isOpen      = true;
            document.body.style.overflow = 'hidden';
            if (['line_chart', 'bar_chart'].includes(this.widgetType)) {
                this.$nextTick(() => this._renderChart());
            }
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
            if (this._chart) { this._chart.destroy(); this._chart = null; }
        },

        _renderChart() {
            if (typeof Chart === 'undefined') return;
            const canvas = document.getElementById('chart-fullscreen');
            if (!canvas || !this.data) return;
            if (this._chart) { this._chart.destroy(); this._chart = null; }
            const d      = this.data;
            const colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
            const datasets = d.datasets.map((ds, i) => ({
                label:           ds.label,
                data:            d.labels.map(lbl => { const pt = ds.data.find(p => p.x === lbl); return pt ? pt.y : null; }),
                borderColor:     colors[i % colors.length],
                backgroundColor: colors[i % colors.length] + '33',
                tension:         0.3,
                spanGaps:        true,
                pointRadius:     3,
            }));
            if (d.show_spec_lines && d.spec && d.datasets.length === 1) {
                const base = { type: 'line', pointRadius: 0, borderWidth: 1.5, fill: false, spanGaps: true };
                const flat = v => Array(d.labels.length).fill(v);
                if (d.spec.max    != null) datasets.push({ ...base, label: 'Max Spec', data: flat(d.spec.max),    borderColor: 'rgba(239,68,68,0.75)',  borderDash: [6,4] });
                if (d.spec.min    != null) datasets.push({ ...base, label: 'Min Spec', data: flat(d.spec.min),    borderColor: 'rgba(245,158,11,0.75)', borderDash: [6,4] });
                if (d.spec.target != null) datasets.push({ ...base, label: 'Target',   data: flat(d.spec.target), borderColor: 'rgba(34,197,94,0.85)',  borderDash: [3,3] });
            }
            this._chart = new Chart(canvas, {
                type: this.widgetType === 'line_chart' ? 'line' : 'bar',
                data: { labels: d.labels, datasets },
                options: {
                    responsive:          true,
                    maintainAspectRatio: false,
                    animation:           { duration: 300 },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                filter: item => !['Max Spec','Min Spec','Target'].includes(item.text)
                                                || (d.show_spec_lines && d.datasets.length === 1),
                            },
                        },
                    },
                    scales: { y: { beginAtZero: false } },
                },
            });
        },
    };
}

// ─── Per-widget Alpine component ─────────────────────────────────────────────
function widgetComponent(widgetId, widgetType, widgetTitle) {
    return {
        widgetId,
        widgetType,
        widgetTitle,
        data:        null,
        loading:     true,
        error:       null,
        // Data table — pagination & sorting state
        currentPage: 1,
        totalPages:  1,
        totalRows:   0,
        perPage:     10,
        sortBy:      'record_date',
        sortDir:     'desc',
        tableFrom:   0,
        tableTo:     0,

        init() {
            const store = Alpine.store('dashDate');
            this.fetchData(store.dateFrom, store.dateTo);

            // Re-render chart reactively when data changes
            if (['line_chart', 'bar_chart'].includes(widgetType)) {
                this.$watch('data', d => {
                    if (d?.datasets && d.labels?.length > 0) {
                        this.$nextTick(() => this._renderChart(d));
                    }
                });
            }

            window.addEventListener('dashboard-date-change', (e) => {
                this.currentPage = 1;
                this.fetchData(e.detail.dateFrom, e.detail.dateTo);
            });
        },

        fetchData(dateFrom, dateTo, keepData = false) {
            this.loading = true;
            if (!keepData) this.data = null;
            const params = {};
            if (dateFrom && dateTo) { params.date_from = dateFrom; params.date_to = dateTo; }
            if (widgetType === 'data_table') {
                params.page     = this.currentPage;
                params.per_page = this.perPage;
                params.sort_by  = this.sortBy;
                params.sort_dir = this.sortDir;
            }
            fetch('/api/dashboard-widgets/' + widgetId + '/data?' + new URLSearchParams(params), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                credentials: 'same-origin',
            })
            .then(r => r.json())
            .then(d => {
                this.data    = d;
                this.loading = false;
                if (d.type === 'data_table') {
                    this.currentPage = d.current_page ?? 1;
                    this.totalPages  = d.last_page    ?? 1;
                    this.totalRows   = d.total        ?? 0;
                    this.tableFrom   = d.from         ?? 0;
                    this.tableTo     = d.to           ?? 0;
                }
            })
            .catch(e => { this.error = e.message; this.loading = false; });
        },

        fetchTablePage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            const s = Alpine.store('dashDate');
            this.fetchData(s.dateFrom, s.dateTo, true);
        },

        toggleSort(column) {
            if (this.sortBy === column) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy  = column;
                this.sortDir = 'asc';
            }
            this.currentPage = 1;
            const s = Alpine.store('dashDate');
            this.fetchData(s.dateFrom, s.dateTo, true);
        },

        get pageRange() {
            const pages = [];
            const total = this.totalPages;
            const cur   = this.currentPage;
            if (total <= 7) {
                for (let i = 1; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                if (cur > 3) pages.push('…');
                for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
                if (cur < total - 2) pages.push('…');
                pages.push(total);
            }
            return pages;
        },

        get isEmpty() {
            if (!this.data || this.loading) return false;
            if (Array.isArray(this.data.labels))  return this.data.labels.length  === 0;
            if (Array.isArray(this.data.records)) return this.data.records.length === 0;
            if (Array.isArray(this.data.dates))   return this.data.dates.length   === 0;
            if (this.data.type === 'kpi_card')    return this.data.avg == null;
            if (this.data.type === 'gauge')       return this.data.value == null;
            return false;
        },

        _renderChart(d) {
            if (typeof Chart === 'undefined') return;
            const canvas = document.getElementById('chart-' + widgetId);
            if (!canvas) return;

            // Destroy previous instance before re-creating
            const existing = Chart.getChart ? Chart.getChart(canvas) : null;
            if (existing) existing.destroy();

            const colors   = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
            const datasets = d.datasets.map((ds, i) => ({
                label:           ds.label,
                data:            d.labels.map(lbl => { const pt = ds.data.find(p => p.x === lbl); return pt ? pt.y : null; }),
                borderColor:     colors[i % colors.length],
                backgroundColor: colors[i % colors.length] + '33',
                tension:         0.3,
                spanGaps:        true,
                pointRadius:     3,
            }));

            if (d.show_spec_lines && d.spec && d.datasets.length === 1) {
                const base = { type: 'line', pointRadius: 0, borderWidth: 1.5, fill: false, spanGaps: true };
                const flat = v => Array(d.labels.length).fill(v);
                if (d.spec.max    != null) datasets.push({ ...base, label: 'Max Spec', data: flat(d.spec.max),    borderColor: 'rgba(239,68,68,0.75)',  borderDash: [6,4] });
                if (d.spec.min    != null) datasets.push({ ...base, label: 'Min Spec', data: flat(d.spec.min),    borderColor: 'rgba(245,158,11,0.75)', borderDash: [6,4] });
                if (d.spec.target != null) datasets.push({ ...base, label: 'Target',   data: flat(d.spec.target), borderColor: 'rgba(34,197,94,0.85)',  borderDash: [3,3] });
            }

            new Chart(canvas, {
                type: widgetType === 'line_chart' ? 'line' : 'bar',
                data: { labels: d.labels, datasets },
                options: {
                    responsive:          true,
                    maintainAspectRatio: false,
                    animation:           { duration: 300 },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                filter: item => !['Max Spec','Min Spec','Target'].includes(item.text)
                                                || (d.show_spec_lines && d.datasets.length === 1),
                            },
                        },
                    },
                    scales: { y: { beginAtZero: false } },
                },
            });
        },
    };
}
</script>
@endpush
