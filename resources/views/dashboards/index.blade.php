@extends('layouts.app')
@section('title', 'Dashboards')
@section('breadcrumb')
<span>Dashboards</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Dashboards</h1>
        <a href="{{ route('dashboards.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>สร้าง Dashboard</span>
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($dashboards as $dashboard)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-layout-dashboard text-2xl text-indigo-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold truncate">{{ $dashboard->name }}</h3>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 rounded px-2 py-0.5">
                                {{ $dashboard->widgets_count }} widgets
                            </span>
                            @if($dashboard->is_public)
                            <span class="text-xs bg-green-100 text-green-600 rounded px-2 py-0.5">Public</span>
                            @else
                            <span class="text-xs bg-gray-100 text-gray-500 rounded px-2 py-0.5">Private</span>
                            @endif
                            <span class="text-xs bg-blue-100 text-blue-600 rounded px-2 py-0.5">{{ $dashboard->factory_scope }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-600 flex gap-2">
                <a href="{{ route('dashboards.show', $dashboard) }}"
                   class="flex-1 text-center text-xs btn-primary flex items-center justify-center space-x-1">
                    <i class="ti ti-eye"></i><span>View</span>
                </a>
                <a href="{{ route('dashboards.edit', $dashboard) }}"
                   class="flex-1 text-center text-xs btn-outline flex items-center justify-center space-x-1">
                    <i class="ti ti-settings"></i><span>Edit</span>
                </a>
                <form method="POST" action="{{ route('dashboards.destroy', $dashboard) }}"
                      onsubmit="return confirm('ลบ Dashboard นี้?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 p-1">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-400">
            <i class="ti ti-layout-dashboard text-6xl mb-4 block"></i>
            <p>ยังไม่มี Dashboard</p>
            <a href="{{ route('dashboards.create') }}" class="mt-4 inline-block btn-primary text-sm">
                <i class="ti ti-plus mr-1"></i>สร้าง Dashboard แรก
            </a>
        </div>
        @endforelse
    </div>

    <div>{{ $dashboards->links() }}</div>
</div>
@endsection
