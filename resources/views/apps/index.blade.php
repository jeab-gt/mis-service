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
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.form-templates.index') }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-forms"></i><span>Form Library</span>
            </a>
            <a href="{{ route('admin.flows.index') }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-git-branch"></i><span>Flow Library</span>
            </a>
            <a href="{{ route('admin.apps.create') }}" class="btn-primary flex items-center space-x-2">
                <i class="ti ti-plus"></i><span>{{ __('common.create') }}</span>
            </a>
        </div>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($apps as $app)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                        <i class="ti {{ $app->icon ?? 'ti-apps' }} text-2xl text-indigo-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold truncate">{{ $app->name }}</h3>
                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 rounded px-2 py-0.5">{{ $app->category }}</span>
                    </div>
                    @if(!$app->is_active)
                    <span class="text-xs bg-red-100 text-red-500 px-2 py-0.5 rounded-full flex-shrink-0">Inactive</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1.5 text-xs text-gray-500">
                    <div class="flex items-center space-x-1.5">
                        <i class="ti ti-forms text-indigo-400 flex-shrink-0"></i>
                        <span class="truncate">{{ $app->initialFormTemplate?->name ?? 'No form' }}</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <i class="ti ti-git-branch text-blue-400 flex-shrink-0"></i>
                        <span class="truncate">{{ $app->flow?->name ?? 'No flow' }}</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <i class="ti ti-send text-gray-400 flex-shrink-0"></i>
                        <span>{{ $app->submissions_count }} submissions</span>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-2">
                @can('app.edit')
                <a href="{{ route('admin.apps.edit', $app) }}"
                   class="text-xs btn-outline flex items-center space-x-1">
                    <i class="ti ti-settings"></i><span>Settings</span>
                </a>
                @endcan

                @if($app->initialFormTemplate)
                <a href="{{ route('admin.form-templates.designer', $app->initialFormTemplate) }}"
                   class="text-xs text-indigo-500 hover:text-indigo-700 flex items-center space-x-1 border border-indigo-100 rounded-lg px-2 py-1 hover:border-indigo-300">
                    <i class="ti ti-layout-grid-add"></i><span>Form</span>
                </a>
                @endif

                @if($app->flow)
                <a href="{{ route('admin.flows.designer', $app->flow) }}"
                   class="text-xs text-blue-500 hover:text-blue-700 flex items-center space-x-1 border border-blue-100 rounded-lg px-2 py-1 hover:border-blue-300">
                    <i class="ti ti-git-branch"></i><span>Flow</span>
                </a>
                @endif

                @can('app.delete')
                <form method="POST" action="{{ route('admin.apps.destroy', $app) }}"
                      onsubmit="return confirm('{{ __('common.confirm_delete') }}')" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 p-1">
                        <i class="ti ti-trash"></i>
                    </button>
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
