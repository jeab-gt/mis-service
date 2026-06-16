@extends('layouts.app')
@section('title', __('menu.all_requests'))

@section('breadcrumb')
<span>{{ __('menu.all_requests') }}</span>
@endsection

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ __('menu.all_requests') }}</h1>
        @can('submission.create')
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="btn-primary flex items-center space-x-2">
                <i class="ti ti-plus"></i>
                <span>{{ __('common.create') }}</span>
                <i class="ti ti-chevron-down text-xs"></i>
            </button>
            <div x-show="open" @click.away="open = false" x-transition
                 class="absolute right-0 mt-1 w-56 bg-white dark:bg-gray-700 rounded-xl shadow-lg border border-gray-200 dark:border-gray-600 z-50 py-1">
                @foreach($apps as $app)
                <a href="{{ route('submissions.create', $app) }}" class="flex items-center px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                    <i class="ti {{ $app->icon }} mr-3 text-indigo-500"></i>
                    {{ $app->name }}
                </a>
                @endforeach
            </div>
        </div>
        @endcan
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <select name="status" class="form-select">
                <option value="">{{ __('common.all_status') }}</option>
                @foreach(['draft','submitted','in_review','approved','rejected','closed'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <select name="app_id" class="form-select">
                <option value="">{{ __('common.all_apps') }}</option>
                @foreach($apps as $app)
                <option value="{{ $app->id }}" {{ request('app_id') == $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input" placeholder="From">
            <input type="date" name="to" value="{{ request('to') }}" class="form-input" placeholder="To">
        </div>
        <div class="flex space-x-2 mt-3">
            <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
            <a href="{{ route('submissions.index') }}" class="btn-secondary text-sm">{{ __('common.reset') }}</a>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold w-16">{{ app()->getLocale() === 'th' ? 'เลขที่' : '#' }}</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ app()->getLocale() === 'th' ? 'ชื่อเรื่อง' : 'Title' }}</th>
                        <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">{{ app()->getLocale() === 'th' ? 'ประเภท' : 'App' }}</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ app()->getLocale() === 'th' ? 'สถานะ' : 'Status' }}</th>
                        <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">{{ app()->getLocale() === 'th' ? 'ผู้ส่ง' : 'Submitter' }}</th>
                        <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">{{ app()->getLocale() === 'th' ? 'วันที่' : 'Date' }}</th>
                        <th class="px-4 py-3 text-left font-semibold w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($submissions as $sub)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 font-mono text-gray-400 text-xs">#{{ $sub->id }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-sm truncate max-w-xs">{{ $sub->title }}</p>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div class="flex items-center space-x-1.5 text-sm text-gray-600 dark:text-gray-300">
                                <i class="ti {{ $sub->app->icon ?? 'ti-app' }} text-indigo-400 text-xs"></i>
                                <span>{{ $sub->app->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="status-badge status-{{ $sub->status }}">
                                {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ $sub->submitter->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-400 hidden lg:table-cell">{{ $sub->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('submissions.show', $sub) }}" class="text-indigo-500 hover:text-indigo-700 flex items-center space-x-1 text-xs">
                                <i class="ti ti-eye"></i><span>{{ __('common.view') }}</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">{{ __('common.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            {{ $submissions->links() }}
        </div>
    </div>
</div>
@endsection
