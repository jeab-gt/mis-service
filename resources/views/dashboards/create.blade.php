@extends('layouts.app')
@section('title', 'สร้าง Dashboard')
@section('breadcrumb')
<a href="{{ route('dashboards.index') }}" class="hover:text-indigo-500">Dashboards</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>สร้างใหม่</span>
@endsection

@section('content')
<div class="max-w-xl mx-auto space-y-4">
    <h1 class="text-xl font-bold">สร้าง Dashboard ใหม่</h1>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-6 mis-card">
        <form method="POST" action="{{ route('dashboards.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">ชื่อ Dashboard <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required
                       placeholder="เช่น Line A Overview">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">Factory Scope <span class="text-red-500">*</span></label>
                <select name="factory_scope" class="form-select" required>
                    <option value="own_factory" {{ old('factory_scope', 'own_factory') === 'own_factory' ? 'selected' : '' }}>Own Factory</option>
                    <option value="specific" {{ old('factory_scope') === 'specific' ? 'selected' : '' }}>Specific Factory</option>
                    <option value="all" {{ old('factory_scope') === 'all' ? 'selected' : '' }}>All Factories</option>
                </select>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" id="is_public" name="is_public" value="1"
                       {{ old('is_public') ? 'checked' : '' }}
                       class="rounded text-indigo-600">
                <label for="is_public" class="form-label mb-0 cursor-pointer">
                    Public (ผู้ใช้งานทุกคนมองเห็น)
                </label>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                <label class="form-label">Primary App (ถ้ามี)</label>
                <select name="primary_app_id" class="form-select">
                    <option value="">— ไม่ผูกกับ App ใด —</option>
                    @foreach($apps as $a)
                    <option value="{{ $a->id }}" {{ old('primary_app_id') == $a->id ? 'selected' : '' }}>
                        {{ $a->name }}
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">ผูก Dashboard นี้กับ Form App — Checksheet ผูก Dashboard จากหน้าแก้ไข Checksheet Template</p>
            </div>

            <div class="flex space-x-3 pt-2 border-t border-gray-200 dark:border-gray-600">
                <button type="submit" class="btn-primary flex items-center space-x-2">
                    <i class="ti ti-arrow-right"></i><span>สร้างและแก้ไข Layout</span>
                </button>
                <a href="{{ route('dashboards.index') }}" class="btn-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
