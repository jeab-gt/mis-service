@php
    $isForm       = $type === 'form';
    $icon         = $entry->icon ?? ($isForm ? 'ti-forms' : 'ti-clipboard-list');
    $hasDashboard = !empty($entry->dashboard_id) && $entry->dashboard;

    if ($isForm) {
        $openUrl = route('submissions.create', $entry);
    } else {
        $openUrl = route('checksheets.fill', $entry);
    }
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600 shadow-sm hover:shadow-md transition-shadow flex flex-col mis-card">
    <div class="p-5 flex-1">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                <i class="ti {{ $icon }} text-xl text-indigo-600 dark:text-indigo-400"></i>
            </div>
            @if($isForm)
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                Form
            </span>
            @else
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                Checksheet
            </span>
            @endif
        </div>

        <h3 class="font-semibold text-sm leading-snug mb-1">{{ $entry->name }}</h3>

        @if($entry->description)
        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $entry->description }}</p>
        @endif
    </div>

    <div class="px-5 pb-4 flex items-center space-x-2">
        <a href="{{ $openUrl }}"
           class="flex-1 text-center text-sm font-medium py-2 px-3 rounded-xl btn-primary">
            <i class="ti ti-player-play text-sm mr-1"></i>เปิด
        </a>

        @if($hasDashboard)
        <a href="{{ route('dashboards.show', $entry->dashboard) }}"
           title="ดู Dashboard"
           class="flex items-center justify-center w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            <i class="ti ti-chart-bar text-base"></i>
        </a>
        @else
        <span title="ยังไม่มี Dashboard"
              class="flex items-center justify-center w-9 h-9 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
            <i class="ti ti-chart-bar text-base"></i>
        </span>
        @endif
    </div>
</div>
