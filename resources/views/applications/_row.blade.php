@php
    $isForm       = $type === 'form';
    $icon         = $entry->icon ?? ($isForm ? 'ti-forms' : 'ti-clipboard-list');
    $hasDashboard = !empty($entry->primary_dashboard_id) && $entry->primaryDashboard;

    $openUrl = $isForm
        ? route('submissions.create', $entry)
        : route('checksheets.fill', $entry);
@endphp

<div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50/70 dark:hover:bg-gray-700/20 transition-colors">

    {{-- App icon --}}
    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
        <i class="ti {{ $icon }} text-lg text-indigo-600 dark:text-indigo-400"></i>
    </div>

    {{-- Name + description --}}
    <div class="flex-1 min-w-0">
        <div class="font-medium text-sm leading-tight">{{ $entry->name }}</div>
        @if($entry->description)
        <div class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ $entry->description }}</div>
        @endif
    </div>

    {{-- Type badge --}}
    @if($isForm)
    <span class="hidden sm:inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex-shrink-0">
        Form
    </span>
    @else
    <span class="hidden sm:inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex-shrink-0">
        CS
    </span>
    @endif

    {{-- Open button --}}
    <a href="{{ $openUrl }}"
       class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium btn-primary whitespace-nowrap">
        <i class="ti ti-player-play text-xs"></i>
        {{ $isForm ? 'เปิด' : 'กรอกข้อมูล' }}
    </a>

    {{-- Dashboard button --}}
    @if($hasDashboard)
    <a href="{{ route('dashboards.show', $entry->primaryDashboard) }}"
       title="ดู Dashboard: {{ $entry->primaryDashboard->name }}"
       class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-xl
              bg-gray-100 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30
              text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400
              transition-colors">
        <i class="ti ti-chart-bar text-sm"></i>
    </a>
    @else
    <span title="ยังไม่มี Dashboard"
          class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-xl
                 bg-gray-50 dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed">
        <i class="ti ti-chart-bar text-sm"></i>
    </span>
    @endif

</div>
