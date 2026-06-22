<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      x-data="appLayout()"
      :class="{ 'dark': darkMode }"
      data-theme="{{ auth()->user()->theme_preference ?? 'default' }}"
      :data-theme="theme"
      @fullscreenchange.window="onFullscreenChange()"
      x-cloak>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — IT MIS System</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="flex-shrink-0 transition-all duration-300 overflow-hidden"
           :class="(sidebarOpen && !isFullscreen) ? 'w-64' : 'w-16'">
        <div class="flex flex-col h-full sidebar-bg-theme dark:bg-gray-800 text-white">

            {{-- Logo row --}}
            <div class="flex items-center h-16 px-4 border-b border-white/10 dark:border-gray-700 flex-shrink-0">
                <i class="ti ti-activity-heartbeat text-2xl text-white/60 flex-shrink-0"></i>
                <span class="ml-3 font-bold text-lg whitespace-nowrap"
                      x-show="sidebarOpen && !isFullscreen" x-transition>IT MIS</span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-2">
                <a href="{{ route('dashboard') }}"
                   title="{{ __('menu.dashboard') }}"
                   class="{{ request()->routeIs('dashboard') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-dashboard text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.dashboard') }}</span>
                </a>

                <a href="{{ route('applications.index') }}"
                   title="Applications"
                   class="{{ request()->routeIs('applications.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-layout-grid text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">Applications</span>
                </a>

                @can('submission.view')
                <div x-data="{ open: {{ request()->routeIs('submissions.*') || request()->routeIs('tasks.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            title="{{ __('menu.requests') }}"
                            class="sidebar-link w-full text-left">
                        <i class="ti ti-apps text-xl flex-shrink-0"></i>
                        <span class="ml-3 flex-1 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.requests') }}</span>
                        <i class="ti ti-chevron-down text-xs transition-transform"
                           :class="open ? 'rotate-180' : ''"
                           x-show="sidebarOpen && !isFullscreen"></i>
                    </button>
                    <div x-show="open" class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('submissions.index') }}"
                           title="{{ __('menu.all_requests') }}"
                           class="{{ request()->routeIs('submissions.index') ? 'sidebar-active' : 'sidebar-link' }}">
                            <i class="ti ti-list text-base flex-shrink-0"></i>
                            <span class="ml-3 text-sm whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.all_requests') }}</span>
                        </a>
                        <a href="{{ route('tasks.index') }}"
                           title="{{ __('menu.my_tasks') }}"
                           class="{{ request()->routeIs('tasks.*') ? 'sidebar-active' : 'sidebar-link' }}">
                            <i class="ti ti-checklist text-base flex-shrink-0"></i>
                            <span class="ml-3 text-sm whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.my_tasks') }}</span>
                        </a>
                    </div>
                </div>
                @endcan

                @can('report.view')
                <a href="{{ route('reports.index') }}"
                   title="{{ __('menu.reports') }}"
                   class="{{ request()->routeIs('reports.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-chart-bar text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.reports') }}</span>
                </a>
                @endcan

                {{-- Checksheets --}}
                <div x-data="{ open: {{ request()->routeIs('checksheets.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            title="Checksheet"
                            class="sidebar-link w-full text-left">
                        <i class="ti ti-clipboard-list text-xl flex-shrink-0"></i>
                        <span class="ml-3 flex-1 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">Checksheet</span>
                        <i class="ti ti-chevron-down text-xs transition-transform"
                           :class="open ? 'rotate-180' : ''"
                           x-show="sidebarOpen && !isFullscreen"></i>
                    </button>
                    <div x-show="open" class="pl-4 space-y-1 mt-1">
                        <a href="{{ route('checksheets.index') }}"
                           title="กรอกข้อมูล"
                           class="{{ request()->routeIs('checksheets.index') ? 'sidebar-active' : 'sidebar-link' }}">
                            <i class="ti ti-list text-base flex-shrink-0"></i>
                            <span class="ml-3 text-sm whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">กรอกข้อมูล</span>
                        </a>
                    </div>
                </div>

                {{-- Dashboards --}}
                <a href="{{ route('dashboards.index') }}"
                   title="Dashboard"
                   class="{{ request()->routeIs('dashboards.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-layout-dashboard text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">Dashboard</span>
                </a>

                @canany(['user.view', 'master.view', 'app.view', 'setting.view'])
                <div class="pt-3 pb-1" x-show="sidebarOpen && !isFullscreen">
                    <p class="text-xs text-white/40 uppercase tracking-wider px-2">Admin</p>
                </div>
                @endcanany

                {{-- Master Management: only super_admin OR (is_parent_factory it_manager) --}}
                @if(auth()->user()->hasRole('super_admin') || (auth()->user()->is_parent_factory && auth()->user()->hasRole('it_manager')))
                <a href="{{ route('admin.masters.index') }}"
                   title="{{ __('menu.masters') }}"
                   class="{{ request()->routeIs('admin.masters.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-sitemap text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.masters') }}</span>
                </a>
                @endif

                @can('user.view')
                <a href="{{ route('admin.users.index') }}"
                   title="{{ __('menu.users') }}"
                   class="{{ request()->routeIs('admin.users.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-users text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.users') }}</span>
                </a>
                <a href="{{ route('admin.roles.index') }}"
                   title="{{ __('menu.roles') }}"
                   class="{{ request()->routeIs('admin.roles.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-shield-lock text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.roles') }}</span>
                </a>
                @endcan

                @can('app.view')
                <a href="{{ route('admin.apps.index') }}"
                   title="{{ __('menu.app_builder') }}"
                   class="{{ request()->routeIs('admin.apps.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-device-desktop-code text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.app_builder') }}</span>
                </a>
                <a href="{{ route('admin.app-categories.index') }}"
                   title="App Categories"
                   class="{{ request()->routeIs('admin.app-categories.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-category-2 text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">App Categories</span>
                </a>
                @endcan

                @can('app.view')
                <a href="{{ route('admin.checksheets.index') }}"
                   title="Checksheet Templates"
                   class="{{ request()->routeIs('admin.checksheets.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-clipboard-check text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">Checksheet Templates</span>
                </a>
                <a href="{{ route('admin.data-management.index') }}"
                   title="Data Management"
                   class="{{ request()->routeIs('admin.data-management.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-database text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">Data Management</span>
                </a>
                @endcan

                @can('setting.view')
                <a href="{{ route('admin.settings.index') }}"
                   title="{{ __('menu.settings') }}"
                   class="{{ request()->routeIs('admin.settings.*') ? 'sidebar-active' : 'sidebar-link' }}">
                    <i class="ti ti-settings text-xl flex-shrink-0"></i>
                    <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen && !isFullscreen">{{ __('menu.settings') }}</span>
                </a>
                @endcan
            </nav>

            {{-- Fullscreen exit button — always accessible, visible only in fullscreen --}}
            <div class="flex-shrink-0 border-t border-white/10 dark:border-gray-700 p-2"
                 x-show="isFullscreen">
                <button @click="toggleFullscreen()"
                        title="ออกจากเต็มจอ"
                        class="w-full flex items-center justify-center p-2 rounded-xl text-white/60 hover:text-white hover:bg-white/10 transition-colors">
                    <i class="ti ti-arrows-minimize text-xl"></i>
                </button>
            </div>

        </div>
    </aside>

    <!-- Main -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
        <!-- Topbar (hidden in fullscreen) -->
        <header class="flex items-center h-16 px-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm flex-shrink-0"
                x-show="!isFullscreen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2">
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="ti ti-menu-2 text-xl"></i>
            </button>

            <nav class="ml-4 flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400 hidden sm:flex">
                @yield('breadcrumb')
            </nav>

            <div class="flex items-center ml-auto space-x-2">
                <!-- Bell with dropdown -->
                @php $unread = auth()->user()->unreadNotificationsCount(); @endphp
                <div class="relative" x-data="notifBell({{ $unread }})">
                    <button @click="toggle()"
                            class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Notifications">
                        <i class="ti ti-bell text-xl"></i>
                        <span x-show="unreadCount > 0" x-cloak
                              x-text="unreadCount > 99 ? '99+' : unreadCount"
                              class="absolute top-0.5 right-0.5 bg-red-500 text-white text-xs rounded-full min-w-[1rem] h-4 flex items-center justify-center px-0.5 leading-none font-medium">
                        </span>
                    </button>

                    <!-- Dropdown panel -->
                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 z-50 overflow-hidden origin-top-right">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ app()->getLocale() === 'th' ? 'การแจ้งเตือน' : 'Notifications' }}
                                </h3>
                                <span x-show="unreadCount > 0" x-text="unreadCount"
                                      class="text-xs bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full px-2 py-0.5 font-semibold">
                                </span>
                            </div>
                            <button @click="markAllRead()" x-show="unreadCount > 0"
                                    class="text-xs text-primary hover:opacity-75 font-medium">
                                {{ app()->getLocale() === 'th' ? 'อ่านทั้งหมด' : 'Mark all read' }}
                            </button>
                        </div>

                        <!-- List -->
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700/50">
                            <!-- Loading -->
                            <template x-if="loading">
                                <div class="flex items-center justify-center py-10 text-gray-400">
                                    <i class="ti ti-loader-2 text-2xl animate-spin"></i>
                                </div>
                            </template>

                            <!-- Empty -->
                            <template x-if="!loading && notifications.length === 0">
                                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                    <i class="ti ti-bell-off text-4xl mb-2 block"></i>
                                    <p class="text-sm">{{ app()->getLocale() === 'th' ? 'ไม่มีการแจ้งเตือน' : 'No notifications' }}</p>
                                </div>
                            </template>

                            <!-- Items -->
                            <template x-for="n in notifications" :key="n.id">
                                <div :class="!n.read_at ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''"
                                     class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                    <!-- Type icon -->
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                         :class="iconBg(n.type)">
                                        <i class="text-sm" :class="icon(n.type)"></i>
                                    </div>
                                    <!-- Text -->
                                    <a :href="notifLink(n)" class="flex-1 min-w-0" @click="open = false">
                                        <p class="text-xs font-medium leading-snug"
                                           :class="!n.read_at ? 'text-gray-800 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'"
                                           x-text="n.title"></p>
                                        <p x-show="n.body" x-text="n.body"
                                           class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-2 leading-snug"></p>
                                        <p x-text="n.created_at"
                                           class="text-xs text-gray-300 dark:text-gray-600 mt-1"></p>
                                    </a>
                                    <!-- Unread dot (click to mark read) -->
                                    <button x-show="!n.read_at" @click.stop="markRead(n)"
                                            title="{{ app()->getLocale() === 'th' ? 'อ่านแล้ว' : 'Mark read' }}"
                                            class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-primary mt-1.5 hover:bg-red-400 transition-colors opacity-80 hover:opacity-100">
                                    </button>
                                    <!-- Read indicator -->
                                    <div x-show="n.read_at" class="flex-shrink-0 w-2.5 h-2.5 mt-1.5"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-750 border-t border-gray-100 dark:border-gray-700 text-center">
                            <a href="{{ route('notifications.index') }}"
                               class="text-xs text-primary hover:opacity-75 font-medium">
                                {{ app()->getLocale() === 'th' ? 'ดูทั้งหมด' : 'View all notifications' }}
                                <i class="ti ti-arrow-right text-xs ml-0.5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Language -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-1 px-2 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="ti ti-world text-lg"></i>
                        <span class="hidden sm:inline uppercase text-xs font-medium">{{ app()->getLocale() }}</span>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-1 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-50 py-1 min-w-[6rem]">
                        <a href="{{ route('locale.switch', 'th') }}" class="block px-4 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 {{ app()->getLocale() === 'th' ? 'text-primary font-semibold' : '' }}">ไทย</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="block px-4 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 {{ app()->getLocale() === 'en' ? 'text-primary font-semibold' : '' }}">English</a>
                    </div>
                </div>

                <!-- Theme switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="เปลี่ยน Theme">
                        <i class="ti ti-palette text-xl"></i>
                    </button>
                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 p-3 bg-white dark:bg-gray-700 rounded-xl shadow-xl border border-gray-200 dark:border-gray-600 z-50 origin-top-right"
                         style="display:none;">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2.5 whitespace-nowrap">เลือก Theme</p>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="t in themes" :key="t.key">
                                <button @click="setTheme(t.key); open = false"
                                        :title="t.name"
                                        class="w-8 h-8 rounded-full transition-all duration-150 hover:scale-110 border-2 border-white dark:border-gray-700 shadow-sm"
                                        :class="theme === t.key ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-offset-gray-700' : ''"
                                        :style="{ backgroundColor: t.color }">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Fullscreen toggle -->
                <button @click="toggleFullscreen()"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                        :title="isFullscreen ? 'ออกจากเต็มจอ' : 'เต็มจอ'">
                    <i :class="isFullscreen ? 'ti ti-arrows-minimize text-xl' : 'ti ti-arrows-maximize text-xl'"></i>
                </button>

                <!-- Dark mode -->
                <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i x-show="!darkMode" class="ti ti-moon text-xl"></i>
                    <i x-show="darkMode" class="ti ti-sun text-xl"></i>
                </button>

                <!-- User menu -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 pl-2 pr-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background-color: var(--color-primary)">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 leading-tight">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400 leading-tight">{{ auth()->user()->getRoleNames()->first() }}</p>
                            @if(auth()->user()->factory)
                            <p class="text-xs text-primary leading-tight truncate max-w-[130px]">
                                <i class="ti ti-building-factory-2 text-xs mr-0.5"></i>{{ auth()->user()->factory->name_th }}
                                @if(auth()->user()->is_parent_factory)
                                <span class="ml-1 text-purple-400 font-semibold">★</span>
                                @endif
                            </p>
                            @endif
                        </div>
                        <i class="ti ti-chevron-down text-xs text-gray-400"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-1 w-52 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 z-50 py-1">
                        @if(auth()->user()->factory)
                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-600">
                            <p class="text-xs text-gray-400">{{ app()->getLocale() === 'th' ? 'โรงงาน' : 'Factory' }}</p>
                            <p class="text-sm font-medium text-primary">{{ auth()->user()->factory->name_th }}</p>
                            @if(auth()->user()->is_parent_factory)
                            <p class="text-xs text-purple-500 mt-0.5">
                                <i class="ti ti-star-filled mr-1"></i>{{ app()->getLocale() === 'th' ? 'เข้าถึงได้ทุก Factory' : 'Cross-Factory Access' }}
                            </p>
                            @endif
                        </div>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                            <i class="ti ti-user mr-2"></i> {{ __('common.profile') }}
                        </a>
                        <hr class="border-gray-200 dark:border-gray-600 my-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <i class="ti ti-logout mr-2"></i> {{ __('common.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900">
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-4 flex items-center p-4 rounded-xl bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-200">
                <i class="ti ti-circle-check mr-2 text-lg"></i>
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="ml-auto"><i class="ti ti-x text-sm"></i></button>
            </div>
            @endif
            @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mb-4 flex items-center p-4 rounded-xl bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200">
                <i class="ti ti-alert-circle mr-2 text-lg"></i>
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="ml-auto"><i class="ti ti-x text-sm"></i></button>
            </div>
            @endif
            @if($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200">
                <ul class="list-disc ml-4 space-y-1 text-sm">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.27/dist/interact.min.js"></script>
<script>
function appLayout() {
    return {
        sidebarOpen:  localStorage.getItem('sidebarOpen') !== 'false',
        darkMode:     localStorage.getItem('darkMode') === 'true',
        isFullscreen: false,
        theme:        '{{ auth()->user()->theme_preference ?? "default" }}',
        themes: [
            { key: 'default', name: 'Default (Indigo)', color: '#4f46e5' },
            { key: 'ocean',   name: 'Ocean',            color: '#0891b2' },
            { key: 'forest',  name: 'Forest',           color: '#16a34a' },
            { key: 'sunset',  name: 'Sunset',           color: '#d97706' },
            { key: 'rose',    name: 'Rose',             color: '#e11d48' },
            { key: 'slate',   name: 'Slate',            color: '#475569' },
        ],

        init() {
            this.$watch('sidebarOpen', v => localStorage.setItem('sidebarOpen', v));
            this.$watch('darkMode',    v => localStorage.setItem('darkMode', v));
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        },

        onFullscreenChange() {
            this.isFullscreen = !!document.fullscreenElement;
            // Sidebar CSS transition is 300ms — re-measure canvas after it settles
            setTimeout(() => window.dispatchEvent(new Event('resize')), 350);
        },

        setTheme(key) {
            this.theme = key;
            document.documentElement.setAttribute('data-theme', key);
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch('/user/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ theme: key }),
                credentials: 'same-origin',
            }).catch(e => console.error('Failed to save theme', e));
        },
    };
}

