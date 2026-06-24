@extends('layouts.app')
@section('title', 'Option Sets')
@section('breadcrumb')
<span>Option Sets</span>
@endsection
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Option Sets</h1>
        <a href="{{ route('admin.option-sets.create') }}" class="btn-primary">
            <i class="ti ti-plus mr-1"></i>สร้าง Option Set
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-750 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Code</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Name (TH / EN)</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Source</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Items</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($optionSets as $os)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                    <td class="px-4 py-3 font-mono text-indigo-600 dark:text-indigo-400">{{ $os->code }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $os->name_th }}</div>
                        <div class="text-xs text-gray-400">{{ $os->name_en }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $os->source_type === 'static' ? 'bg-blue-100 text-blue-700' :
                               ($os->source_type === 'master' ? 'bg-purple-100 text-purple-700' :
                               ($os->source_type === 'users' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700')) }}">
                            {{ $os->source_type }}
                            @if($os->master_type) : {{ $os->master_type }} @endif
                        </span>
                        @if($os->filter_by_factory)
                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-orange-100 text-orange-600">by factory</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">
                        @if($os->source_type === 'static') {{ $os->items_count }} items
                        @else <span class="text-gray-400 italic">dynamic</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('admin.option-sets.edit', $os) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                        <form method="POST" action="{{ route('admin.option-sets.destroy', $os) }}" class="inline"
                              onsubmit="return confirm('ลบ Option Set นี้?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                        <i class="ti ti-list text-3xl block mb-2"></i>
                        ยังไม่มี Option Set
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($optionSets->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-600">
            {{ $optionSets->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
