@extends('layouts.app')
@section('title', __('menu.roles'))
@section('breadcrumb')
<span>{{ __('menu.roles') }}</span>
@endsection
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ __('menu.roles') }}</h1>
        <a href="{{ route('admin.roles.create') }}" class="btn-primary flex items-center space-x-2">
            <i class="ti ti-plus"></i><span>{{ __('common.create') }}</span>
        </a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($roles as $role)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-gray-100">{{ $role->name }}</h3>
                    <p class="text-sm text-gray-400 mt-1">{{ $role->permissions_count }} permissions</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="p-1.5 text-blue-500 hover:text-blue-700">
                        <i class="ti ti-edit"></i>
                    </a>
                    @if(!in_array($role->name, ['super_admin','it_manager','it_staff','team_lead','requester']))
                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                          onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-red-400 hover:text-red-600"><i class="ti ti-trash"></i></button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
