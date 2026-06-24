@extends('layouts.app')
@section('title', 'App Categories')
@section('breadcrumb')
<span>Admin</span>
<i class="ti ti-chevron-right text-xs mx-1"></i>
<span>App Categories</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ editing: null, form: {} }">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">App Categories</h1>
        <a href="{{ route('applications.index') }}" target="_blank"
           class="btn-secondary text-sm flex items-center space-x-1">
            <i class="ti ti-external-link text-sm"></i><span>{{ __('category.view_applications') }}</span>
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Add Category --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600 p-6">
        <h2 class="font-semibold mb-4">{{ __('category.add_new') }}</h2>
        <form method="POST" action="{{ route('admin.app-categories.store') }}" class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="form-label">{{ __('category.name_th') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name_th" class="form-input" required placeholder="IT Services">
            </div>
            <div>
                <label class="form-label">{{ __('category.name_en') }}</label>
                <input type="text" name="name_en" class="form-input" placeholder="IT Services">
            </div>
            <div>
                <label class="form-label">Icon (Tabler)</label>
                <input type="text" name="icon" class="form-input font-mono" placeholder="ti-device-desktop" value="ti-category">
            </div>
            <div>
                <label class="form-label">Color</label>
                <select name="color" class="form-select">
                    @foreach(['indigo','blue','green','yellow','red','purple','pink','orange','teal','cyan'] as $c)
                    <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" value="0" min="0">
            </div>
            <div class="md:col-start-4 flex items-end">
                <button type="submit" class="btn-primary w-full">
                    <i class="ti ti-plus mr-1"></i>{{ __('common.add') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Category List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600 overflow-hidden">
        @if($categories->isEmpty())
        <div class="p-10 text-center text-gray-400">
            <i class="ti ti-category-2 text-4xl block mb-2"></i>
            <p>{{ __('category.no_categories') }}</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ __('category.category') }}</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Icon / Color</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Apps</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Checksheets</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Order</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                @foreach($categories as $cat)
                <tr x-show="editing !== {{ $cat->id }}">
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $cat->name_th }}</div>
                        @if($cat->name_en)
                        <div class="text-xs text-gray-400">{{ $cat->name_en }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 rounded-lg bg-{{ $cat->color }}-100 dark:bg-{{ $cat->color }}-900/30 flex items-center justify-center">
                                <i class="ti {{ $cat->icon }} text-{{ $cat->color }}-600 dark:text-{{ $cat->color }}-400 text-sm"></i>
                            </div>
                            <span class="font-mono text-xs text-gray-400">{{ $cat->icon }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">{{ $cat->apps_count }}</td>
                    <td class="px-4 py-3 text-center">{{ $cat->checksheets_count }}</td>
                    <td class="px-4 py-3 text-center text-gray-400">{{ $cat->sort_order }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end space-x-2">
                            <button @click="editing = {{ $cat->id }}; form = { name_th: '{{ addslashes($cat->name_th) }}', name_en: '{{ addslashes($cat->name_en ?? '') }}', icon: '{{ $cat->icon }}', color: '{{ $cat->color }}', sort_order: {{ $cat->sort_order }} }"
                                    class="text-gray-400 hover:text-indigo-600 transition-colors" title="{{ __('common.edit') }}">
                                <i class="ti ti-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.app-categories.destroy', $cat) }}"
                                  data-confirm="{{ __('category.delete_confirm') }} {{ $cat->name_th }}"
                                  onsubmit="return confirm(this.dataset.confirm)">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="{{ __('common.delete') }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                {{-- Edit row --}}
                <tr x-show="editing === {{ $cat->id }}" class="bg-indigo-50/50 dark:bg-indigo-900/10">
                    <td colspan="6" class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.app-categories.update', $cat) }}"
                              class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
                            @csrf @method('PUT')
                            <div>
                                <label class="form-label text-xs">{{ __('category.name_th_short') }} *</label>
                                <input type="text" name="name_th" x-model="form.name_th" class="form-input text-sm" required>
                            </div>
                            <div>
                                <label class="form-label text-xs">{{ __('category.name_en_short') }}</label>
                                <input type="text" name="name_en" x-model="form.name_en" class="form-input text-sm">
                            </div>
                            <div>
                                <label class="form-label text-xs">Icon</label>
                                <input type="text" name="icon" x-model="form.icon" class="form-input text-sm font-mono">
                            </div>
                            <div>
                                <label class="form-label text-xs">Color</label>
                                <select name="color" x-model="form.color" class="form-select text-sm">
                                    @foreach(['indigo','blue','green','yellow','red','purple','pink','orange','teal','cyan'] as $c)
                                    <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">Order</label>
                                <input type="number" name="sort_order" x-model="form.sort_order" class="form-input text-sm" min="0">
                            </div>
                            <div class="flex space-x-2 md:col-span-5">
                                <button type="submit" class="btn-primary text-sm">{{ __('common.save') }}</button>
                                <button type="button" @click="editing = null" class="btn-secondary text-sm">{{ __('common.cancel') }}</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>
@endsection
