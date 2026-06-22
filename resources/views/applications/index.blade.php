@extends('layouts.app')
@section('title', 'Applications')
@section('breadcrumb')
<span>Applications</span>
@endsection

@section('content')
<div class="space-y-4 max-w-5xl">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Applications</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">ระบบและ Checksheet ที่คุณมีสิทธิ์เข้าถึง</p>
        </div>
    </div>

    {{-- Categorized groups --}}
    @php $anyVisible = false; @endphp

    @foreach($grouped as $item)
        @php
            $cat      = $item['category'];
            $appCount = $item['apps']->count();
            $csCount  = $item['checksheets']->count();
            $total    = $appCount + $csCount;
        @endphp
        @if($total > 0)
        @php $anyVisible = true; @endphp
        <div x-data="{ open: true }"
             class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">

            {{-- Category header --}}
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <i class="ti ti-chevron-right text-gray-400 transition-transform duration-200"
                   :class="open && 'rotate-90'"></i>
                <div class="w-7 h-7 rounded-lg bg-{{ $cat->color }}-100 dark:bg-{{ $cat->color }}-900/30
                            flex items-center justify-center flex-shrink-0">
                    <i class="ti {{ $cat->icon }} text-{{ $cat->color }}-600 dark:text-{{ $cat->color }}-400 text-sm"></i>
                </div>
                <span class="font-semibold text-sm">{{ $cat->name_th }}</span>
                @if($cat->name_en)
                <span class="text-xs text-gray-400">{{ $cat->name_en }}</span>
                @endif
                <span class="ml-auto text-xs text-gray-400 font-normal">{{ $total }} รายการ</span>
            </button>

            {{-- Item rows --}}
            <div x-show="open"
                 x-transition:enter="transition-all duration-200 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="border-t border-gray-50 dark:border-gray-700 divide-y divide-gray-50 dark:divide-gray-700/50">
                @foreach($item['apps'] as $entry)
                    @include('applications._row', ['entry' => $entry, 'type' => 'form'])
                @endforeach
                @foreach($item['checksheets'] as $entry)
                    @include('applications._row', ['entry' => $entry, 'type' => 'checksheet'])
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    {{-- Uncategorized --}}
    @if($uncatApps->isNotEmpty() || $uncatChecksheets->isNotEmpty())
    @php $anyVisible = true; @endphp
    <div x-data="{ open: true }"
         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <button @click="open = !open"
                class="w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
            <i class="ti ti-chevron-right text-gray-400 transition-transform duration-200"
               :class="open && 'rotate-90'"></i>
            <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-apps text-gray-500 dark:text-gray-400 text-sm"></i>
            </div>
            <span class="font-semibold text-sm text-gray-500 dark:text-gray-400">อื่น ๆ (ไม่ระบุหมวดหมู่)</span>
            <span class="ml-auto text-xs text-gray-400">{{ $uncatApps->count() + $uncatChecksheets->count() }} รายการ</span>
        </button>
        <div x-show="open"
             class="border-t border-gray-50 dark:border-gray-700 divide-y divide-gray-50 dark:divide-gray-700/50">
            @foreach($uncatApps as $entry)
                @include('applications._row', ['entry' => $entry, 'type' => 'form'])
            @endforeach
            @foreach($uncatChecksheets as $entry)
                @include('applications._row', ['entry' => $entry, 'type' => 'checksheet'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if(!$anyVisible)
    <div class="text-center py-24 text-gray-400">
        <i class="ti ti-apps-off text-6xl mb-4 block opacity-40"></i>
        <p class="text-lg font-medium">ยังไม่มี Application ที่คุณมีสิทธิ์เข้าถึง</p>
        <p class="text-sm mt-1">ติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์</p>
    </div>
    @endif

</div>
@endsection
