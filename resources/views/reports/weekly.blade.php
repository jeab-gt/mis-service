@extends('layouts.app')
@section('title', 'Weekly Report')
@section('breadcrumb')
<a href="{{ route('reports.index') }}" class="hover:text-indigo-600">{{ __('menu.reports') }}</a>
<i class="ti ti-chevron-right text-xs"></i><span>Weekly</span>
@endsection

@section('content')
<div class="space-y-4">
    @include('reports._tabs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">
            Weekly Report
            <span class="text-base font-normal text-gray-500">{{ $start->format('d M') }} – {{ $end->format('d M Y') }}</span>
        </h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.weekly', ['start' => $start->copy()->subWeek()->toDateString()]) }}"
               class="btn-outline text-sm"><i class="ti ti-arrow-left mr-1"></i>Prev</a>
            <a href="{{ route('reports.weekly', ['start' => $start->copy()->addWeek()->toDateString()]) }}"
               class="btn-outline text-sm">Next<i class="ti ti-arrow-right ml-1"></i></a>
            @can('report.export')
            <a href="{{ route('reports.export', ['format'=>'excel','from'=>$start->toDateString(),'to'=>$end->toDateString()]) }}"
               class="btn-success text-sm"><i class="ti ti-table-export mr-1"></i>Excel</a>
            <a href="{{ route('reports.export', ['format'=>'pdf','from'=>$start->toDateString(),'to'=>$end->toDateString()]) }}"
               class="btn-danger text-sm"><i class="ti ti-file-type-pdf mr-1"></i>PDF</a>
            @endcan
        </div>
    </div>

    <!-- Week selector -->
    <form method="GET" class="flex items-center space-x-3">
        <input type="date" name="start" value="{{ $start->toDateString() }}" class="form-input w-auto text-sm">
        <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
    </form>

    <!-- Stats row -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-300 dark:border-gray-600 text-center">
            <p class="text-3xl font-bold text-indigo-600">{{ $submissions->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Submissions</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-300 dark:border-gray-600 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $submissions->whereIn('status',['approved','closed'])->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">Completed</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-300 dark:border-gray-600 text-center">
            <p class="text-3xl font-bold text-purple-600">{{ $avgHrs ? round($avgHrs, 1) : '-' }}</p>
            <p class="text-sm text-gray-500 mt-1">Avg Resolution (hrs)</p>
        </div>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Bar chart: created vs completed per day -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'คำร้องรายวัน' : 'Daily Submissions' }}</h3>
            <canvas id="weeklyChart" height="160"></canvas>
        </div>
        <!-- Line chart: avg resolution time per day -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'เวลาดำเนินการเฉลี่ย (ชม.)' : 'Avg Resolution Time (hrs)' }}</h3>
            <canvas id="resolutionChart" height="160"></canvas>
        </div>
    </div>

    <!-- Submissions table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">App</th>
                    <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ผู้ส่ง' : 'Submitter' }}</th>
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
                    <td class="px-4 py-3"><span class="status-badge status-{{ $s->status }}">{{ $s->status }}</span></td>
                    <td class="px-4 py-3 text-gray-500">{{ $s->created_at->format('D d/m H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Assignee summary table -->
    @if($assigneeStats->count())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-semibold">{{ app()->getLocale() === 'th' ? 'สรุปตามผู้รับผิดชอบ' : 'Summary by Assignee' }}</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ผู้รับผิดชอบ' : 'Assignee' }}</th>
                    <th class="px-4 py-3 text-center">{{ app()->getLocale() === 'th' ? 'งานทั้งหมด' : 'Assigned' }}</th>
                    <th class="px-4 py-3 text-center">{{ app()->getLocale() === 'th' ? 'เสร็จสิ้น' : 'Completed' }}</th>
                    <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ความคืบหน้าเฉลี่ย' : 'Avg Progress' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($assigneeStats as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="px-4 py-3 font-medium">{{ $row['assignee']?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">{{ $row['assigned'] }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="{{ $row['completed'] > 0 ? 'text-green-600 font-semibold' : 'text-gray-400' }}">{{ $row['completed'] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $row['avg_progress'] >= 70 ? 'bg-green-500' : ($row['avg_progress'] >= 30 ? 'bg-amber-400' : 'bg-red-400') }}"
                                     style="width: {{ $row['avg_progress'] }}%"></div>
                            </div>
                            <span class="text-xs font-medium w-8">{{ $row['avg_progress'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const weeklyDays  = @json($days);
const avgPerDay   = @json($avgPerDay);

new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: weeklyDays.map(d => d.date),
        datasets: [
            { label: 'Created',   data: weeklyDays.map(d => d.created),   backgroundColor: 'rgba(99,102,241,0.7)',  borderRadius: 4 },
            { label: 'Completed', data: weeklyDays.map(d => d.completed), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

new Chart(document.getElementById('resolutionChart'), {
    type: 'line',
    data: {
        labels: avgPerDay.map(d => d.date),
        datasets: [{
            label: 'Avg hrs',
            data: avgPerDay.map(d => d.avg_hrs),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139,92,246,0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#8b5cf6',
            pointRadius: 4,
            spanGaps: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Hours' } }
        }
    }
});
</script>
@endpush
