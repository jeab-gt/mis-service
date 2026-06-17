@extends('layouts.app')
@section('title', 'Daily Report')
@section('breadcrumb')
<a href="{{ route('reports.index') }}" class="hover:text-indigo-600">{{ __('menu.reports') }}</a>
<i class="ti ti-chevron-right text-xs"></i><span>Daily</span>
@endsection

@section('content')
<div class="space-y-4">
    @include('reports._tabs')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">
            Daily Report — <span class="text-indigo-600">{{ $date }}</span>
        </h1>
        <div class="flex items-center gap-2">
            @can('report.export')
            <a href="{{ route('reports.export', ['format'=>'excel','from'=>$date,'to'=>$date]) }}"
               class="btn-success text-sm"><i class="ti ti-table-export mr-1"></i>Excel</a>
            <a href="{{ route('reports.export', ['format'=>'pdf','from'=>$date,'to'=>$date]) }}"
               class="btn-danger text-sm"><i class="ti ti-file-type-pdf mr-1"></i>PDF</a>
            @endcan
        </div>
    </div>

    <!-- Date picker -->
    <form method="GET" class="flex items-center space-x-3">
        <a href="{{ route('reports.daily', ['date' => \Carbon\Carbon::parse($date)->subDay()->toDateString()]) }}"
           class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-500">
            <i class="ti ti-chevron-left"></i>
        </a>
        <input type="date" name="date" value="{{ $date }}" class="form-input w-auto text-sm">
        <a href="{{ route('reports.daily', ['date' => \Carbon\Carbon::parse($date)->addDay()->toDateString()]) }}"
           class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-500">
            <i class="ti ti-chevron-right"></i>
        </a>
        <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
    </form>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $newCount }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ app()->getLocale() === 'th' ? 'คำร้องใหม่' : 'New Requests' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $completedCount }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ app()->getLocale() === 'th' ? 'เสร็จสิ้น' : 'Completed' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-amber-600">{{ $pendingList->count() }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ app()->getLocale() === 'th' ? 'รอดำเนินการ' : 'Pending' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-3xl font-bold text-red-500">{{ $overdueCount }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ app()->getLocale() === 'th' ? 'เกินกำหนด' : 'Overdue' }}</p>
        </div>
    </div>

    <!-- All submissions table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold">{{ app()->getLocale() === 'th' ? 'คำร้องทั้งหมดวันนี้' : 'All Submissions Today' }}</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">App</th>
                    <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ผู้ส่ง' : 'Submitter' }}</th>
                    <th class="px-4 py-3 text-left">{{ app()->getLocale() === 'th' ? 'ผู้รับผิดชอบ' : 'Assignee' }}</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Time</th>
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
                    <td class="px-4 py-3 text-gray-500">{{ $s->created_at->format('H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pendingList->count())
    <!-- Pending requests -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-amber-100 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
            <h3 class="font-semibold text-amber-700 dark:text-amber-300">
                <i class="ti ti-clock mr-2"></i>{{ app()->getLocale() === 'th' ? 'รอดำเนินการ' : 'Still Pending' }} ({{ $pendingList->count() }})
            </h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($pendingList as $s)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('submissions.show', $s->id) }}" class="text-sm font-medium text-indigo-500 hover:underline">
                        {{ $s->app?->name ?? '-' }} #{{ $s->id }}
                    </a>
                    <p class="text-xs text-gray-400">{{ $s->submitter?->name ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <span class="status-badge status-{{ $s->status }}">{{ $s->status }}</span>
                    @if($s->latestAssignment?->assignee)
                    <p class="text-xs text-gray-400 mt-0.5">→ {{ $s->latestAssignment->assignee->name }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
