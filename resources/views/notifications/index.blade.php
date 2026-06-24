@extends('layouts.app')
@section('title', app()->getLocale() === 'th' ? 'การแจ้งเตือน' : 'Notifications')
@section('breadcrumb')
<span>{{ app()->getLocale() === 'th' ? 'การแจ้งเตือน' : 'Notifications' }}</span>
@endsection
@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ app()->getLocale() === 'th' ? 'การแจ้งเตือน' : 'Notifications' }}</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn-outline text-sm flex items-center space-x-1">
                <i class="ti ti-checks"></i>
                <span>{{ app()->getLocale() === 'th' ? 'อ่านทั้งหมด' : 'Mark All Read' }}</span>
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700 mis-card">
        @forelse($notifications as $n)
        <div class="flex items-start p-4 {{ is_null($n->read_at) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                @php
                    [$icon, $color] = match($n->type) {
                        'approval_required' => ['ti-clock',         'text-blue-500'],
                        'approval_result'   => ['ti-circle-check',  'text-green-500'],
                        'assigned'          => ['ti-user-check',    'text-indigo-500'],
                        'overdue'           => ['ti-alarm',         'text-red-500'],
                        'task_done'         => ['ti-trophy',        'text-yellow-500'],
                        default             => ['ti-bell',          'text-gray-400'],
                    };
                @endphp
                <i class="ti {{ $icon }} {{ $color }}"></i>
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-medium {{ is_null($n->read_at) ? 'text-gray-800 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400' }}">
                    {{ app()->getLocale() === 'th' ? $n->title_th : $n->title_en }}
                </p>
                @if($n->body_th || $n->body_en)
                <p class="text-xs text-gray-500 mt-0.5">{{ app()->getLocale() === 'th' ? $n->body_th : $n->body_en }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
            </div>
            @if(is_null($n->read_at))
            <div class="ml-3 flex-shrink-0">
                <form method="POST" action="{{ route('notifications.read', $n) }}">
                    @csrf
                    <button type="submit" class="p-1.5 text-indigo-400 hover:text-indigo-600" title="{{ app()->getLocale() === 'th' ? 'อ่านแล้ว' : 'Mark Read' }}">
                        <i class="ti ti-check text-sm"></i>
                    </button>
                </form>
            </div>
            @else
            <div class="ml-3 w-2 h-2 rounded-full bg-gray-200 dark:bg-gray-600 flex-shrink-0 mt-2"></div>
            @endif
        </div>
        @empty
        <div class="py-16 text-center text-gray-400">
            <i class="ti ti-bell-off text-5xl block mb-3"></i>
            <p>{{ app()->getLocale() === 'th' ? 'ไม่มีการแจ้งเตือน' : 'No notifications' }}</p>
        </div>
        @endforelse
    </div>
    <div>{{ $notifications->links() }}</div>
</div>
@endsection
