@extends('layouts.app')
@section('title', __('menu.masters'))

@section('breadcrumb')
<span>{{ __('menu.masters') }}</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ __('menu.masters') }}</h1>
        @can('master.create')
        <a href="{{ route('admin.masters.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>{{ __('common.create') }}</span>
        </a>
        @endcan
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6"
         x-data="{ tree: {} }">
        @forelse($roots as $root)
        <div x-data="{ open: true }" class="mb-2">
            @include('master._node', ['node' => $root, 'level' => 0])
        </div>
        @empty
        <p class="text-gray-400 text-center py-8">{{ __('common.no_data') }}</p>
        @endforelse
    </div>
</div>
@endsection
