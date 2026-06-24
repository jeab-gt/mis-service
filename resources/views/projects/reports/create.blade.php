@extends('layouts.app')

@section('title', (app()->getLocale() === 'th' ? 'สร้างรายงาน' : 'Create Report') . ' — ' . $project->name)

@section('content')
<div class="p-6 max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.reports.index', $project) }}"
           class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <i class="ti ti-arrow-left text-xl"></i>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ app()->getLocale() === 'th' ? 'สร้างรายงานใหม่' : 'New Report' }}
        </h1>
    </div>

    <form method="POST" action="{{ route('projects.reports.store', $project) }}"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ app()->getLocale() === 'th' ? 'ชื่อรายงาน' : 'Report Title' }} <span class="text-red-500">*</span>
            </label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   placeholder="{{ app()->getLocale() === 'th' ? 'เช่น รายงานประจำเดือน มิ.ย. 2569' : 'e.g. Monthly Status Report – June 2026' }}"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            @error('title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ app()->getLocale() === 'th' ? 'คำอธิบาย' : 'Description' }}
            </label>
            <textarea name="description" rows="2"
                      placeholder="{{ app()->getLocale() === 'th' ? 'คำอธิบายสั้นๆ (ไม่บังคับ)' : 'Short description (optional)' }}"
                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description') }}</textarea>
        </div>

        @if($templates->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ app()->getLocale() === 'th' ? 'เริ่มจากเทมเพลต (ไม่บังคับ)' : 'Start from template (optional)' }}
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="relative cursor-pointer">
                    <input type="radio" name="template_id" value="" class="peer sr-only" checked>
                    <div class="rounded-lg border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 p-3 text-center hover:border-indigo-300 transition-colors">
                        <i class="ti ti-file-plus text-2xl text-gray-400 mb-1 block"></i>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                            {{ app()->getLocale() === 'th' ? 'เปล่า' : 'Blank' }}
                        </span>
                    </div>
                </label>
                @foreach($templates as $tpl)
                <label class="relative cursor-pointer">
                    <input type="radio" name="template_id" value="{{ $tpl->id }}" class="peer sr-only">
                    <div class="rounded-lg border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 p-3 text-center hover:border-indigo-300 transition-colors">
                        <i class="ti ti-layout-grid text-2xl text-indigo-400 mb-1 block"></i>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 line-clamp-2">{{ $tpl->template_name }}</span>
                        <span class="text-xs text-gray-400">{{ $tpl->slides->count() }} slides</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex gap-3 pt-2">
            <a href="{{ route('projects.reports.index', $project) }}"
               class="flex-1 text-center px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                {{ app()->getLocale() === 'th' ? 'ยกเลิก' : 'Cancel' }}
            </a>
            <button type="submit"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                <i class="ti ti-presentation"></i>
                {{ app()->getLocale() === 'th' ? 'สร้างและเปิด Builder' : 'Create & Open Builder' }}
            </button>
        </div>
    </form>
</div>
@endsection
