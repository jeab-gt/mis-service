@extends('layouts.app')
@section('title', __('menu.dashboard'))

@section('breadcrumb')
<span class="text-gray-800 dark:text-gray-200 font-medium">{{ __('menu.dashboard') }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'th' ? 'เปิดอยู่' : 'Open' }}</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['open'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <i class="ti ti-inbox text-2xl text-blue-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'th' ? 'กำลังดำเนินการ' : 'In Progress' }}</p>
                    <p class="text-3xl font-bold text-yellow-500 mt-1">{{ $stats['in_review'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center">
                    <i class="ti ti-loader text-2xl text-yellow-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'th' ? 'เกินกำหนด' : 'Overdue' }}</p>
                    <p class="text-3xl font-bold text-red-500 mt-1">{{ $stats['overdue'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <i class="ti ti-clock-exclamation text-2xl text-red-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'th' ? 'ปิดแล้ว (เดือนนี้)' : 'Closed (this month)' }}</p>
                    <p class="text-3xl font-bold text-green-500 mt-1">{{ $stats['closed'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                    <i class="ti ti-circle-check text-2xl text-green-500"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Daily submissions chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">
                {{ app()->getLocale() === 'th' ? 'คำร้อง 7 วันล่าสุด' : 'Submissions (Last 7 Days)' }}
            </h3>
            <canvas id="dailyChart" height="100"></canvas>
        </div>
        <!-- Status doughnut -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">
                {{ app()->getLocale() === 'th' ? 'สถานะคำร้อง' : 'By Status' }}
            </h3>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>

    <!-- My Tasks -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-300 dark:border-gray-600 mis-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">{{ __('menu.my_tasks') }}</h3>
            <a href="{{ route('tasks.index') }}" class="text-sm text-indigo-600 hover:underline">{{ __('common.view_all') }}</a>
        </div>
        @forelse($myTasks as $assignment)
        <div class="flex items-center py-3 border-b border-gray-200 dark:border-gray-600 last:border-0">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-file-text text-indigo-500"></i>
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ $assignment->submission->app->name ?? '-' }} #{{ $assignment->submission_id }}</p>
                @if($assignment->due_date)
                    @php $isOverdue = $assignment->due_date->isPast() @endphp
                    <p class="text-xs {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                        @if($isOverdue)<i class="ti ti-alert-triangle text-xs mr-0.5"></i>@endif
                        Due: {{ $assignment->due_date->format('d/m/Y') }}
                    </p>
                @else
                    <p class="text-xs text-gray-400">{{ app()->getLocale() === 'th' ? 'ไม่มีกำหนด' : 'No due date' }}</p>
                @endif
            </div>
            <div class="ml-3 flex items-center space-x-2">
                <div class="w-24">
                    @php $pct = $assignment->submission->dailyLogs->first()?->progress_pct ?? 0 @endphp
                    <div class="flex justify-between text-xs mb-1">
                        <span class="{{ $pct >= 100 ? 'text-green-600' : 'text-gray-500' }}">{{ $pct }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="{{ $pct >= 100 ? 'bg-green-500' : ($pct >= 70 ? 'bg-indigo-500' : ($pct >= 30 ? 'bg-amber-400' : 'bg-red-400')) }} h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                <a href="{{ route('submissions.show', $assignment->submission_id) }}" class="text-indigo-500 hover:text-indigo-700">
                    <i class="ti ti-arrow-right"></i>
                </a>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-sm py-4 text-center">{{ app()->getLocale() === 'th' ? 'ไม่มีงานที่ได้รับมอบหมาย' : 'No tasks assigned' }}</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
const dailyData  = window.dailyData  = @json($daily);
const statusData = window.statusData = @json($byStatus);

// Daily bar chart
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Submissions',
            data: dailyData.map(d => d.count),
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Status doughnut
const statusLabels = Object.keys(statusData);
const statusColors = { draft:'#6b7280',submitted:'#3b82f6',in_review:'#f59e0b',approved:'#10b981',rejected:'#ef4444',closed:'#8b5cf6' };
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusLabels.map(k => statusData[k]),
            backgroundColor: statusLabels.map(k => statusColors[k] || '#6b7280'),
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
@endpush
