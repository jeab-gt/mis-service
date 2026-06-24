@extends('layouts.app')
@section('title', 'รายการ: ' . $template->name)
@section('breadcrumb')
<a href="{{ route('checksheets.index') }}" class="hover:text-indigo-500">Checksheet</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>{{ $template->name }}</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">{{ $template->name }}</h1>
            <p class="text-sm text-gray-400">รายการที่กรอกแล้ว</p>
        </div>
        <a href="{{ route('checksheets.fill', $template) }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>กรอกข้อมูลใหม่</span>
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3 text-left">วันที่</th>
                    <th class="px-4 py-3 text-left">Time Slot</th>
                    <th class="px-4 py-3 text-left">สถานะ</th>
                    <th class="px-4 py-3 text-center">Alerts</th>
                    <th class="px-4 py-3 text-left">กรอกโดย</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($records as $record)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="px-4 py-3 font-medium">{{ $record->record_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $record->timeSlot?->label ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusBadge = [
                                'draft'     => 'bg-gray-100 text-gray-600',
                                'submitted' => 'bg-blue-100 text-blue-600',
                                'approved'  => 'bg-green-100 text-green-600',
                                'rejected'  => 'bg-red-100 text-red-600',
                            ];
                            $statusLabel = [
                                'draft'     => 'Draft',
                                'submitted' => 'ส่งแล้ว',
                                'approved'  => 'อนุมัติแล้ว',
                                'rejected'  => 'ไม่ผ่าน',
                            ];
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $statusBadge[$record->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabel[$record->status] ?? $record->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($record->alert_count > 0)
                        <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium">
                            <i class="ti ti-alert-triangle mr-1"></i>{{ $record->alert_count }}
                        </span>
                        @else
                        <span class="text-xs text-gray-300">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $record->submitter?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($record->status === 'draft')
                        <form method="POST" action="{{ route('checksheets.submit', $record) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-blue-500 hover:text-blue-700 border border-blue-200 hover:border-blue-400 px-2 py-1 rounded">
                                <i class="ti ti-send mr-1"></i>ส่ง
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                        <i class="ti ti-clipboard-list text-4xl block mb-2"></i>
                        ยังไม่มีรายการ
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $records->links() }}</div>
</div>
@endsection
