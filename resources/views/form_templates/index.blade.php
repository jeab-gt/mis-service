@extends('layouts.app')
@section('title', 'Form Library')
@section('breadcrumb')
<span>Form Library</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Form Library</h1>
        @can('app.create')
        <button onclick="document.getElementById('create-modal').classList.remove('hidden')"
                class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>สร้าง Form Template</span>
        </button>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->has('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ $errors->first('error') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left">ชื่อ</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-center">Fields</th>
                    <th class="px-4 py-3 text-center">ใช้ใน Apps</th>
                    <th class="px-4 py-3 text-center">ใช้ใน Flows</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($templates as $t)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="px-4 py-3 font-medium">{{ $t->name }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $t->category }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ count($t->schema['fields'] ?? []) }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $t->apps_as_initial_count + $t->apps_as_revision_count }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $t->flow_nodes_count }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($t->is_active)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-600">Active</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('admin.form-templates.designer', $t) }}"
                               class="text-xs btn-outline flex items-center space-x-1">
                                <i class="ti ti-layout-grid-add"></i><span>Design</span>
                            </a>
                            @can('app.create')
                            <form method="POST" action="{{ route('admin.form-templates.duplicate', $t) }}">
                                @csrf
                                <button type="submit" class="text-xs text-indigo-500 hover:text-indigo-700 px-2 py-1 rounded border border-indigo-200 hover:border-indigo-400">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </form>
                            @endcan
                            @can('app.delete')
                            <form method="POST" action="{{ route('admin.form-templates.destroy', $t) }}"
                                  onsubmit="return confirm('ลบ Form Template นี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded border border-red-100 hover:border-red-300">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                        <i class="ti ti-forms text-5xl block mb-2"></i>
                        <p>ยังไม่มี Form Template</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="font-bold text-lg mb-4">สร้าง Form Template ใหม่</h2>
        <form method="POST" action="{{ route('admin.form-templates.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">ชื่อ Template <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="form-input" required placeholder="เช่น IT Request Form">
            </div>
            <div>
                <label class="form-label">Category <span class="text-red-500">*</span></label>
                <select name="category" class="form-select" required>
                    <option value="general">general</option>
                    <option value="maintenance">maintenance</option>
                    <option value="development">development</option>
                    <option value="step_form">step_form</option>
                    <option value="revision">revision</option>
                </select>
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
