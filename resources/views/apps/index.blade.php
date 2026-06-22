@extends('layouts.app')
@section('title', 'App Builder')
@section('breadcrumb')
<span>App Builder</span>
@endsection

@section('content')
<div class="space-y-4" x-data="{ showCreateModal: false }" @keydown.escape.window="showCreateModal = false">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">App Builder</h1>
        @can('app.create')
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.form-templates.index') }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-forms"></i><span>Form Library</span>
            </a>
            <a href="{{ route('admin.flows.index') }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-git-branch"></i><span>Flow Library</span>
            </a>
            <button @click="showCreateModal = true" class="btn-primary flex items-center space-x-2">
                <i class="ti ti-plus"></i><span>สร้างใหม่</span>
            </button>
        </div>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Unified grid: Form Apps + Checksheets --}}
    @php $totalCount = $apps->count() + $checksheets->count(); @endphp

    @if($totalCount === 0)
    <div class="text-center py-20 text-gray-400">
        <i class="ti ti-tool text-6xl mb-4 block opacity-30"></i>
        <p class="text-lg font-medium">ยังไม่มี App หรือ Checksheet</p>
        <button @click="showCreateModal = true" class="mt-4 btn-primary text-sm">
            <i class="ti ti-plus mr-1"></i>สร้างตัวแรก
        </button>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

        {{-- Form App cards --}}
        @foreach($apps as $app)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                        <i class="ti {{ $app->icon ?? 'ti-file-text' }} text-xl text-indigo-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <h3 class="font-bold text-sm truncate">{{ $app->name }}</h3>
                            @if(!$app->is_active)
                            <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-500 px-1.5 py-0.5 rounded-full flex-shrink-0">Inactive</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-1.5 py-0.5 rounded-full">Form</span>
                            @if($app->category)
                            <span class="text-xs text-gray-400 truncate">{{ $app->category->name_th }}</span>
                            @elseif($app->category)
                            <span class="text-xs text-gray-400">{{ $app->category }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <i class="ti ti-forms text-indigo-400 flex-shrink-0 w-4"></i>
                        <span class="truncate">{{ $app->initialFormTemplate?->name ?? '— ไม่มี Form —' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="ti ti-git-branch text-blue-400 flex-shrink-0 w-4"></i>
                        <span class="truncate">{{ $app->flow?->name ?? '— ไม่มี Flow —' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="ti ti-send text-gray-400 flex-shrink-0 w-4"></i>
                        <span>{{ $app->submissions_count }} submissions</span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-1.5 items-center">
                @can('app.edit')
                <a href="{{ route('admin.apps.edit', $app) }}"
                   class="text-xs btn-outline flex items-center gap-1">
                    <i class="ti ti-settings text-xs"></i><span>Settings</span>
                </a>
                @endcan
                @if($app->initialFormTemplate)
                <a href="{{ route('admin.form-templates.designer', $app->initialFormTemplate) }}"
                   class="text-xs text-indigo-500 hover:text-indigo-700 flex items-center gap-1 border border-indigo-100 dark:border-indigo-800 rounded-lg px-2 py-1 hover:border-indigo-300 transition-colors">
                    <i class="ti ti-layout-grid-add text-xs"></i><span>Form</span>
                </a>
                @endif
                @if($app->flow)
                <a href="{{ route('admin.flows.designer', $app->flow) }}"
                   class="text-xs text-blue-500 hover:text-blue-700 flex items-center gap-1 border border-blue-100 dark:border-blue-800 rounded-lg px-2 py-1 hover:border-blue-300 transition-colors">
                    <i class="ti ti-git-branch text-xs"></i><span>Flow</span>
                </a>
                @endif
                @can('app.delete')
                <form method="POST" action="{{ route('admin.apps.destroy', $app) }}"
                      onsubmit="return confirm('ลบ App นี้?')" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @endforeach

        {{-- Checksheet cards --}}
        @foreach($checksheets as $template)
        @php
            $freqColors = [
                'realtime' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
                'hourly'   => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                'daily'    => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
                'weekly'   => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
                'monthly'  => 'bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400',
            ];
            $freqClass = $freqColors[$template->frequency] ?? 'bg-gray-100 text-gray-600';
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center flex-shrink-0">
                        <i class="ti ti-clipboard-list text-xl text-teal-600 dark:text-teal-400"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <h3 class="font-bold text-sm truncate">{{ $template->name }}</h3>
                            @if(!$template->is_active)
                            <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-500 px-1.5 py-0.5 rounded-full flex-shrink-0">Inactive</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-xs bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 px-1.5 py-0.5 rounded-full">Checksheet</span>
                            @if($template->category)
                            <span class="text-xs text-gray-400 truncate">{{ $template->category->name_th }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $freqClass }}">{{ $template->frequency }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <i class="ti ti-sliders-horizontal mr-0.5"></i>{{ $template->parameters_count }} params
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <i class="ti ti-file-description mr-0.5"></i>{{ $template->records_count }} records
                    </span>
                </div>

                @if($template->description)
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500 line-clamp-2">{{ $template->description }}</p>
                @endif
            </div>

            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-1.5 items-center">
                <a href="{{ route('admin.checksheets.edit', $template) }}"
                   class="text-xs btn-outline flex items-center gap-1">
                    <i class="ti ti-settings text-xs"></i><span>Settings</span>
                </a>
                <a href="{{ route('admin.checksheets.builder', $template) }}"
                   class="text-xs text-teal-600 hover:text-teal-800 flex items-center gap-1 border border-teal-200 dark:border-teal-800 rounded-lg px-2 py-1 hover:border-teal-400 transition-colors">
                    <i class="ti ti-layout-grid-add text-xs"></i><span>Builder</span>
                </a>
                <a href="{{ route('checksheets.records', $template) }}"
                   class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1 hover:border-gray-400 transition-colors">
                    <i class="ti ti-table text-xs"></i><span>Records</span>
                </a>
                <form method="POST" action="{{ route('admin.checksheets.destroy', $template) }}"
                      onsubmit="return confirm('ลบ Checksheet Template นี้?')" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach

    </div>
    @endif

    {{-- Create modal --}}
    <div x-show="showCreateModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @click.self="showCreateModal = false"
         style="display:none;">
        <div x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white dark:bg-gray-800 rounded-2xl p-8 w-full max-w-md shadow-2xl">

            <h2 class="text-xl font-bold mb-1">สร้างใหม่</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">เลือกประเภทที่ต้องการสร้าง</p>

            <div class="grid grid-cols-2 gap-4">
                {{-- Form App --}}
                <a href="{{ route('admin.apps.create') }}"
                   class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 dark:border-gray-600
                          rounded-xl hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                          transition-all cursor-pointer group">
                    <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center
                                group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800/60 transition-colors">
                        <i class="ti ti-file-text text-2xl text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Form App</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-snug">
                            สร้างฟอร์มขอ/แจ้ง<br>พร้อม Approval Flow
                        </div>
                    </div>
                </a>

                {{-- Checksheet --}}
                <a href="{{ route('admin.apps.create', ['type' => 'checksheet']) }}"
                   class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 dark:border-gray-600
                          rounded-xl hover:border-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20
                          transition-all cursor-pointer group">
                    <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center
                                group-hover:bg-teal-200 dark:group-hover:bg-teal-800/60 transition-colors">
                        <i class="ti ti-clipboard-list text-2xl text-teal-600 dark:text-teal-400"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Checksheet</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-snug">
                            สร้างแบบฟอร์มบันทึก<br>ข้อมูลซ้ำๆ รายวัน/กะ
                        </div>
                    </div>
                </a>
            </div>

            <button @click="showCreateModal = false"
                    class="mt-6 w-full py-2.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200
                           rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                ยกเลิก
            </button>
        </div>
    </div>

</div>
@endsection
