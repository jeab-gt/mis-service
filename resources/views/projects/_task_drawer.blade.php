{{-- Task Detail Drawer (slides in from the right) --}}
<div x-show="drawerOpen"
     @keydown.escape.window="closeDrawer()"
     @open-drawer.window="openDrawer($event.detail.taskId)"
     class="fixed inset-0 z-50 flex justify-end"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/30 dark:bg-black/50 backdrop-blur-sm"
         @click="closeDrawer()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    {{-- Drawer panel --}}
    <div class="relative w-full max-w-lg h-full bg-white dark:bg-gray-800 shadow-2xl overflow-y-auto flex flex-col"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        <template x-if="drawerTask">
            <div class="flex flex-col h-full">

                {{-- Drawer header --}}
                <div class="flex items-start gap-3 p-4 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-400 mb-1">Task #<span x-text="drawerTask.id"></span></p>
                        <p class="font-semibold text-base leading-snug" x-text="drawerTask.title"></p>
                    </div>
                    <button @click="closeDrawer()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg flex-shrink-0">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                {{-- Drawer body --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-5">

                    {{-- Status + Priority --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Status</label>
                            <select class="form-select text-sm"
                                    x-model="drawerTask.status"
                                    @change="updateTaskField(drawerTask.id, 'status', drawerTask.status)">
                                <option value="todo">Todo</option>
                                <option value="in_progress">In Progress</option>
                                <option value="review">Review</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Priority</label>
                            <select class="form-select text-sm"
                                    x-model="drawerTask.priority"
                                    @change="updateTaskField(drawerTask.id, 'priority', drawerTask.priority)">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    {{-- Assignee --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Assignee</label>
                        <select class="form-select text-sm"
                                x-model="drawerTask.assignee_id"
                                @change="updateTaskField(drawerTask.id, 'assignee_id', drawerTask.assignee_id)">
                            <option value="">Unassigned</option>
                            <template x-for="u in MEMBER_USERS" :key="u.id">
                                <option :value="u.id" x-text="u.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Start Date</label>
                            <input type="date" class="form-input text-sm"
                                   x-model="drawerTask.start_date"
                                   @change="updateTaskField(drawerTask.id, 'start_date', drawerTask.start_date)">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Due Date</label>
                            <input type="date" class="form-input text-sm"
                                   x-model="drawerTask.due_date"
                                   @change="updateTaskField(drawerTask.id, 'due_date', drawerTask.due_date)">
                        </div>
                    </div>

                    {{-- Hours --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Estimated Hours</label>
                            <input type="number" class="form-input text-sm" min="0" step="0.5"
                                   x-model="drawerTask.estimated_hours"
                                   @change="updateTaskField(drawerTask.id, 'estimated_hours', drawerTask.estimated_hours)">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Actual Hours</label>
                            <input type="number" class="form-input text-sm" readonly :value="drawerTask.actual_hours">
                        </div>
                    </div>

                    {{-- Progress --}}
                    <div x-data="{ logDetail: '', logProgress: drawerTask?.progress_pct ?? 0 }">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">
                            Progress: <span x-text="logProgress"></span>%
                        </label>
                        <input type="range" class="w-full accent-primary" min="0" max="100" step="5"
                               x-model="logProgress">
                        <textarea x-model="logDetail" rows="2" placeholder="Progress detail / note..."
                                  class="form-input text-sm mt-2 w-full"></textarea>
                        <button @click="async () => {
                            if (!logDetail) return;
                            await fetch(`/project-tasks/${drawerTask.id}/progress`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                                body: JSON.stringify({ progress_pct: logProgress, detail: logDetail }),
                            });
                            drawerTask.progress_pct = logProgress;
                            logDetail = '';
                        }"
                               class="mt-2 px-3 py-1.5 text-xs rounded-lg btn-primary">
                            Log Progress
                        </button>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Description</label>
                        <textarea class="form-input text-sm w-full" rows="3"
                                  x-model="drawerTask.description"
                                  @blur="updateTaskField(drawerTask.id, 'description', drawerTask.description)"></textarea>
                    </div>

                    {{-- Checklist --}}
                    <div x-data="{ newCheck: '' }">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 block">
                            Checklist
                            <span class="ml-1" x-show="drawerTask.checklists.length">
                                (<span x-text="drawerTask.checklists.filter(c => c.is_completed).length"></span>/<span x-text="drawerTask.checklists.length"></span>)
                            </span>
                        </label>
                        <div class="space-y-1.5 mb-2">
                            <template x-for="item in drawerTask.checklists" :key="item.id">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="checkbox" :checked="item.is_completed"
                                           @change="async () => {
                                               const r = await fetch(`/project-tasks/checklist/${item.id}`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': CSRF } });
                                               const d = await r.json();
                                               item.is_completed = d.is_completed;
                                           }"
                                           class="rounded">
                                    <span class="text-sm" :class="item.is_completed ? 'line-through text-gray-400' : ''" x-text="item.title"></span>
                                </label>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" x-model="newCheck" placeholder="Add checklist item..."
                                   class="form-input text-sm flex-1"
                                   @keydown.enter.prevent="async () => {
                                       if (!newCheck) return;
                                       const r = await fetch(`/project-tasks/${drawerTask.id}/checklist`, {
                                           method: 'POST',
                                           headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                                           body: JSON.stringify({ title: newCheck }),
                                       });
                                       const d = await r.json();
                                       drawerTask.checklists.push(d.item);
                                       newCheck = '';
                                   }">
                            <button @click="async () => {
                                if (!newCheck) return;
                                const r = await fetch(`/project-tasks/${drawerTask.id}/checklist`, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                                    body: JSON.stringify({ title: newCheck }),
                                });
                                const d = await r.json();
                                drawerTask.checklists.push(d.item);
                                newCheck = '';
                            }" class="px-3 py-1.5 text-xs rounded-lg btn-primary flex-shrink-0">Add</button>
                        </div>
                    </div>

                    {{-- Log Time --}}
                    <div x-data="{ logDate: new Date().toISOString().slice(0,10), logHours: '', logDesc: '', showLog: false }">
                        <button @click="showLog = !showLog"
                                class="text-xs text-primary font-medium flex items-center gap-1 mb-2">
                            <i class="ti ti-clock"></i> Log Time
                        </button>
                        <div x-show="showLog" class="grid grid-cols-2 gap-2">
                            <input type="date" x-model="logDate" class="form-input text-sm">
                            <input type="number" x-model="logHours" placeholder="Hours" min="0.25" step="0.25" class="form-input text-sm">
                            <input type="text" x-model="logDesc" placeholder="Description" class="form-input text-sm col-span-2">
                            <button @click="async () => {
                                const r = await fetch(`/project-tasks/${drawerTask.id}/log-time`, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                                    body: JSON.stringify({ log_date: logDate, hours: parseFloat(logHours), description: logDesc }),
                                });
                                const d = await r.json();
                                if (d.ok) { drawerTask.actual_hours = d.actual_hours; logHours = ''; logDesc = ''; }
                            }" class="col-span-2 px-3 py-1.5 text-xs rounded-lg btn-primary">Save Time Log</button>
                        </div>
                    </div>

                    {{-- Subtasks --}}
                    <div x-show="drawerTask.subtasks && drawerTask.subtasks.length">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 block">
                            Subtasks (<span x-text="drawerTask.subtasks?.length ?? 0"></span>)
                        </label>
                        <div class="space-y-1">
                            <template x-for="sub in (drawerTask.subtasks || [])" :key="sub.id">
                                <div class="flex items-center gap-2 text-sm p-2 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                                    <i class="ti ti-subtask text-gray-400 text-xs"></i>
                                    <span x-text="sub.title" class="flex-1"></span>
                                    <span class="text-xs text-gray-400" x-text="sub.status"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Comments --}}
                    <div x-data="taskComments(drawerTask?.id)">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 block">
                            <i class="ti ti-message mr-1"></i>Comments
                        </label>
                        <div class="space-y-2 mb-3" x-show="comments.length">
                            <template x-for="c in comments" :key="c.id">
                                <div class="flex gap-2">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                         style="background-color: var(--color-primary)"
                                         x-text="(c.user?.name ?? '?').charAt(0).toUpperCase()"></div>
                                    <div class="flex-1">
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-xs font-semibold" x-text="c.user?.name"></span>
                                            <span class="text-xs text-gray-400" x-text="c.created_at"></span>
                                        </div>
                                        <p class="text-sm mt-0.5 whitespace-pre-line" x-text="c.content"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <textarea x-model="newComment" rows="2" placeholder="Add a comment... (@mention to notify)"
                                      class="form-input text-sm flex-1"></textarea>
                            <button @click="postComment()"
                                    class="px-3 py-1.5 text-xs rounded-lg btn-primary self-end flex-shrink-0">
                                Send
                            </button>
                        </div>
                    </div>

                </div>

                {{-- Drawer footer --}}
                <div class="border-t border-gray-100 dark:border-gray-700 p-4 flex justify-between flex-shrink-0">
                    <form :action="`/project-tasks/${drawerTask?.id}`" method="POST"
                          onsubmit="return confirm('Delete this task?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1">
                            <i class="ti ti-trash"></i> Delete Task
                        </button>
                    </form>
                    <button @click="closeDrawer()" class="text-xs text-gray-400 hover:text-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function taskComments(taskId) {
    return {
        comments:   [],
        newComment: '',
        taskId:     taskId,

        async init() {
            // Pre-load comments via AJAX when task id changes
            this.$watch('taskId', id => id && this.load(id));
        },

        async load(id) {
            const taskId = id || this.taskId;
            if (!taskId) return;
            // Comments are embedded in TASK_DATA checklists but not comments; simple placeholder.
            this.comments = [];
        },

        async postComment() {
            if (!this.newComment.trim()) return;
            const r = await fetch(`/project-tasks/${this.taskId}/comments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ content: this.newComment }),
            });
            const d = await r.json();
            this.comments.push(d.comment);
            this.newComment = '';
        },
    };
}
</script>
