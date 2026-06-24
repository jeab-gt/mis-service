@extends('layouts.app')

@section('title', __('Reports') . ' — ' . $project->name)

@section('content')
<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.show', $project) }}"
               class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ti ti-arrow-left text-xl"></i>
            </a>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project->name }}</p>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ app()->getLocale() === 'th' ? 'รายงานโปรเจกต์' : 'Project Reports' }}
                </h1>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('projects.reports.templates', $project) }}"
               class="flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="ti ti-layout-grid"></i>
                {{ app()->getLocale() === 'th' ? 'เทมเพลต' : 'Templates' }}
            </a>
            <a href="{{ route('projects.reports.create', $project) }}"
               class="flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                <i class="ti ti-plus"></i>
                {{ app()->getLocale() === 'th' ? 'สร้างรายงาน' : 'New Report' }}
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
        {{ session('success') }}
    </div>
    @endif

    {{-- Report list --}}
    @if($reports->isEmpty())
    <div class="text-center py-16 text-gray-500 dark:text-gray-400">
        <i class="ti ti-file-report text-5xl mb-3 block opacity-30"></i>
        <p class="text-lg font-medium">{{ app()->getLocale() === 'th' ? 'ยังไม่มีรายงาน' : 'No reports yet' }}</p>
        <p class="text-sm mt-1">{{ app()->getLocale() === 'th' ? 'สร้างรายงานแรกของโปรเจกต์นี้' : 'Create the first report for this project' }}</p>
        <a href="{{ route('projects.reports.create', $project) }}"
           class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
            <i class="ti ti-plus"></i>
            {{ app()->getLocale() === 'th' ? 'สร้างรายงาน' : 'Create Report' }}
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($reports as $report)
        <div class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all">
            {{-- Thumbnail --}}
            <div class="h-36 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 flex items-center justify-center">
                <i class="ti ti-presentation text-4xl text-indigo-300 dark:text-indigo-600"></i>
                <span class="absolute top-2 right-2 text-xs bg-white dark:bg-gray-700 rounded px-1.5 py-0.5 text-gray-600 dark:text-gray-300 shadow">
                    {{ $report->slides_count ?? $report->slides->count() }} {{ app()->getLocale() === 'th' ? 'สไลด์' : 'slides' }}
                </span>
            </div>
            {{-- Info --}}
            <div class="p-3">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate">{{ $report->title }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $report->creator->name ?? '—' }} · {{ $report->created_at->diffForHumans() }}
                </p>
            </div>
            {{-- Actions --}}
            <div class="px-3 pb-3 flex gap-2">
                <a href="{{ route('projects.reports.builder', [$project, $report]) }}"
                   class="flex-1 text-center text-xs py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    {{ app()->getLocale() === 'th' ? 'แก้ไข' : 'Edit' }}
                </a>
                <a href="{{ route('projects.reports.preview', [$project, $report]) }}"
                   target="_blank"
                   class="flex-1 text-center text-xs py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    {{ app()->getLocale() === 'th' ? 'ดูตัวอย่าง' : 'Preview' }}
                </a>
                <form method="POST" action="{{ route('projects.reports.destroy', [$project, $report]) }}"
                      onsubmit="return confirm('{{ app()->getLocale() === 'th' ? 'ลบรายงานนี้?' : 'Delete this report?' }}')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <i class="ti ti-trash text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
