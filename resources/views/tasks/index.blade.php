@extends('layouts.app')
@section('title', __('menu.my_tasks'))
@section('breadcrumb')
<span>{{ __('menu.my_tasks') }}</span>
@endsection

@section('content')
<div x-data="kanban()" class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">{{ __('menu.my_tasks') }}</h1>
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ $todo->count() + $inProgress->count() + $done->count() }} {{ app()->getLocale() === 'th' ? 'งานทั้งหมด' : 'total tasks' }}</span>
            <a href="{{ route('tasks.schedule') }}" class="btn-outline text-sm flex items-center space-x-1">
                <i class="ti ti-calendar-stats"></i><span>{{ app()->getLocale() === 'th' ? 'ตารางงาน' : 'Schedule' }}</span>
            </a>
        </div>
    </div>

    <!-- Kanban Columns -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Todo -->
        <div class="flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
            <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800 flex items-center justify-between">
                <h3 class="font-semibold text-blue-700 dark:text-blue-300 flex items-center space-x-2">
                    <i class="ti ti-inbox"></i>
                    <span>To Do <span class="text-sm font-normal" id="todo-count">({{ $todo->count() }})</span></span>
                </h3>
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            </div>
            <div id="col-todo" class="p-3 space-y-3 min-h-[300px] flex-1"
                 data-column="todo">
                @forelse($todo as $a)
                @include('tasks._card', ['assignment' => $a, 'col' => 'todo'])
                @empty
                <div class="kanban-empty flex flex-col items-center justify-center py-10 text-gray-300">
                    <i class="ti ti-inbox text-3xl mb-2"></i>
                    <p class="text-sm">{{ __('common.no_data') }}</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- In Progress -->
        <div class="flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
            <div class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-800 flex items-center justify-between">
                <h3 class="font-semibold text-amber-700 dark:text-amber-300 flex items-center space-x-2">
                    <i class="ti ti-loader"></i>
                    <span>In Progress <span class="text-sm font-normal" id="inprogress-count">({{ $inProgress->count() }})</span></span>
                </h3>
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            </div>
            <div id="col-in-progress" class="p-3 space-y-3 min-h-[300px] flex-1"
                 data-column="in_progress">
                @forelse($inProgress as $a)
                @include('tasks._card', ['assignment' => $a, 'col' => 'in_progress'])
                @empty
                <div class="kanban-empty flex flex-col items-center justify-center py-10 text-gray-300">
                    <i class="ti ti-loader text-3xl mb-2"></i>
                    <p class="text-sm">{{ __('common.no_data') }}</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Done -->
        <div class="flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
            <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-800 flex items-center justify-between">
                <h3 class="font-semibold text-green-700 dark:text-green-300 flex items-center space-x-2">
                    <i class="ti ti-circle-check"></i>
                    <span>Done <span class="text-sm font-normal" id="done-count">({{ $done->count() }})</span></span>
                </h3>
                <span class="w-2 h-2 rounded-full bg-green-400"></span>
            </div>
            <div id="col-done" class="p-3 space-y-3 min-h-[300px] flex-1"
                 data-column="done">
                @forelse($done as $a)
                @include('tasks._card', ['assignment' => $a, 'col' => 'done'])
                @empty
                <div class="kanban-empty flex flex-col items-center justify-center py-10 text-gray-300">
                    <i class="ti ti-circle-check text-3xl mb-2"></i>
                    <p class="text-sm">{{ __('common.no_data') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Daily Log Modal -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         @keydown.escape.window="closeModal">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] flex flex-col z-10">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-600 flex-shrink-0">
                <div>
                    <h2 class="font-bold text-lg" x-text="modalTitle"></h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="modalApp"></p>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Body (scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 space-y-5">
                <!-- Add Log Form -->
                <div class="bg-gray-50 dark:bg-gray-750 rounded-xl p-4 space-y-3">
                    <h3 class="text-sm font-semibold">{{ app()->getLocale() === 'th' ? 'บันทึกความคืบหน้า' : 'Add Progress Log' }}</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs">{{ app()->getLocale() === 'th' ? 'วันที่' : 'Date' }}</label>
                            <input type="date" x-model="logDate" class="form-input text-sm">
                        </div>
                        <div>
                            <label class="form-label text-xs">{{ app()->getLocale() === 'th' ? 'ความคืบหน้า' : 'Progress' }}</label>
                            <div class="flex items-center space-x-2">
                                <input type="number" x-model.number="logProgress" min="0" max="100"
                                       class="form-input text-sm w-20" @input="syncSlider($event.target.value)">
                                <span class="text-xs text-gray-500">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Slider -->
                    <div class="space-y-1">
                        <input type="range" x-model.number="logProgress" min="0" max="100"
                               class="w-full h-2 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>0%</span>
                            <span class="font-medium text-indigo-600" x-text="logProgress + '%'"></span>
                            <span>100%</span>
                        </div>
                    </div>

                    <!-- Progress bar preview -->
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="logProgress >= 100 ? 'bg-green-500' : logProgress >= 70 ? 'bg-indigo-500' : logProgress >= 30 ? 'bg-amber-500' : 'bg-red-400'"
                             :style="`width: ${logProgress}%`"></div>
                    </div>

                    <div>
                        <label class="form-label text-xs">{{ app()->getLocale() === 'th' ? 'รายละเอียดงานวันนี้' : 'Details' }} <span class="text-red-500">*</span></label>
                        <textarea x-model="logDetail" rows="3" class="form-input text-sm"
                                  placeholder="{{ app()->getLocale() === 'th' ? 'อธิบายงานที่ทำวันนี้...' : 'Describe what was done today...' }}"></textarea>
                    </div>

                    <button @click="submitLog"
                            :disabled="saving || !logDetail.trim()"
                            class="w-full btn-primary text-sm flex items-center justify-center space-x-2"
                            :class="{ 'opacity-50 cursor-not-allowed': saving || !logDetail.trim() }">
                        <span x-show="saving"><i class="ti ti-loader animate-spin mr-1"></i></span>
                        <i class="ti ti-device-floppy" x-show="!saving"></i>
                        <span x-text="saving ? '{{ app()->getLocale() === 'th' ? 'กำลังบันทึก...' : 'Saving...' }}' : '{{ app()->getLocale() === 'th' ? 'บันทึก' : 'Save Log' }}'"></span>
                    </button>

                    <p x-show="saveMsg" x-text="saveMsg" class="text-xs text-center text-green-600 font-medium" x-cloak></p>
                </div>

                <!-- Log History -->
                <div>
                    <h3 class="text-sm font-semibold mb-3 flex items-center space-x-2">
                        <i class="ti ti-history text-gray-400"></i>
                        <span>{{ app()->getLocale() === 'th' ? 'ประวัติการบันทึก' : 'Log History' }}</span>
                    </h3>
                    <div class="space-y-2" id="log-history">
                        <template x-for="log in logs" :key="log.log_date + log.progress_pct">
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-bold text-sm"
                                     :class="log.progress_pct >= 100 ? 'bg-green-100 text-green-600' : log.progress_pct >= 70 ? 'bg-indigo-100 text-indigo-600' : log.progress_pct >= 30 ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'">
                                    <span x-text="log.progress_pct + '%'"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300" x-text="log.log_date"></span>
                                        <span class="text-xs text-gray-400" x-text="log.user_name"></span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-200 mt-1" x-text="log.detail"></p>
                                </div>
                            </div>
                        </template>
                        <p x-show="!logs.length" class="text-sm text-gray-400 text-center py-4">{{ app()->getLocale() === 'th' ? 'ยังไม่มีบันทึก' : 'No logs yet' }}</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-gray-200 dark:border-gray-600 p-4 flex items-center justify-between flex-shrink-0">
                <a :href="'/submissions/' + submissionId" target="_blank"
                   class="text-sm text-indigo-500 hover:text-indigo-700 flex items-center space-x-1">
                    <i class="ti ti-external-link text-xs"></i>
                    <span>{{ app()->getLocale() === 'th' ? 'ดู Request' : 'View Request' }}</span>
                </a>
                <button @click="closeModal" class="btn-secondary text-sm">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function kanban() {
    return {
        modalOpen: false,
        submissionId: null,
        assignmentId: null,
        modalTitle: '',
        modalApp: '',
        logDate: new Date().toISOString().split('T')[0],
        logProgress: 0,
        logDetail: '',
        logs: [],
        saving: false,
        saveMsg: '',

        init() {
            this.initSortable('col-todo');
            this.initSortable('col-in-progress');
            this.initSortable('col-done');
        },

        initSortable(colId) {
            const el = document.getElementById(colId);
            if (!el) return;
            Sortable.create(el, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'opacity-40',
                dragClass: 'ring-2 ring-indigo-400 shadow-lg',
                handle: '.drag-handle',
                forceFallback: true,
                onAdd: (evt) => this.onCardMove(evt),
            });
        },

        onCardMove(evt) {
            const card       = evt.item;
            const targetCol  = evt.to.dataset.column;
            const aId        = card.dataset.assignmentId;
            const pct        = parseInt(card.dataset.progress || '0');

            if (targetCol === 'done' && pct < 100) {
                // Revert: move card back
                evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                alert('{{ app()->getLocale() === 'th' ? 'ต้องอัปเดต progress เป็น 100% ก่อนจึงจะย้ายไป Done ได้' : 'Progress must be 100% before moving to Done' }}');
                return;
            }

            fetch(`/tasks/move/${aId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ column: targetCol }),
            }).then(r => r.json()).then(data => {
                if (!data.success) {
                    evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                    alert(data.message || 'Error moving card');
                }
            });
        },

        openModal(submissionId, assignmentId, title, app, progress) {
            this.submissionId = submissionId;
            this.assignmentId = assignmentId;
            this.modalTitle   = title;
            this.modalApp     = app;
            this.logProgress  = progress;
            this.logDate      = new Date().toISOString().split('T')[0];
            this.logDetail    = '';
            this.saveMsg      = '';
            this.logs         = [];
            this.modalOpen    = true;

            this.loadLogs();
        },

        closeModal() {
            this.modalOpen = false;
        },

        loadLogs() {
            fetch(`/tasks/logs/${this.submissionId}`)
                .then(r => r.json())
                .then(data => {
                    this.logs = data.logs || [];
                    if (!this.logProgress && data.progress) {
                        this.logProgress = data.progress;
                    }
                });
        },

        syncSlider(val) { this.logProgress = parseInt(val) || 0; },
        syncInput(val) { this.logProgress = parseInt(val) || 0; },

        submitLog() {
            if (!this.logDetail.trim() || this.saving) return;
            this.saving = true;
            this.saveMsg = '';

            fetch(`/tasks/log/${this.submissionId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    progress_pct: this.logProgress,
                    detail: this.logDetail,
                    log_date: this.logDate,
                }),
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    this.logs = data.logs || [];
                    this.logDetail = '';
                    this.saveMsg = '{{ app()->getLocale() === 'th' ? 'บันทึกสำเร็จ!' : 'Saved successfully!' }}';

                    // Update progress bar on card
                    const card = document.querySelector(`[data-assignment-id="${this.assignmentId}"]`);
                    if (card) {
                        card.dataset.progress = data.progress;
                        const bar = card.querySelector('.progress-bar');
                        const pct = card.querySelector('.progress-pct');
                        if (bar) bar.style.width = data.progress + '%';
                        if (pct) pct.textContent = data.progress + '%';
                    }

                    setTimeout(() => { this.saveMsg = ''; }, 3000);
                }
            }).finally(() => { this.saving = false; });
        },
    };
}
</script>
@endpush
