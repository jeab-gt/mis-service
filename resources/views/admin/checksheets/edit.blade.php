@extends('layouts.app')
@section('title', 'แก้ไข Template: ' . $template->name)
@section('breadcrumb')
<span>Admin</span>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<a href="{{ route('admin.checksheets.index') }}" class="hover:text-indigo-500">Checksheet Templates</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>แก้ไข</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">แก้ไข: {{ $template->name }}</h1>
        <a href="{{ route('admin.checksheets.builder', $template) }}" class="btn-secondary flex items-center space-x-1 text-sm">
            <i class="ti ti-layout-grid-add"></i><span>ไปที่ Builder</span>
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form method="POST" action="{{ route('admin.checksheets.save', $template) }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">ชื่อ Template <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $template->name) }}" class="form-input" required>
            </div>

            <div>
                <label class="form-label">คำอธิบาย</label>
                <textarea name="description" rows="3" class="form-input">{{ old('description', $template->description) }}</textarea>
            </div>

            <div>
                <label class="form-label">ความถี่ในการกรอก <span class="text-red-500">*</span></label>
                <select name="frequency" class="form-select" required>
                    @foreach(['realtime' => 'Real-time', 'hourly' => 'ทุกชั่วโมง', 'daily' => 'ทุกวัน', 'weekly' => 'ทุกสัปดาห์', 'monthly' => 'ทุกเดือน'] as $value => $label)
                    <option value="{{ $value }}" {{ old('frequency', $template->frequency) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Flow (ถ้ามี)</label>
                <select name="flow_id" class="form-select">
                    <option value="">— ไม่มี Flow —</option>
                    @foreach($flows as $flow)
                    <option value="{{ $flow->id }}" {{ old('flow_id', $template->flow_id) == $flow->id ? 'selected' : '' }}>{{ $flow->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Factory Scope <span class="text-red-500">*</span></label>
                <div class="space-y-2 mt-2">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="radio" name="factory_scope" value="own_factory"
                               {{ old('factory_scope', $template->factory_scope) === 'own_factory' ? 'checked' : '' }}
                               class="text-indigo-600">
                        <span class="text-sm">Own Factory</span>
                    </label>
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="radio" name="factory_scope" value="all_factories"
                               {{ old('factory_scope', $template->factory_scope) === 'all_factories' ? 'checked' : '' }}
                               class="text-indigo-600">
                        <span class="text-sm">All Factories</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                       class="rounded text-indigo-600">
                <label for="is_active" class="form-label mb-0 cursor-pointer">เปิดใช้งาน (Active)</label>
            </div>

            {{-- Portal Settings --}}
            <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
                <h3 class="font-semibold text-sm mb-3 flex items-center space-x-2">
                    <i class="ti ti-layout-grid text-purple-500"></i><span>Portal Settings</span>
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">หมวดหมู่ (Portal)</label>
                        <select name="category_id" class="form-select">
                            <option value="">— ไม่ระบุ —</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ old('category_id', $template->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name_th }}{{ $cat->name_en ? ' — ' . $cat->name_en : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Dashboard ที่ผูกไว้</label>
                        <select name="primary_dashboard_id" class="form-select">
                            <option value="">— ไม่มี Dashboard —</option>
                            @foreach($dashboards as $db)
                            <option value="{{ $db->id }}"
                                {{ old('dashboard_id', $template->primary_dashboard_id) == $db->id ? 'selected' : '' }}>
                                {{ $db->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Roles ที่มีสิทธิ์ (ว่าง = ทุก Role)</label>
                        <div class="flex flex-wrap gap-3 mt-1">
                            @foreach($roles as $role)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="allowed_roles[]" value="{{ $role->name }}"
                                       {{ in_array($role->name, old('allowed_roles', $template->allowed_roles ?? [])) ? 'checked' : '' }}
                                       class="rounded text-indigo-600">
                                <span class="text-sm">{{ $role->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Factories ที่มีสิทธิ์ (ว่าง = ทุก Factory)</label>
                        <div class="flex flex-wrap gap-3 mt-1">
                            @foreach($factories as $factory)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="allowed_factories[]" value="{{ $factory->id }}"
                                       {{ in_array($factory->id, old('allowed_factories', $template->allowed_factories ?? [])) ? 'checked' : '' }}
                                       class="rounded text-indigo-600">
                                <span class="text-sm">{{ $factory->name_th }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <button type="submit" class="btn-primary flex items-center space-x-2">
                    <i class="ti ti-device-floppy"></i><span>บันทึก</span>
                </button>
                <a href="{{ route('admin.checksheets.index') }}" class="btn-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
