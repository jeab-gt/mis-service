@extends('layouts.app')
@section('title', 'Monthly Report')
@section('breadcrumb')
<a href="{{ route('reports.index') }}" class="hover:text-indigo-600">{{ __('menu.reports') }}</a>
<i class="ti ti-chevron-right text-xs"></i><span>Monthly</span>
@endsection

@section('content')
@php $monthName = date('F', mktime(0,0,0,$month)); @endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">
            Monthly Report — <span class="text-indigo-600">{{ $monthName }} {{ $year }}</span>
        </h1>
        <div class="flex items-center gap-2">
            @php
                $prevMonth = $month == 1 ? 12 : $month - 1;
                $prevYear  = $month == 1 ? $year - 1 : $year;
                $nextMonth = $month == 12 ? 1 : $month + 1;
                $nextYear  = $month == 12 ? $year + 1 : $year;
            @endphp
            <a href="{{ route('reports.monthly', ['month'=>$prevMonth,'year'=>$prevYear]) }}"
               class="btn-outline text-sm"><i class="ti ti-arrow-left mr-1"></i>Prev</a>
            <a href="{{ route('reports.monthly', ['month'=>$nextMonth,'year'=>$nextYear]) }}"
               class="btn-outline text-sm">Next<i class="ti ti-arrow-right ml-1"></i></a>
            @can('report.export')
            @php
                $mStart = \Carbon\Carbon::createFromDate($year,$month,1)->toDateString();
                $mEnd   = \Carbon\Carbon::createFromDate($year,$month,1)->endOfMonth()->toDateString();
            @endphp
            <a href="{{ route('reports.export', ['format'=>'excel','from'=>$mStart,'to'=>$mEnd]) }}"
               class="btn-success text-sm"><i class="ti ti-table-export mr-1"></i>Excel</a>
            <a href="{{ route('reports.export', ['format'=>'pdf','from'=>$mStart,'to'=>$mEnd]) }}"
               class="btn-danger text-sm"><i class="ti ti-file-type-pdf mr-1"></i>PDF</a>
            @endcan
        </div>
    </div>

    <!-- Month selector -->
    <form method="GET" class="flex items-center space-x-3">
        <select name="month" class="form-select w-auto text-sm">
            @for($m=1;$m<=12;$m++)
            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m)) }}</option>
            @endfor
        </select>
        <input type="number" name="year" value="{{ $year }}" class="form-input w-24 text-sm">
        <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
    </form>

    <!-- Stats row -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-indigo-600">{{ $submissions->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Submissions</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $submissions->whereIn('status',['approved','closed'])->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">Completed</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-amber-600">{{ $submissions->whereNotIn('status',['approved','rejected','closed'])->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Weekly trend chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'แนวโน้มรายสัปดาห์' : 'Weekly Trend' }}</h3>
            <canvas id="monthlyChart" height="120"></canvas>
        </div>

        <!-- By App pie chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'ตาม App' : 'By App' }}</h3>
            <canvas id="byAppPie" height="200"></canvas>
        </div>
    </div>

    <!-- Top 5 Assignees -->
    @if($topAssignees->count())
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <h3 class="font-semibold mb-4">
            <i class="ti ti-trophy text-amber-500 mr-2"></i>
            {{ app()->getLocale() === 'th' ? 'Top 5 ผู้ดำเนินการ' : 'Top 5 Assignees by Completed Tasks' }}
        </h3>
        <div class="space-y-3">
            @foreach($topAssignees as $i => $row)
            <div class="flex items-center space-x-3">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $i === 0 ? 'bg-amber-100 text-amber-600' : ($i === 1 ? 'bg-gray-100 text-gray-500' : 'bg-orange-100 text-orange-600') }}">
                    {{ $i + 1 }}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium">{{ $row->assignee?->name ?? '-' }}</p>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mt-1">
                        <div class="bg-indigo-500 h-1.5 rounded-full"
                             style="width: {{ round($row->completed / max($topAssignees->max('completed'),1) * 100) }}%"></div>
                    </div>
                </div>
                <span class="text-sm font-bold text-indigo-600">{{ $row->completed }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Submissions table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold">{{ app()->getLocale() === 'th' ? 'รายการทั้งหมด' : 'All Submissions' }}</h3>
            <span class="text-sm text-gray-500">{{ $submissions->count() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">App</th>
                        <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ผู้ส่ง' : 'Submitter' }}</th>
                        <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ผู้รับผิดชอบ' : 'Assignee' }}</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($submissions as $s)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 font-mono">
                            <a href="{{ route('submissions.show', $s->id) }}" class="text-indigo-500 hover:underline">{{ $s->id }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $s->app?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $s->submitter?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $s->latestAssignment?->assignee?->name ?? '-' }}</td>
                        <td class="px-4 py-3"><span class="status-badge status-{{ $s->status }}">{{ $s->status }}</span></td>
                        <td class="px-4 py-3 text-gray-500">{{ $s->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">{{ __('common.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const weekData = @json($weeks);
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: weekData.map(d => d.label),
        datasets: [
            { label: 'Created',   data: weekData.map(d => d.created),   backgroundColor: 'rgba(99,102,241,0.7)',  borderRadius: 4 },
            { label: 'Completed', data: weekData.map(d => d.completed), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 4 },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

const appData = @json($byApp);
new Chart(document.getElementById('byAppPie'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(appData),
        datasets: [{
            data: Object.values(appData),
            backgroundColor: ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8 } } } }
});
</script>
@endpush
