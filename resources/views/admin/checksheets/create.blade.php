@extends('layouts.app')
@section('title', 'สร้าง Checksheet Template')
@section('breadcrumb')
<span>Admin</span>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<a href="{{ route('admin.checksheets.index') }}" class="hover:text-indigo-500">Checksheet Templates</a>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>สร้างใหม่</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-xl font-bold">สร้าง Checksheet Template</h1>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form method="POST" action="{{ route('admin.checksheets.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label">ชื่อ Template <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required
                       placeholder="เช่น บันทึกอุณหภูมิ Line A">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label">คำอธิบาย</label>
                <textarea name="description" rows="3" class="form-input"
                          placeholder="อธิบายวัตถุประสงค์ของ Checksheet นี้...">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="form-label">ความถี่ในการกรอก <span class="text-red-500">*</span></label>
                <select name="frequency" class="form-select" required>
                    @foreach(['realtime' => 'Real-time', 'hourly' => 'ทุกชั่วโมง', 'daily' => 'ทุกวัน', 'weekly' => 'ทุกสัปดาห์', 'monthly' => 'ทุกเดือน'] as $value => $label)
                    <option value="{{ $value }}" {{ old('frequency', 'daily') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Flow (ถ้ามี)</label>
                <select name="flow_id" class="form-select">
                    <option value="">— ไม่มี Flow —</option>
                    @foreach($flows as $flow)
                    <option value="{{ $flow->id }}" {{ old('flow_id') == $flow->id ? 'selected' : '' }}>{{ $flow->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">เชื่อมกับ Approval Flow (Optional)</p>
            </div>

            <div>
                <label class="form-label">Factory Scope <span class="text-red-500">*</span></label>
                <div class="space-y-2 mt-2">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="radio" name="factory_scope" value="own_factory"
                               {{ old('factory_scope', 'own_factory') === 'own_factory' ? 'checked' : '' }}
                               class="text-indigo-600">
                        <div>
                            <span class="text-sm font-medium">Own Factory</span>
                            <p class="text-xs text-gray-400">แต่ละ Factory กรอกข้อมูลของตนเอง</p>
                        </div>
                    </label>
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="radio" name="factory_scope" value="all_factories"
                               {{ old('factory_scope') === 'all_factories' ? 'checked' : '' }}
                               class="text-indigo-600">
                        <div>
                            <span class="text-sm font-medium">All Factories</span>
                            <p class="text-xs text-gray-400">ใช้ร่วมกันทุก Factory</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="rounded text-indigo-600">
                <label for="is_active" class="form-label mb-0 cursor-pointer">เปิดใช้งาน (Active)</label>
            </div>

            <div class="flex space-x-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <button type="submit" class="btn-primary flex items-center space-x-2">
                    <i class="ti ti-arrow-right"></i><span>บันทึกและไปที่ Builder</span>
                </button>
                <a href="{{ route('admin.checksheets.index') }}" class="btn-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
