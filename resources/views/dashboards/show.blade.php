@extends('layouts.app')
@section('title', $dashboard->name)
@section('breadcrumb')
<a href="{{ route('dashboards.index') }}" class="hover:text-indigo-500">Dashboards</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>{{ $dashboard->name }}</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ $dashboard->name }}</h1>
        <div class="flex items-center space-x-2">
            <a href="{{ route('dashboards.edit', $dashboard) }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-settings"></i><span>Edit Layout</span>
            </a>
        </div>
    </div>

    @if($dashboard->widgets->isEmpty())
    <div class="text-center py-16 text-gray-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
        <i class="ti ti-layout-dashboard text-6xl block mb-4"></i>
        <p>ยังไม่มี Widget ใน Dashboard นี้</p>
        <a href="{{ route('dashboards.edit', $dashboard) }}" class="mt-4 inline-block btn-primary text-sm">
            <i class="ti ti-plus mr-1"></i>เพิ่ม Widget
        </a>
    </div>
    @else
    <div class="grid grid-cols-12 gap-4 auto-rows-auto">
        @foreach($dashboard->widgets as $widget)
        <div class="col-span-12 sm:col-span-{{ min($widget->width, 12) }}"
             x-data="{
                data: null,
                loading: true,
                error: null,
                init() {
                    fetch('/api/dashboard-widgets/{{ $widget->id }}/data', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    })
                    .then(r => r.json())
                    .then(d => { this.data = d; this.loading = false; })
                    .catch(e => { this.error = e.message; this.loading = false; });
                }
             }">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 h-full"
                 style="min-height: {{ $widget->height * 60 }}px;">
                <h3 class="font-semibold text-sm mb-3 flex items-center space-x-2">
                    @php
                        $widgetIcons = [
                            'line_chart' => 'ti-chart-line',
                            'bar_chart'  => 'ti-chart-bar',
                            'gauge'      => 'ti-gauge',
                            'heatmap'    => 'ti-grid-4x4',
                            'kpi_card'   => 'ti-brand-speedtest',
                            'data_table' => 'ti-table',
                        ];
                    @endphp
                    <i class="ti {{ $widgetIcons[$widget->widget_type] ?? 'ti-chart-bar' }} text-indigo-500"></i>
                    <span>{{ $widget->title }}</span>
                </h3>

                <div x-show="loading" class="flex items-center justify-center py-10 text-gray-400">
                    <i class="ti ti-loader-2 animate-spin text-2xl"></i>
                </div>
                <div x-show="error && !loading" class="text-red-500 text-sm text-center py-4" x-text="'Error: ' + error"></div>

                {{-- Line/Bar Chart --}}
                @if(in_array($widget->widget_type, ['line_chart', 'bar_chart']))
                {{-- Keep container always in DOM so Chart.js can measure; hide with opacity until data arrives --}}
                <div style="position:relative; height:{{ max($widget->height * 50, 200) }}px;"
                     :style="(data && !loading) ? '' : 'opacity:0; pointer-events:none;'">
                    <canvas id="chart-{{ $widget->id }}" style="max-height:100%;"></canvas>
                </div>
                @endif

                {{-- KPI Card --}}
                @if($widget->widget_type === 'kpi_card')
                <div x-show="data && !loading" class="text-center py-4">
                    <div class="text-4xl font-bold text-indigo-600 dark:text-indigo-400"
                         x-text="data && data.latest_value !== null ? parseFloat(data.latest_value).toFixed(2) : '—'"></div>
                    <div class="flex items-center justify-center space-x-4 mt-3 text-sm text-gray-500">
                        <span>Avg: <b x-text="data && data.avg !== null ? parseFloat(data.avg).toFixed(2) : '—'"></b></span>
                        <span>Min: <b x-text="data && data.min !== null ? parseFloat(data.min).toFixed(2) : '—'"></b></span>
                        <span>Max: <b x-text="data && data.max !== null ? parseFloat(data.max).toFixed(2) : '—'"></b></span>
                    </div>
                    <div class="mt-2">
                        <template x-if="data && data.total_alerts > 0">
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                                <i class="ti ti-alert-triangle mr-1"></i>
                                <span x-text="data.total_alerts + ' alerts'"></span>
                            </span>
                        </template>
                    </div>
                </div>
                @endif

                {{-- Gauge --}}
                @if($widget->widget_type === 'gauge')
                <div x-show="data && !loading" class="flex flex-col items-center py-4">
                    <svg viewBox="0 0 200 120" class="w-48">
                        <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e5e7eb" stroke-width="16" stroke-linecap="round"/>
                        <path id="gauge-fill-{{ $widget->id }}" d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#6366f1" stroke-width="16" stroke-linecap="round"
                              stroke-dasharray="0 251"/>
                        <text x="100" y="105" text-anchor="middle" class="text-2xl font-bold" font-size="24" fill="currentColor"
                              x-text="data ? parseFloat(data.value).toFixed(1) : '—'"></text>
                    </svg>
                    <div class="flex items-center justify-between w-48 text-xs text-gray-400 mt-1">
                        <span x-text="data ? data.min : ''"></span>
                        <span x-text="data ? data.max : ''"></span>
                    </div>
                </div>
                @endif

                {{-- Heatmap --}}
                @if($widget->widget_type === 'heatmap')
                <div x-show="data && !loading" class="overflow-auto">
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
                                            <div class="w-6 h-6 rounded mx-auto"
                                                 :class="{
                                                    'bg-red-400': data.cells[date] && data.cells[date][paramId] && data.cells[date][paramId].level === 'critical',
                                                    'bg-yellow-300': data.cells[date] && data.cells[date][paramId] && data.cells[date][paramId].level === 'warning',
                                                    'bg-green-300': data.cells[date] && data.cells[date][paramId] && data.cells[date][paramId].level === 'ok',
                                                    'bg-gray-100 dark:bg-gray-600': !data.cells[date] || !data.cells[date][paramId],
                                                 }">
                                            </div>
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
                <div x-show="data && !loading" class="overflow-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-2 py-1.5 text-left">วันที่</th>
                                <th class="px-2 py-1.5 text-left">Slot</th>
                                <th class="px-2 py-1.5 text-left">Factory</th>
                                <th class="px-2 py-1.5 text-center">Status</th>
                                <th class="px-2 py-1.5 text-center">Alerts</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="row in (data ? data.records : [])" :key="row.id">
                                <tr>
                                    <td class="px-2 py-1.5" x-text="row.record_date"></td>
                                    <td class="px-2 py-1.5 text-gray-500" x-text="row.time_slot || '-'"></td>
                                    <td class="px-2 py-1.5" x-text="row.factory || '-'"></td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="px-1.5 py-0.5 rounded-full text-xs"
                                              :class="{
                                                'bg-blue-100 text-blue-600': row.status === 'submitted',
                                                'bg-green-100 text-green-600': row.status === 'approved',
                                                'bg-red-100 text-red-600': row.status === 'rejected',
                                                'bg-gray-100 text-gray-500': row.status === 'draft',
                                              }"
                                              x-text="row.status"></span>
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span x-show="row.alert_count > 0" class="text-xs text-red-500 font-medium" x-text="row.alert_count"></span>
                                        <span x-show="!row.alert_count" class="text-gray-300">-</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @foreach($dashboard->widgets as $widget)
    @if(in_array($widget->widget_type, ['line_chart', 'bar_chart']))
    (function() {
        const widgetId = {{ $widget->id }};
        const canvasEl = document.getElementById('chart-' + widgetId);
        if (!canvasEl) return;

        // Alpine v3: use Alpine.$data() — comp.__x is Alpine v2 only
        const poll = setInterval(() => {
            const comp = canvasEl.closest('[x-data]');
            if (!comp) return;
            const alpineData = window.Alpine ? Alpine.$data(comp) : null;
            if (!alpineData || !alpineData.data) return;

            clearInterval(poll);
            const d = alpineData.data;
            if (!d || !d.datasets) return;

            // Unhide the canvas container first so Chart.js can measure dimensions
            const wrapper = canvasEl.closest('[id^="chart-wrap-"]');
            if (wrapper) wrapper.style.display = 'block';

            const colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
            const datasets = d.datasets.map((ds, i) => ({
                label: ds.label,
                data: d.labels.map(lbl => {
                    const found = ds.data.find(pt => pt.x === lbl);
                    return found ? found.y : null;
                }),
                borderColor: colors[i % colors.length],
                backgroundColor: colors[i % colors.length] + '33',
                tension: 0.3,
                spanGaps: true,
            }));

            // Use requestAnimationFrame so element is visible before Chart.js measures it
            requestAnimationFrame(() => {
                new Chart(canvasEl, {
                    type: '{{ $widget->widget_type }}' === 'line_chart' ? 'line' : 'bar',
                    data: { labels: d.labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: false } },
                    },
                });
            });
        }, 100);

        // Stop polling after 10s to avoid leaks
        setTimeout(() => clearInterval(poll), 10000);
    })();
    @endif
    @endforeach
});
</script>
@endpush
