@extends('layouts.app')
@section('title', 'Data Management')
@section('breadcrumb')
<span>Admin</span>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>Data Management</span>
@endsection

@section('content')
<div x-data="{ archiveYear: null, dropTable: null, showArchiveConfirm: false, showDropConfirm: false }" class="space-y-6">
    <h1 class="text-xl font-bold">Data Management</h1>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Hot Table Stats --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6 mis-card">
        <h2 class="font-semibold text-base mb-4 flex items-center space-x-2">
            <i class="ti ti-database-export text-indigo-500 text-lg"></i>
            <span>Hot Table: {{ $hotStats['table'] }}</span>
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
            <div class="text-center p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                <div class="text-2xl font-bold text-indigo-600">{{ number_format($hotStats['row_count']) }}</div>
                <div class="text-xs text-gray-500 mt-1">Rows</div>
            </div>
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <div class="text-2xl font-bold text-blue-600">{{ $hotStats['size_mb'] }} MB</div>
                <div class="text-xs text-gray-500 mt-1">Size</div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $hotStats['min_year'] ?? '—' }}</div>
                <div class="text-xs text-gray-500 mt-1">ปีเก่าสุด</div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $hotStats['max_year'] ?? '—' }}</div>
                <div class="text-xs text-gray-500 mt-1">ปีล่าสุด</div>
            </div>
        </div>

        @if($hotStats['min_year'] && $hotStats['max_year'])
        <div>
            <p class="text-sm font-medium mb-2">Archive ข้อมูลตามปี:</p>
            <div class="flex flex-wrap gap-2">
                @for($y = $hotStats['min_year']; $y <= $hotStats['max_year']; $y++)
                @if($y < now()->year)
                <button type="button"
                        @click="archiveYear = {{ $y }}; showArchiveConfirm = true"
                        class="btn-secondary text-sm flex items-center space-x-1">
                    <i class="ti ti-archive"></i>
                    <span>Archive ปี {{ $y }}</span>
                </button>
                @endif
                @endfor
                @if($hotStats['min_year'] >= now()->year)
                <p class="text-sm text-gray-400">ไม่มีข้อมูลที่พร้อม Archive (ต้องเป็นปีก่อนหน้า)</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Archive Tables --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6 mis-card">
        <h2 class="font-semibold text-base mb-4 flex items-center space-x-2">
            <i class="ti ti-archive text-yellow-500 text-lg"></i>
            <span>Archive Tables ({{ count($archiveTables) }})</span>
        </h2>

        @if(empty($archiveTables))
        <div class="text-center py-8 text-gray-400">
            <i class="ti ti-archive text-4xl block mb-2"></i>
            <p class="text-sm">ยังไม่มีตาราง Archive</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3 text-left">Table Name</th>
                        <th class="px-4 py-3 text-right">Rows</th>
                        <th class="px-4 py-3 text-right">Size MB</th>
                        <th class="px-4 py-3 text-left">Archived</th>
                        <th class="px-4 py-3 text-left">By</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($archiveTables as $at)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 font-mono text-xs">{{ $at['table'] }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($at['row_count']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $at['size_mb'] }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $at['archived_at'] ? \Carbon\Carbon::parse($at['archived_at'])->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $at['archived_by'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                @can('setting.view')
                                <button type="button"
                                        @click="dropTable = '{{ $at['table'] }}'; showDropConfirm = true"
                                        class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded border border-red-100 hover:border-red-300">
                                    <i class="ti ti-trash mr-1"></i>Drop
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Archive Confirm Modal --}}
    <div x-show="showArchiveConfirm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         x-cloak>
        <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 class="font-bold text-lg mb-2 flex items-center space-x-2 text-yellow-600">
                <i class="ti ti-archive"></i><span>ยืนยัน Archive</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                คุณต้องการ Archive ข้อมูลปี <b x-text="archiveYear"></b> ไปยัง archive table ใหม่?
                <br>ข้อมูลปีนี้จะถูกลบออกจาก Hot Table
            </p>
            <form method="POST" action="{{ route('admin.data-management.archive') }}">
                @csrf
                <input type="hidden" name="year" x-bind:value="archiveYear">
                <div class="flex space-x-3">
                    <button type="submit" class="btn-primary flex-1">
                        <i class="ti ti-archive mr-1"></i>ยืนยัน Archive
                    </button>
                    <button type="button" @click="showArchiveConfirm = false" class="btn-secondary flex-1">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Drop Confirm Modal --}}
    <div x-show="showDropConfirm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         x-cloak>
        <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 class="font-bold text-lg mb-2 flex items-center space-x-2 text-red-600">
                <i class="ti ti-alert-triangle"></i><span>ยืนยันการลบตาราง</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                คุณแน่ใจหรือไม่ที่จะลบตาราง <b class="font-mono text-red-600" x-text="dropTable"></b>?
                <br><span class="text-red-500 font-semibold">ข้อมูลจะหายถาวรและไม่สามารถกู้คืนได้!</span>
            </p>
            <form method="POST" action="{{ route('admin.data-management.drop') }}">
                @csrf @method('DELETE')
                <input type="hidden" name="table_name" x-bind:value="dropTable">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-xl transition-colors">
                        <i class="ti ti-trash mr-1"></i>ลบถาวร
                    </button>
                    <button type="button" @click="showDropConfirm = false" class="btn-secondary flex-1">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