function notifBell(initialCount) {
    return {
        open: false,
        loading: false,
        loaded: false,
        notifications: [],
        unreadCount: initialCount,

        async toggle() {
            this.open = !this.open;
            if (this.open && !this.loaded) {
                await this.load();
            }
        },

        async load() {
            this.loading = true;
            try {
                const r = await fetch('/notifications/recent', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await r.json();
                this.notifications = data.notifications;
                this.unreadCount   = data.unreadCount;
                this.loaded        = true;
            } catch (e) {
                console.error('Failed to load notifications', e);
            } finally {
                this.loading = false;
            }
        },

        markRead(n) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch(`/notifications/${n.id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            n.read_at = new Date().toISOString();
            this.unreadCount = Math.max(0, this.unreadCount - 1);
        },

        async markAllRead() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await fetch('/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            this.notifications.forEach(n => { n.read_at = new Date().toISOString(); });
            this.unreadCount = 0;
        },

        notifLink(n) {
            if (n.payload && n.payload.submission_id) {
                return `/submissions/${n.payload.submission_id}`;
            }
            return '/notifications';
        },

        icon(type) {
            return {
                approval_required: 'ti ti-clock text-blue-500',
                approval_result:   'ti ti-circle-check text-green-500',
                assigned:          'ti ti-user-check text-indigo-500',
                overdue:           'ti ti-alarm text-red-500',
                task_done:         'ti ti-trophy text-yellow-500',
            }[type] ?? 'ti ti-bell text-gray-400';
        },

        iconBg(type) {
            return {
                approval_required: 'bg-blue-50 dark:bg-blue-900/30',
                approval_result:   'bg-green-50 dark:bg-green-900/30',
                assigned:          'bg-indigo-100 dark:bg-indigo-900/40',
                overdue:           'bg-red-50 dark:bg-red-900/30',
                task_done:         'bg-yellow-50 dark:bg-yellow-900/30',
            }[type] ?? 'bg-gray-100 dark:bg-gray-700';
        },
    };
}
</script>
@stack('scripts')
</body>
</html>
