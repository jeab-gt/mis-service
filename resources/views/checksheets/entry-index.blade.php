@extends('layouts.app')
@section('title', 'Checksheet')
@section('breadcrumb')
<span>Checksheet</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Checksheet</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($templates as $template)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-5 flex flex-col space-y-3">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold">{{ $template->name }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ $template->description ?? '' }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
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
                <span class="text-xs text-gray-400">
                    <i class="ti ti-sliders mr-1"></i>{{ $template->parameters_count }} items
                </span>
            </div>

            <div class="flex items-center space-x-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                <a href="{{ route('checksheets.fill', $template) }}"
                   class="flex-1 text-center btn-primary flex items-center justify-center space-x-1 text-sm">
                    <i class="ti ti-edit"></i><span>กรอกข้อมูล</span>
                </a>
                <a href="{{ route('checksheets.records', $template) }}"
                   class="flex-1 text-center btn-secondary flex items-center justify-center space-x-1 text-sm">
                    <i class="ti ti-list"></i><span>ดูรายการ</span>
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-400">
            <i class="ti ti-clipboard-list text-6xl mb-4 block"></i>
            <p>ยังไม่มี Checksheet ที่เปิดใช้งาน</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
