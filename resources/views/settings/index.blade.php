@extends('layouts.app')
@section('title', __('menu.settings'))
@section('breadcrumb')
<span>{{ __('menu.settings') }}</span>
@endsection
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-xl font-bold">{{ __('menu.settings') }}</h1>
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @foreach($settings as $group => $items)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden mb-4">
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <h3 class="font-semibold text-sm uppercase text-gray-500 dark:text-gray-400">{{ $group }}</h3>
            </div>
            <div class="p-5 space-y-4">
                @foreach($items as $setting)
                <div>
                    <label class="form-label">{{ $setting->description ?? $setting->key }}</label>
                    <input type="text" name="settings[{{ $setting->key }}]" value="{{ old('settings.' . $setting->key, $setting->value) }}" class="form-input">
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
        <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
    </form>
</div>
@endsection
