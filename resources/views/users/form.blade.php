@extends('layouts.app')
@section('title', isset($user) ? __('common.edit') . ' User' : __('common.create') . ' User')

@section('breadcrumb')
<a href="{{ route('admin.users.index') }}" class="hover:text-indigo-600">{{ __('menu.users') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ isset($user) ? __('common.edit') : __('common.create') }}</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto"
     x-data="{
         factoryId: '{{ old('factory_id', isset($user) ? $user->factory_id : '') }}',
         sectionId: '{{ old('section_id', isset($user) ? $user->section_id : '') }}',
         sections: @json($sections->values()),
         async loadSections(fid) {
             if (!fid) { this.sections = []; return; }
             try {
                 const r = await fetch('/admin/factory-sections/' + fid);
                 this.sections = await r.json();
             } catch(e) { this.sections = []; }
         }
     }"
     x-init="if(factoryId) loadSections(factoryId)">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h1 class="text-xl font-bold">{{ isset($user) ? 'แก้ไขผู้ใช้งาน' : 'สร้างผู้ใช้งาน' }}</h1>
        </div>
        <form method="POST" action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" class="p-6 space-y-5">
            @csrf
            @if(isset($user)) @method('PUT') @endif

            {{-- Basic info --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Username (Display) <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">ชื่อภาษาไทย</label>
                    <input type="text" name="name_th" value="{{ old('name_th', $user->name_th ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">English Name</label>
                    <input type="text" name="name_en" value="{{ old('name_en', $user->name_en ?? '') }}" class="form-input">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">{{ app()->getLocale() === 'th' ? 'รหัสพนักงาน' : 'Employee Code' }} <span class="text-red-500">*</span></label>
                    <input type="text" name="employee_code" value="{{ old('employee_code', $user->employee_code ?? '') }}" class="form-input" required
                           placeholder="EMP001">
                </div>
                <div>
                    <label class="form-label">{{ app()->getLocale() === 'th' ? 'โทรศัพท์' : 'Phone' }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-input" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Password {{ isset($user) ? '' : '*' }}</label>
                    <input type="password" name="password" class="form-input" {{ isset($user) ? '' : 'required' }}
                           placeholder="{{ isset($user) ? (app()->getLocale() === 'th' ? 'เว้นว่างเพื่อไม่เปลี่ยน' : 'Leave blank to keep') : '' }}">
                </div>
                <div>
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-input">
                </div>
            </div>

            {{-- Factory & Section --}}
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl space-y-4">
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 flex items-center">
                    <i class="ti ti-building-factory-2 mr-2 text-indigo-500"></i>
                    {{ app()->getLocale() === 'th' ? 'Factory & Section' : 'Factory & Section' }}
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Factory</label>
                        <select name="factory_id" x-model="factoryId" @change="sectionId=''; loadSections(factoryId)" class="form-select">
                            <option value="">-- None --</option>
                            @foreach($factories as $f)
                            <option value="{{ $f->id }}" {{ old('factory_id', isset($user) ? $user->factory_id : '') == $f->id ? 'selected' : '' }}>
                                {{ $f->name_th }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-select" x-model="sectionId">
                            <option value="">-- None --</option>
                            <template x-for="s in sections" :key="s.id">
                                <option :value="s.id" :selected="s.id == sectionId" x-text="s.name_th + (s.name_en ? ' / ' + s.name_en : '')"></option>
                            </template>
                            <template x-if="sections.length === 0 && sectionId">
                                {{-- preserve pre-selected when sections haven't loaded yet --}}
                                <option :value="sectionId" selected>{{ isset($user) ? ($user->section?->name_th ?? '') : '' }}</option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="hidden" name="is_parent_factory" value="0">
                    <input type="checkbox" name="is_parent_factory" id="is_parent_factory" value="1" class="rounded text-purple-600"
                           {{ old('is_parent_factory', isset($user) ? $user->is_parent_factory : false) ? 'checked' : '' }}>
                    <label for="is_parent_factory" class="text-sm">
                        <span class="font-medium text-purple-600">{{ app()->getLocale() === 'th' ? 'สิทธิ์เข้าถึงข้ามโรงงาน (Cross-Factory Access)' : 'Cross-Factory Access (Parent Factory IT)' }}</span>
                        <span class="text-gray-400 text-xs ml-2">{{ app()->getLocale() === 'th' ? 'สำหรับ IT ส่วนกลางที่ดูแลหลาย Factory' : 'For HQ IT staff managing multiple factories' }}</span>
                    </label>
                </div>
            </div>

            {{-- Global Roles --}}
            <div>
                <label class="form-label">Global Roles <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1">
                    @foreach($roles as $r)
                    @php $userRoles = isset($user) ? $user->roles->pluck('name')->toArray() : []; @endphp
                    <label class="flex items-center space-x-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                        <input type="checkbox" name="roles[]" value="{{ $r->name }}"
                               {{ in_array($r->name, old('roles', $userRoles)) ? 'checked' : '' }}
                               class="rounded text-indigo-600">
                        <span class="text-sm">{{ $r->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Factory-specific Roles (shown when factory is selected) --}}
            <div x-show="factoryId" x-transition>
                <label class="form-label text-indigo-600">
                    <i class="ti ti-shield-lock mr-1"></i>
                    {{ app()->getLocale() === 'th' ? 'Role เฉพาะใน Factory นี้' : 'Factory-Specific Roles' }}
                </label>
                <p class="text-xs text-gray-400 mb-2">{{ app()->getLocale() === 'th' ? 'Role ที่ใช้เฉพาะใน Factory ที่เลือก (user_factory_roles)' : 'Roles effective only within the selected factory' }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach($roles as $r)
                    @php $factoryRoleIds = $factoryRoleIds ?? []; @endphp
                    <label class="flex items-center space-x-2 p-2 rounded-lg border border-indigo-100 dark:border-indigo-900/40 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                        <input type="checkbox" name="factory_roles[]" value="{{ $r->id }}"
                               {{ in_array($r->id, old('factory_roles', $factoryRoleIds)) ? 'checked' : '' }}
                               class="rounded text-indigo-600">
                        <span class="text-sm">{{ $r->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            @if(!isset($user) || $user->id !== auth()->id())
            <div class="flex items-center space-x-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
                       {{ old('is_active', isset($user) ? $user->is_active : true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm">{{ app()->getLocale() === 'th' ? 'เปิดใช้งาน' : 'Active' }}</label>
            </div>
            @endif

            <div class="flex items-center space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
