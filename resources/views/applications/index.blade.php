@extends('layouts.app')
@section('title', 'Applications')
@section('breadcrumb')
<span>Applications</span>
@endsection

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Applications</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ระบบและ Checksheet ที่คุณมีสิทธิ์เข้าถึง</p>
        </div>
    </div>

    {{-- Categorized --}}
    @foreach($grouped as $item)
        @php
            $cat   = $item['category'];
            $items = $item['apps']->merge($item['checksheets'])->sortBy('name');
        @endphp
        @if($items->isNotEmpty())
        <section>
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-{{ $cat->color }}-100 dark:bg-{{ $cat->color }}-900/30 flex items-center justify-center flex-shrink-0">
                    <i class="ti {{ $cat->icon }} text-{{ $cat->color }}-600 dark:text-{{ $cat->color }}-400"></i>
                </div>
                <h2 class="text-lg font-semibold">{{ $cat->name_th }}</h2>
                @if($cat->name_en)
                <span class="text-sm text-gray-400">{{ $cat->name_en }}</span>
                @endif
                <span class="ml-auto text-xs text-gray-400">{{ $items->count() }} รายการ</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($item['apps'] as $app)
                    @include('applications._card', ['entry' => $app, 'type' => 'form'])
                @endforeach
                @foreach($item['checksheets'] as $cs)
                    @include('applications._card', ['entry' => $cs, 'type' => 'checksheet'])
                @endforeach
            </div>
        </section>
        @endif
    @endforeach

    {{-- Uncategorized --}}
    @if($uncatApps->isNotEmpty() || $uncatChecksheets->isNotEmpty())
    <section>
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-apps text-gray-500"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-600 dark:text-gray-400">อื่น ๆ</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($uncatApps as $app)
                @include('applications._card', ['entry' => $app, 'type' => 'form'])
            @endforeach
            @foreach($uncatChecksheets as $cs)
                @include('applications._card', ['entry' => $cs, 'type' => 'checksheet'])
            @endforeach
        </div>
    </section>
    @endif

    {{-- Empty state --}}
    @if(empty(array_filter($grouped, fn($g) => $g['apps']->isNotEmpty() || $g['checksheets']->isNotEmpty())) && $uncatApps->isEmpty() && $uncatChecksheets->isEmpty())
    <div class="text-center py-20 text-gray-400">
        <i class="ti ti-apps-off text-6xl mb-4 block"></i>
        <p class="text-lg font-medium">ยังไม่มี Application ที่คุณมีสิทธิ์เข้าถึง</p>
        <p class="text-sm mt-1">ติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์</p>
    </div>
    @endif

</div>
@endsection
