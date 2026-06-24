@extends('layouts.app')

@section('title', (app()->getLocale() === 'th' ? 'เทมเพลตรายงาน' : 'Report Templates'))

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('projects.reports.index', $project) }}"
           class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <i class="ti ti-arrow-left text-xl"></i>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ app()->getLocale() === 'th' ? 'เทมเพลตรายงาน' : 'Report Templates' }}
        </h1>
    </div>

    @if($templates->isEmpty())
    <div class="text-center py-16 text-gray-500 dark:text-gray-400">
        <i class="ti ti-template text-5xl mb-3 block opacity-30"></i>
        <p class="text-lg font-medium">{{ app()->getLocale() === 'th' ? 'ยังไม่มีเทมเพลต' : 'No templates yet' }}</p>
        <p class="text-sm mt-1">
            {{ app()->getLocale() === 'th'
                ? 'บันทึกรายงานเป็นเทมเพลตจากหน้า Builder เพื่อนำมาใช้ซ้ำ'
                : 'Save a report as a template from the Builder to reuse it.' }}
        </p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($templates as $tpl)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all">
            <div class="h-32 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 flex items-center justify-center">
                <i class="ti ti-layout-grid text-3xl text-purple-400 dark:text-purple-500"></i>
            </div>
            <div class="p-3">
                <h3 class="font-semibold text-sm text-gray-900 dark:text-white">{{ $tpl->template_name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $tpl->slides->count() }} {{ app()->getLocale() === 'th' ? 'สไลด์' : 'slides' }}
                </p>
            </div>
            <div class="px-3 pb-3">
                <form method="POST" action="{{ route('projects.reports.store', $project) }}">
                    @csrf
                    <input type="hidden" name="title" value="{{ $tpl->template_name }} (Copy)">
                    <input type="hidden" name="template_id" value="{{ $tpl->id }}">
                    <button type="submit"
                            class="w-full py-1.5 rounded-lg text-xs bg-indigo-600 text-white hover:bg-indigo-700">
                        <i class="ti ti-copy mr-1"></i>
                        {{ app()->getLocale() === 'th' ? 'ใช้เทมเพลตนี้' : 'Use Template' }}
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
