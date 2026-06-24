@extends('layouts.app')
@section('title', __('menu.reports'))
@section('breadcrumb')
<span>{{ __('menu.reports') }}</span>
@endsection

@section('content')
<div class="space-y-5">
    <!-- Header + Nav + Export -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">{{ __('menu.reports') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('reports.daily') }}" class="btn-outline text-sm">
                <i class="ti ti-calendar-day mr-1"></i>Daily
            </a>
            <a href="{{ route('reports.weekly') }}" class="btn-outline text-sm">
                <i class="ti ti-calendar-week mr-1"></i>Weekly
            </a>
            <a href="{{ route('reports.monthly') }}" class="btn-outline text-sm">
                <i class="ti ti-calendar-month mr-1"></i>Monthly
            </a>
            @can('report.export')
            <a href="{{ route('reports.export', ['format'=>'excel','from'=>$from,'to'=>$to]) }}"
               class="btn-success text-sm flex items-center space-x-1">
                <i class="ti ti-table-export"></i><span>Excel</span>
            </a>
            <a href="{{ route('reports.export', ['format'=>'pdf','from'=>$from,'to'=>$to]) }}"
               class="btn-danger text-sm flex items-center space-x-1">
                <i class="ti ti-file-type-pdf"></i><span>PDF</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Date Filter -->
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-300 dark:border-gray-600 flex flex-wrap items-center gap-3">
        <label class="text-sm text-gray-500">{{ app()->getLocale() === 'th' ? 'ช่วงวันที่' : 'Date Range' }}:</label>
        <input type="date" name="from" value="{{ $from }}" class="form-input w-auto text-sm">
        <span class="text-gray-400">—</span>
        <input type="date" name="to" value="{{ $to }}" class="form-input w-auto text-sm">
        <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
        <span class="text-xs text-gray-400">{{ $from }} → {{ $to }}</span>
    </form>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label_th' => 'คำร้องทั้งหมด', 'label_en' => 'Total Requests', 'value' => $totalNew, 'icon' => 'ti-apps', 'color' => 'indigo'],
            ['label_th' => 'เสร็จสิ้น', 'label_en' => 'Completed', 'value' => $totalCompleted, 'icon' => 'ti-circle-check', 'color' => 'green'],
            ['label_th' => 'รอดำเนินการ', 'label_en' => 'Pending', 'value' => $totalPending, 'icon' => 'ti-clock', 'color' => 'amber'],
            ['label_th' => 'เวลาเฉลี่ย (ชม.)', 'label_en' => 'Avg Resolution (hrs)', 'value' => $avgResolution ? round($avgResolution, 1) : '-', 'icon' => 'ti-timer', 'color' => 'purple'],
        ] as $kpi)
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-{{ $kpi['color'] }}-100 dark:bg-{{ $kpi['color'] }}-900/40 flex items-center justify-center flex-shrink-0">
                <i class="ti {{ $kpi['icon'] }} text-xl text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400"></i>
            </div>
            <div>
                <p class="text-2xl font-bold">{{ $kpi['value'] }}</p>
                <p class="text-xs text-gray-500">{{ app()->getLocale() === 'th' ? $kpi['label_th'] : $kpi['label_en'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- By Status -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'สรุปตามสถานะ' : 'By Status' }}</h3>
            @php $totalSum = $summary->sum() ?: 1; @endphp
            <div class="space-y-3">
                @foreach([
                    ['key'=>'submitted',  'label'=>'Submitted',  'cls'=>'bg-blue-500'],
                    ['key'=>'in_review',  'label'=>'In Review',  'cls'=>'bg-amber-500'],
                    ['key'=>'approved',   'label'=>'Approved',   'cls'=>'bg-green-500'],
                    ['key'=>'rejected',   'label'=>'Rejected',   'cls'=>'bg-red-500'],
                    ['key'=>'closed',     'label'=>'Closed',     'cls'=>'bg-purple-500'],
                    ['key'=>'returned',   'label'=>'Returned',   'cls'=>'bg-orange-500'],
                ] as $s)
                @php $cnt = $summary[$s['key']] ?? 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">{{ $s['label'] }}</span>
                        <span class="font-semibold">{{ $cnt }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                        <div class="{{ $s['cls'] }} h-2 rounded-full transition-all" style="width:{{ round($cnt/$totalSum*100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- By App (Chart) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'สรุปตาม App' : 'By App Type' }}</h3>
            <canvas id="byAppChart" height="200"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const byAppData = @json($byApp);
const colors = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
new Chart(document.getElementById('byAppChart'), {
    type: 'doughnut',
    data: {
        labels: byAppData.map(d => d.app?.name ?? 'Unknown'),
        datasets: [{
            data: byAppData.map(d => d.total),
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right', labels: { font: { size: 11 }, padding: 12 } },
        },
    }
});
</script>
@endpush
