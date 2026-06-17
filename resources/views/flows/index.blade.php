@extends('layouts.app')
@section('title', 'Flow Library')
@section('breadcrumb')
<span>Flow Library</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Flow Library</h1>
        @can('app.create')
        <button onclick="document.getElementById('create-modal').classList.remove('hidden')"
                class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>สร้าง Flow</span>
        </button>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->has('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ $errors->first('error') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($flows as $flow)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex flex-col space-y-3">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-bold">{{ $flow->name }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $flow->description }}</p>
                </div>
                @if(!$flow->is_active)
                <span class="text-xs bg-red-100 text-red-500 px-2 py-0.5 rounded-full flex-shrink-0">Inactive</span>
                @endif
            </div>

            <div class="flex items-center space-x-4 text-xs text-gray-500">
                <span><i class="ti ti-circle-dot mr-1"></i>{{ $flow->nodes->count() }} nodes</span>
                <span><i class="ti ti-apps mr-1"></i>{{ $flow->apps_count }} apps</span>
            </div>

            <!-- Node type badges -->
            <div class="flex flex-wrap gap-1">
                @foreach($flow->nodes->groupBy('type') as $type => $nodes)
                @php
                    $colors = ['start'=>'bg-gray-100 text-gray-600','approval'=>'bg-blue-100 text-blue-600','end_approved'=>'bg-green-100 text-green-600','end_rejected'=>'bg-red-100 text-red-500','return_revision'=>'bg-yellow-100 text-yellow-600'];
                @endphp
                <span class="text-xs px-2 py-0.5 rounded-full {{ $colors[$type] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $type }} ({{ $nodes->count() }})
                </span>
                @endforeach
            </div>

            <div class="flex items-center space-x-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('admin.flows.designer', $flow) }}"
                   class="flex-1 text-center text-xs btn-outline flex items-center justify-center space-x-1">
                    <i class="ti ti-git-branch"></i><span>Design Flow</span>
                </a>
                @can('app.create')
                <form method="POST" action="{{ route('admin.flows.duplicate', $flow) }}">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-500 hover:text-indigo-700 px-2 py-1 rounded border border-indigo-200 hover:border-indigo-400">
                        <i class="ti ti-copy"></i>
                    </button>
                </form>
                @endcan
                @can('app.delete')
                <form method="POST" action="{{ route('admin.flows.destroy', $flow) }}"
                      onsubmit="return confirm('ลบ Flow นี้?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded border border-red-100 hover:border-red-300">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-400">
            <i class="ti ti-git-branch text-6xl mb-4 block"></i>
            <p>ยังไม่มี Flow</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="font-bold text-lg mb-4">สร้าง Flow ใหม่</h2>
        <form method="POST" action="{{ route('admin.flows.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">ชื่อ Flow <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="form-input" required placeholder="เช่น IT Standard Approval">
            </div>
            <div>
                <label class="form-label">คำอธิบาย</label>
                <textarea name="description" rows="2" class="form-input"></textarea>
            </div>
            <div class="flex space-x-3 pt-2">
                <button type="submit" class="btn-primary flex-1">สร้างและออกแบบ</button>
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')"
                        class="btn-secondary flex-1">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>
@endsection
