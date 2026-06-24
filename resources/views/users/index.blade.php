@extends('layouts.app')
@section('title', __('menu.users'))

@section('breadcrumb')
<span>{{ __('menu.users') }}</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ __('menu.users') }}</h1>
        @can('user.create')
        <a href="{{ route('admin.users.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-user-plus"></i><span>{{ __('common.create') }}</span>
        </a>
        @endcan
    </div>

    <form method="GET" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-300 dark:border-gray-600">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input"
                   placeholder="{{ app()->getLocale() === 'th' ? 'ค้นหาชื่อ อีเมล รหัสพนักงาน...' : 'Search name, email, employee code...' }}">
            <select name="factory_id" class="form-select">
                <option value="">{{ app()->getLocale() === 'th' ? 'ทุก Factory' : 'All Factories' }}</option>
                @foreach($factories as $f)
                <option value="{{ $f->id }}" {{ request('factory_id') == $f->id ? 'selected' : '' }}>{{ $f->name_th }}</option>
                @endforeach
            </select>
            <select name="role" class="form-select">
                <option value="">{{ app()->getLocale() === 'th' ? 'ทุก Role' : 'All Roles' }}</option>
                @foreach($roles as $r)
                <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">{{ app()->getLocale() === 'th' ? 'ทุกสถานะ' : 'All Status' }}</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ app()->getLocale() === 'th' ? 'ใช้งาน' : 'Active' }}</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ app()->getLocale() === 'th' ? 'ปิดใช้' : 'Inactive' }}</option>
            </select>
        </div>
        <div class="flex space-x-2 mt-3">
            <button type="submit" class="btn-primary text-sm">{{ __('common.search') }}</button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary text-sm">{{ __('common.reset') }}</a>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">{{ app()->getLocale() === 'th' ? 'ชื่อ / รหัส' : 'Name / Code' }}</th>
                        <th class="px-4 py-3 text-left font-semibold">Role</th>
                        <th class="px-4 py-3 text-left font-semibold hidden md:table-cell">Factory</th>
                        <th class="px-4 py-3 text-left font-semibold hidden lg:table-cell">Section</th>
                        <th class="px-4 py-3 text-left font-semibold">{{ app()->getLocale() === 'th' ? 'สถานะ' : 'Status' }}</th>
                        <th class="px-4 py-3 text-left font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->employee_code }} {{ $user->email ? '· ' . $user->email : '' }}</p>
                                    @if($user->is_parent_factory)
                                    <span class="text-xs text-purple-500"><i class="ti ti-star-filled text-xs"></i> Cross-Factory</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @foreach($user->roles as $r)
                            <span class="inline-block text-xs bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 rounded-full px-2 py-0.5 mr-1">{{ $r->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
                            @if($user->factory)
                            <span class="flex items-center space-x-1">
                                <i class="ti ti-building-factory-2 text-xs text-indigo-400"></i>
                                <span>{{ $user->factory->name_th }}</span>
                            </span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden lg:table-cell">{{ $user->section?->name_th ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $user->is_active ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ $user->is_active ? (app()->getLocale() === 'th' ? 'ใช้งาน' : 'Active') : (app()->getLocale() === 'th' ? 'ปิดใช้' : 'Inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                @can('user.edit')
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-500 hover:text-blue-700">
                                    <i class="ti ti-edit text-sm"></i>
                                </a>
                                @endcan
                                @can('user.delete')
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                      onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600">
                                        <i class="ti ti-trash text-sm"></i>
                                    </button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">{{ __('common.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-600">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
