@extends('layouts.app')
@section('title', isset($role) ? 'Edit Role' : 'Create Role')
@section('breadcrumb')
<a href="{{ route('admin.roles.index') }}" class="hover:text-indigo-600">{{ __('menu.roles') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ isset($role) ? __('common.edit') : __('common.create') }}</span>
@endsection
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden mis-card">
        <div class="p-6 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-bold">{{ isset($role) ? 'แก้ไข Role' : 'สร้าง Role' }}</h1>
        </div>
        <form method="POST" action="{{ isset($role) ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="p-6 space-y-5">
            @csrf
            @if(isset($role)) @method('PUT') @endif
            <div>
                <label class="form-label">Role Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}" class="form-input" required
                       {{ isset($role) && in_array($role->name, ['super_admin','it_manager','it_staff','team_lead','requester']) ? 'readonly' : '' }}>
            </div>

            <div>
                <h3 class="form-label mb-3">Permissions</h3>
                <div class="space-y-4">
                    @foreach($permissions as $module => $perms)
                    <div class="border border-gray-300 dark:border-gray-600 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-2.5 flex items-center justify-between">
                            <h4 class="font-semibold text-sm uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ $module }}</h4>
                            <button type="button" onclick="toggleGroup('{{ $module }}')" class="text-xs text-indigo-500 hover:underline">
                                {{ app()->getLocale() === 'th' ? 'เลือกทั้งหมด' : 'Select All' }}
                            </button>
                        </div>
                        <div class="p-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2" id="group-{{ $module }}">
                            @foreach($perms as $perm)
                            @php $action = explode('.', $perm->name)[1] ?? ''; @endphp
                            <label class="flex items-center space-x-2 p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                       class="rounded text-indigo-600 perm-{{ $module }}"
                                       {{ in_array($perm->name, old('permissions', $rolePerms ?? [])) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $action }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
                <a href="{{ route('admin.roles.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function toggleGroup(module) {
    const checkboxes = document.querySelectorAll('.perm-' + module);
    const allChecked = Array.from(checkboxes).every(c => c.checked);
    checkboxes.forEach(c => c.checked = !allChecked);
}
</script>
@endpush
