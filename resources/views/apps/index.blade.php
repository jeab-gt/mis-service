@extends('layouts.app')
@section('title', __('menu.app_builder'))
@section('breadcrumb')
<span>{{ __('menu.app_builder') }}</span>
@endsection
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ __('menu.app_builder') }}</h1>
        @can('app.create')
        <a href="{{ route('admin.apps.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>{{ __('common.create') }}</span>
        </a>
        @endcan
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($apps as $app)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                        <i class="ti {{ $app->icon }} text-2xl text-indigo-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold truncate">{{ $app->name }}</h3>
                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 rounded px-2 py-0.5">{{ $app->category }}</span>
                    </div>
                    @if(!$app->is_active)
                    <span class="text-xs bg-red-100 text-red-500 px-2 py-0.5 rounded-full flex-shrink-0">Inactive</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-3 line-clamp-2">{{ $app->description ?? 'No description' }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $app->submissions_count }} submissions</p>
            </div>
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-2">
                <a href="{{ route('admin.apps.designer', $app) }}" class="text-xs btn-outline flex items-center space-x-1">
                    <i class="ti ti-layout-grid-add"></i><span>{{ app()->getLocale() === 'th' ? 'ออกแบบฟอร์ม' : 'Form' }}</span>
                </a>
                <a href="{{ route('admin.apps.flow', $app) }}" class="text-xs btn-outline flex items-center space-x-1">
                    <i class="ti ti-git-branch"></i><span>Flow</span>
                </a>
                @can('app.edit')
                <a href="{{ route('admin.apps.edit', $app) }}" class="text-xs text-blue-500 hover:text-blue-700 flex items-center space-x-1">
                    <i class="ti ti-edit"></i>
                </a>
                @endcan
                @can('app.delete')
                <form method="POST" action="{{ route('admin.apps.destroy', $app) }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600"><i class="ti ti-trash"></i></button>
                </form>
                @endcan
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-400">
            <i class="ti ti-apps text-6xl mb-4 block"></i>
            <p>{{ __('common.no_data') }}</p>
        </div>
        @endforelse
    </div>
    <div>{{ $apps->links() }}</div>
</div>
@endsection
