@extends('layouts.app')
@section('title', 'App Builder')
@section('breadcrumb')
<span>App Builder</span>
@endsection

@section('content')
<script>
window.__appBuilderData = {
    items:     @json($allItems),
    canEdit:   @json($canEdit),
    canDelete: @json($canDelete),
};
</script>
<div x-data="appBuilder()"
     class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">App Builder</h1>
        @can('app.create')
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.form-templates.index') }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-forms"></i><span>Form Library</span>
            </a>
            <a href="{{ route('admin.flows.index') }}" class="btn-secondary flex items-center space-x-1 text-sm">
                <i class="ti ti-git-branch"></i><span>Flow Library</span>
            </a>
            <button @click="showCreateModal = true" class="btn-primary flex items-center space-x-2">
                <i class="ti ti-plus"></i><span>สร้างใหม่</span>
            </button>
        </div>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Search + Filter bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-3 py-2.5 flex flex-wrap gap-2.5 items-center">

        {{-- Search --}}
        <div class="relative flex-1 min-w-48">
            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
            <input type="text" x-model="search" placeholder="ค้นหาชื่อ App / Checksheet..."
                   class="w-full pl-8 pr-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-700 dark:text-white placeholder-gray-400">
        </div>

        {{-- Type filter pills --}}
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 flex-shrink-0">
            <button @click="filterType = 'all'"
                    :class="filterType === 'all'
                        ? 'bg-white dark:bg-gray-600 shadow text-gray-800 dark:text-white'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="px-3 py-1 rounded-md text-xs font-medium transition-all whitespace-nowrap">
                ทั้งหมด
            </button>
            <button @click="filterType = 'form'"
                    :class="filterType === 'form'
                        ? 'bg-white dark:bg-gray-600 shadow text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="px-3 py-1 rounded-md text-xs font-medium transition-all whitespace-nowrap">
                Form App
            </button>
            <button @click="filterType = 'checksheet'"
                    :class="filterType === 'checksheet'
                        ? 'bg-white dark:bg-gray-600 shadow text-teal-600 dark:text-teal-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="px-3 py-1 rounded-md text-xs font-medium transition-all whitespace-nowrap">
                Checksheet
            </button>
        </div>

        {{-- Category dropdown --}}
        <select x-model="filterCategory"
                class="text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:text-white">
            <option value="all">ทุกหมวดหมู่</option>
            <template x-for="cat in categoryNames" :key="cat">
                <option :value="cat" x-text="cat"></option>
            </template>
        </select>

        {{-- Sort --}}
        <select x-model="sortBy"
                class="text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:text-white">
            <option value="latest">ล่าสุด</option>
            <option value="name">ชื่อ A-Z</option>
            <option value="submissions">Submissions มากสุด</option>
        </select>

        {{-- Count --}}
        <span class="text-xs text-gray-400 flex-shrink-0 whitespace-nowrap">
            <span x-text="totalVisible"></span> รายการ
        </span>

        {{-- Clear filters --}}
        <button x-show="search || filterType !== 'all' || filterCategory !== 'all'"
                @click="search = ''; filterType = 'all'; filterCategory = 'all';"
                class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex items-center gap-1 flex-shrink-0">
            <i class="ti ti-x text-xs"></i>ล้าง
        </button>
    </div>

    {{-- Grouped list --}}
    <div class="space-y-3">

        {{-- No apps at all --}}
        <template x-if="items.length === 0">
            <div class="text-center py-20 text-gray-400">
                <i class="ti ti-tool text-6xl mb-4 block opacity-30"></i>
                <p class="text-lg font-medium">ยังไม่มี App หรือ Checksheet</p>
                <button @click="showCreateModal = true" class="mt-4 btn-primary text-sm">
                    <i class="ti ti-plus mr-1"></i>สร้างตัวแรก
                </button>
            </div>
        </template>

        {{-- No results from filter --}}
        <template x-if="items.length > 0 && totalVisible === 0">
            <div class="text-center py-16 text-gray-400">
                <i class="ti ti-search-off text-5xl mb-3 block opacity-30"></i>
                <p class="font-medium">ไม่พบรายการที่ตรงกับการค้นหา</p>
                <button @click="search = ''; filterType = 'all'; filterCategory = 'all';"
                        class="mt-3 text-sm text-indigo-500 hover:text-indigo-700">
                    ล้างตัวกรอง
                </button>
            </div>
        </template>

        {{-- Category groups --}}
        <template x-for="catKey in groupKeys" :key="catKey">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">

                {{-- Category header --}}
                <button @click="toggle(catKey)"
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <i class="ti ti-chevron-right text-gray-400 text-sm transition-transform duration-200"
                       :class="isOpen(catKey) ? 'rotate-90' : ''"></i>
                    <i class="ti ti-folder-filled text-indigo-400 text-sm"></i>
                    <span class="font-semibold text-sm" x-text="catKey"></span>
                    <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full"
                          x-text="grouped[catKey].length"></span>
                </button>

                {{-- Item rows --}}
                <div x-show="isOpen(catKey)"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="border-t border-gray-50 dark:border-gray-700 divide-y divide-gray-50 dark:divide-gray-700/50">
                    <template x-for="item in grouped[catKey]" :key="item.type + '-' + item.id">
                        <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition-colors">

                            {{-- Icon --}}
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :class="item.type === 'form'
                                    ? 'bg-indigo-100 dark:bg-indigo-900/40'
                                    : 'bg-teal-100 dark:bg-teal-900/40'">
                                <i class="text-sm"
                                   :class="'ti ' + item.icon + (item.type === 'form'
                                        ? ' text-indigo-600 dark:text-indigo-400'
                                        : ' text-teal-600 dark:text-teal-400')"></i>
                            </div>

                            {{-- Name + meta --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-sm truncate dark:text-gray-100" x-text="item.name"></span>
                                    <span x-show="!item.is_active"
                                          class="flex-shrink-0 text-xs bg-red-100 dark:bg-red-900/30 text-red-500 px-1.5 py-0.5 rounded-full">
                                        Inactive
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    <template x-if="item.type === 'form'">
                                        <span x-text="(item.submissions_count ?? 0) + ' submissions'"></span>
                                    </template>
                                    <template x-if="item.type === 'checksheet'">
                                        <span x-text="(item.parameters_count ?? 0) + ' params · ' + (item.records_count ?? 0) + ' records'"></span>
                                    </template>
                                </div>
                            </div>

                            {{-- Type badge --}}
                            <span class="hidden sm:inline-flex flex-shrink-0 text-xs font-medium px-2 py-0.5 rounded-full"
                                  :class="item.type === 'form'
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                    : 'bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400'"
                                  x-text="item.type === 'form' ? 'Form' : 'Checksheet'"></span>

                            {{-- Action buttons --}}
                            <div class="flex items-center gap-1.5 flex-shrink-0">

                                {{-- Form App actions --}}
                                <template x-if="item.type === 'form'">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="canEdit">
                                            <a :href="item.edit_url"
                                               class="inline-flex items-center gap-1 text-xs btn-outline py-1 px-2">
                                                <i class="ti ti-settings text-xs"></i><span>Settings</span>
                                            </a>
                                        </template>
                                        <template x-if="item.form_url">
                                            <a :href="item.form_url"
                                               class="inline-flex items-center gap-1 text-xs text-indigo-500 hover:text-indigo-700 border border-indigo-100 dark:border-indigo-800 rounded-lg px-2 py-1 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors">
                                                <i class="ti ti-layout-grid-add text-xs"></i><span>Form</span>
                                            </a>
                                        </template>
                                        <template x-if="item.flow_url">
                                            <a :href="item.flow_url"
                                               class="inline-flex items-center gap-1 text-xs text-blue-500 hover:text-blue-700 border border-blue-100 dark:border-blue-800 rounded-lg px-2 py-1 hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
                                                <i class="ti ti-git-branch text-xs"></i><span>Flow</span>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                {{-- Checksheet actions --}}
                                <template x-if="item.type === 'checksheet'">
                                    <div class="flex items-center gap-1.5">
                                        <a :href="item.edit_url"
                                           class="inline-flex items-center gap-1 text-xs btn-outline py-1 px-2">
                                            <i class="ti ti-settings text-xs"></i><span>Settings</span>
                                        </a>
                                        <a :href="item.builder_url"
                                           class="inline-flex items-center gap-1 text-xs text-teal-600 hover:text-teal-800 border border-teal-200 dark:border-teal-800 rounded-lg px-2 py-1 hover:border-teal-400 dark:hover:border-teal-600 transition-colors">
                                            <i class="ti ti-layout-grid-add text-xs"></i><span>Builder</span>
                                        </a>
                                        <a :href="item.records_url"
                                           class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1 hover:border-gray-400 transition-colors">
                                            <i class="ti ti-table text-xs"></i><span>Records</span>
                                        </a>
                                    </div>
                                </template>

                                {{-- Delete --}}
                                <template x-if="canDelete">
                                    <button @click="deleteItem(item.delete_url, item.delete_label)"
                                            class="p-1.5 rounded text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors ml-1">
                                        <i class="ti ti-trash text-xs"></i>
                                    </button>
                                </template>

                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Create modal --}}
    <div x-show="showCreateModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showCreateModal = false"
         @keydown.escape.window="showCreateModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         style="display:none;">
        <div x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white dark:bg-gray-800 rounded-2xl p-8 w-full max-w-md shadow-2xl">

            <h2 class="text-xl font-bold mb-1">สร้างใหม่</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">เลือกประเภทที่ต้องการสร้าง</p>

            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('admin.apps.create') }}"
                   class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 dark:border-gray-600
                          rounded-xl hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                          transition-all cursor-pointer group">
                    <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center
                                group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800/60 transition-colors">
                        <i class="ti ti-file-text text-2xl text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Form App</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-snug">
                            สร้างฟอร์มขอ/แจ้ง<br>พร้อม Approval Flow
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.apps.create', ['type' => 'checksheet']) }}"
                   class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 dark:border-gray-600
                          rounded-xl hover:border-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20
                          transition-all cursor-pointer group">
                    <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/40 rounded-xl flex items-center justify-center
                                group-hover:bg-teal-200 dark:group-hover:bg-teal-800/60 transition-colors">
                        <i class="ti ti-clipboard-list text-2xl text-teal-600 dark:text-teal-400"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Checksheet</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-snug">
                            สร้างแบบฟอร์มบันทึก<br>ข้อมูลซ้ำๆ รายวัน/กะ
                        </div>
                    </div>
                </a>
            </div>

            <button @click="showCreateModal = false"
                    class="mt-6 w-full py-2.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200
                           rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                ยกเลิก
            </button>
        </div>
    </div>

    {{-- Hidden delete form (CSRF-safe) --}}
    <form id="appBuilderDeleteForm" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('appBuilder', () => {
        const d = window.__appBuilderData || {};
        return {
        items:     d.items     || [],
        canEdit:   d.canEdit   ?? false,
        canDelete: d.canDelete ?? false,
        search: '',
        filterType: 'all',
        filterCategory: 'all',
        sortBy: 'latest',
        openCats: {},
        showCreateModal: false,

        get categoryNames() {
            return [...new Set(this.items.map(i => i.category_name))].sort((a, b) => {
                if (a === 'ไม่ระบุหมวดหมู่') return 1;
                if (b === 'ไม่ระบุหมวดหมู่') return -1;
                return a.localeCompare(b, 'th');
            });
        },

        get filteredItems() {
            let result = this.items.filter(item => {
                const matchSearch   = !this.search || item.name.toLowerCase().includes(this.search.toLowerCase());
                const matchType     = this.filterType === 'all' || item.type === this.filterType;
                const matchCategory = this.filterCategory === 'all' || item.category_name === this.filterCategory;
                return matchSearch && matchType && matchCategory;
            });

            if (this.sortBy === 'name') {
                result = [...result].sort((a, b) => a.name.localeCompare(b.name, 'th'));
            } else if (this.sortBy === 'submissions') {
                result = [...result].sort((a, b) => (b.submissions_count ?? 0) - (a.submissions_count ?? 0));
            }
            // 'latest' keeps original server order

            return result;
        },

        get grouped() {
            const groups = {};
            for (const item of this.filteredItems) {
                if (!groups[item.category_name]) groups[item.category_name] = [];
                groups[item.category_name].push(item);
            }
            return groups;
        },

        get groupKeys() {
            return Object.keys(this.grouped).sort((a, b) => {
                if (a === 'ไม่ระบุหมวดหมู่') return 1;
                if (b === 'ไม่ระบุหมวดหมู่') return -1;
                return a.localeCompare(b, 'th');
            });
        },

        get totalVisible() {
            return this.filteredItems.length;
        },

        isOpen(cat) {
            return this.openCats[cat] !== false;
        },

        toggle(cat) {
            this.openCats[cat] = !this.isOpen(cat);
        },

        deleteItem(url, label) {
            if (!confirm(label)) return;
            const form = document.getElementById('appBuilderDeleteForm');
            form.action = url;
            form.submit();
        },
        }; // end return object
    }); // end Alpine.data
});
</script>
@endpush
