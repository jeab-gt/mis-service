@extends('layouts.app')
@section('title', 'Checksheet Templates')
@section('breadcrumb')
<span>Admin</span>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>Checksheet Templates</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Checksheet Templates</h1>
        <a href="{{ route('admin.checksheets.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>สร้าง Template</span>
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($templates as $template)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-5 flex flex-col space-y-3">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold truncate">{{ $template->name }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ $template->description ?? '-' }}</p>
                </div>
                @if(!$template->is_active)
                <span class="text-xs bg-red-100 text-red-500 px-2 py-0.5 rounded-full flex-shrink-0 ml-2">Inactive</span>
                @else
                <span class="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full flex-shrink-0 ml-2">Active</span>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                @php
                    $freqColors = [
                        'realtime' => 'bg-purple-100 text-purple-600',
                        'hourly'   => 'bg-blue-100 text-blue-600',
                        'daily'    => 'bg-indigo-100 text-indigo-600',
                        'weekly'   => 'bg-cyan-100 text-cyan-600',
                        'monthly'  => 'bg-teal-100 text-teal-600',
                    ];
                @endphp
                <span class="text-xs px-2 py-0.5 rounded-full {{ $freqColors[$template->frequency] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $template->frequency }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <i class="ti ti-sliders mr-1"></i>{{ $template->parameters_count }} params
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <i class="ti ti-file-description mr-1"></i>{{ $template->records_count }} records
                </span>
            </div>

            <div class="text-xs text-gray-400">
                <i class="ti ti-user mr-1"></i>{{ $template->creator?->name ?? '-' }}
                &nbsp;·&nbsp;{{ $template->created_at->format('d/m/Y') }}
            </div>

            <div class="flex items-center space-x-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                <a href="{{ route('admin.checksheets.edit', $template) }}"
                   class="flex-1 text-center text-xs btn-outline flex items-center justify-center space-x-1">
                    <i class="ti ti-settings"></i><span>Edit</span>
                </a>
                <a href="{{ route('admin.checksheets.builder', $template) }}"
                   class="flex-1 text-center text-xs text-indigo-500 hover:text-indigo-700 px-2 py-1.5 rounded-lg border border-indigo-200 hover:border-indigo-400 flex items-center justify-center space-x-1">
                    <i class="ti ti-layout-grid-add"></i><span>Builder</span>
                </a>
                <form method="POST" action="{{ route('admin.checksheets.destroy', $template) }}"
                      onsubmit="return confirm('ลบ Template นี้?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 px-2 py-1.5 rounded border border-red-100 hover:border-red-300">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-400">
            <i class="ti ti-clipboard-list text-6xl mb-4 block"></i>
            <p>ยังไม่มี Checksheet Template</p>
            <a href="{{ route('admin.checksheets.create') }}" class="mt-4 inline-block btn-primary text-sm">
                <i class="ti ti-plus mr-1"></i>สร้าง Template แรก
            </a>
        </div>
        @endforelse
    </div>

    <div>{{ $templates->links() }}</div>
</div>
@endsection
