@php
    $progress = $assignment->_progress ?? $assignment->submission->progress ?? 0;
    $title    = $assignment->submission->title ?? '#' . $assignment->submission_id;
    $appName  = $assignment->submission->app->name ?? '-';
    $appCat   = $assignment->submission->app->category ?? 'general';
    $isOverdue = $assignment->due_date && $assignment->due_date->isPast()
        && !in_array($assignment->submission->status, ['approved','closed','rejected']);
@endphp
<div class="kanban-card group relative bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl p-3 hover:border-indigo-300 hover:shadow-sm transition-all cursor-pointer mis-card"
     data-assignment-id="{{ $assignment->id }}"
     data-submission-id="{{ $assignment->submission_id }}"
     data-progress="{{ $progress }}"
     @click.prevent="openModal({{ $assignment->submission_id }}, {{ $assignment->id }}, {{ Js::from($title) }}, {{ Js::from($appName) }}, {{ $progress }})"
     x-data>

    <!-- Drag handle + external link row -->
    <div class="flex items-center justify-between mb-2">
        <div class="drag-handle cursor-grab text-gray-300 hover:text-gray-500 p-0.5 -ml-1" @click.stop>
            <i class="ti ti-grip-vertical text-sm"></i>
        </div>
        <div class="flex items-center space-x-1.5">
            @if($isOverdue)
            <span class="flex items-center text-xs text-red-500 font-medium">
                <i class="ti ti-alert-triangle text-xs mr-0.5"></i>Overdue
            </span>
            @endif
            <a href="{{ route('submissions.show', $assignment->submission_id) }}"
               class="text-gray-300 hover:text-indigo-500" @click.stop>
                <i class="ti ti-external-link text-xs"></i>
            </a>
        </div>
    </div>

    <!-- App badge + title -->
    <div class="mb-2">
        <span class="inline-block text-xs px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 font-medium mb-1.5">
            {{ $appName }}
        </span>
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-tight line-clamp-2">{{ $title }}</p>
        <p class="text-xs text-gray-400 mt-0.5">#{{ $assignment->submission_id }}</p>
    </div>

    <!-- Progress bar -->
    <div class="mb-2">
        <div class="flex justify-between text-xs text-gray-400 mb-1">
            <span>{{ app()->getLocale() === 'th' ? 'ความคืบหน้า' : 'Progress' }}</span>
            <span class="font-medium progress-pct
                {{ $progress >= 100 ? 'text-green-600' : ($progress >= 70 ? 'text-indigo-600' : ($progress >= 30 ? 'text-amber-600' : 'text-red-500')) }}">
                {{ $progress }}%
            </span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
            <div class="progress-bar h-1.5 rounded-full transition-all
                {{ $progress >= 100 ? 'bg-green-500' : ($progress >= 70 ? 'bg-indigo-500' : ($progress >= 30 ? 'bg-amber-400' : 'bg-red-400')) }}"
                 style="width: {{ $progress }}%"></div>
        </div>
    </div>

    <!-- Due date -->
    @if($assignment->due_date)
    <div class="flex items-center text-xs {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-400' }}">
        <i class="ti ti-calendar mr-1 text-xs"></i>
        <span>{{ $assignment->due_date->format('d/m/Y') }}</span>
    </div>
    @endif

    <!-- Click hint -->
    <div class="absolute inset-x-0 bottom-0 h-0.5 bg-indigo-500 rounded-b-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
</div>
