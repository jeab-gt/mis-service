{{-- Shared tab navigation for report pages --}}
<div class="flex items-center bg-white dark:bg-gray-800 rounded-xl border border-gray-300 dark:border-gray-600 overflow-hidden shadow-sm w-fit">
    <a href="{{ route('reports.daily') }}"
       class="px-4 py-2 text-sm font-medium flex items-center space-x-1.5
              {{ request()->routeIs('reports.daily') ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
        <i class="ti ti-calendar-day"></i><span>Daily</span>
    </a>
    <a href="{{ route('reports.weekly') }}"
       class="px-4 py-2 text-sm font-medium flex items-center space-x-1.5 border-l border-gray-200 dark:border-gray-600
              {{ request()->routeIs('reports.weekly') ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
        <i class="ti ti-calendar-week"></i><span>Weekly</span>
    </a>
    <a href="{{ route('reports.monthly') }}"
       class="px-4 py-2 text-sm font-medium flex items-center space-x-1.5 border-l border-gray-200 dark:border-gray-600
              {{ request()->routeIs('reports.monthly') ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
        <i class="ti ti-calendar-month"></i><span>Monthly</span>
    </a>
</div>
