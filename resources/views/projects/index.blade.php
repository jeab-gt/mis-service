@extends('layouts.app')
@section('title', 'Projects')
@section('breadcrumb')
<span>Projects</span>
@endsection

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold">Projects</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage and track all your projects</p>
        </div>
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium btn-primary">
            <i class="ti ti-plus"></i> New Project
        </a>
    </div>

    {{-- Filter bar --}}
    <form method="GET" class="flex items-center gap-3 flex-wrap bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600 px-4 py-3 shadow-sm mis-card">

        {{-- Status tabs --}}
        <div class="flex gap-1 flex-wrap">
            @foreach(['all' => 'All', 'planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $val]) }}"
               class="px-3 py-1 rounded-lg text-xs font-medium transition-colors
                      {{ (request('status', 'all') === $val) ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        <div class="flex-1"></div>

        {{-- Priority filter --}}
        <select name="priority" onchange="this.form.submit()"
                class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg px-3 py-1.5 text-sm w-36 focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="">All Priority</option>
            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $v => $l)
            <option value="{{ $v }}" {{ request('priority') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>

        {{-- Search --}}
        <div class="relative">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search projects..."
                   class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-sm w-48 focus:outline-none focus:ring-1 focus:ring-primary placeholder:text-gray-400">
            <i class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        </div>

        {{-- My projects toggle --}}
        <label class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer whitespace-nowrap">
            <input type="checkbox" name="my_projects" value="1"
                   {{ request('my_projects') ? 'checked' : '' }} onchange="this.form.submit()"
                   class="rounded">
            My Projects
        </label>

        @if(request()->hasAny(['status', 'priority', 'search', 'my_projects']))
        <a href="{{ route('projects.index') }}" class="text-xs text-gray-400 hover:text-gray-600 whitespace-nowrap">
            <i class="ti ti-x"></i> Clear
        </a>
        @endif
    </form>

    {{-- Project Cards --}}
    @forelse($projects as $project)
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-600 shadow-sm px-5 py-3 hover:shadow-md transition-shadow mis-card">

        {{-- Row 1: dot + name + badges + factory --}}
        <div class="flex items-center gap-2 min-w-0">
            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $project->color }}"></div>
            <a href="{{ route('projects.show', $project) }}"
               class="font-semibold text-sm hover:text-primary transition-colors truncate">
                {{ $project->name }}
            </a>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                         bg-{{ $project->status_badge_color }}-100 dark:bg-{{ $project->status_badge_color }}-900/30
                         text-{{ $project->status_badge_color }}-700 dark:text-{{ $project->status_badge_color }}-400">
                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                         bg-{{ $project->priority_badge_color }}-100 dark:bg-{{ $project->priority_badge_color }}-900/30
                         text-{{ $project->priority_badge_color }}-700 dark:text-{{ $project->priority_badge_color }}-400">
                {{ ucfirst($project->priority) }}
            </span>
            @if($project->factory)
            <span class="text-xs text-gray-400 flex-shrink-0">{{ $project->factory->name_th }}</span>
            @endif
        </div>

        {{-- Row 2: description --}}
        @if($project->description)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $project->description }}</p>
        @endif

        {{-- Row 3: progress bar --}}
        <div class="mt-2 flex items-center gap-3">
            <div class="flex-1 h-1 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all" style="width: {{ $project->progress_pct }}%; background-color: {{ $project->color }}"></div>
            </div>
            <span class="text-xs text-gray-400 font-medium flex-shrink-0">{{ $project->progress_pct }}%</span>
        </div>

        {{-- Row 4: manager + date + avatars --}}
        <div class="flex items-center justify-between mt-2">
            <div class="flex items-center gap-4 text-xs text-gray-400">
                @if($project->manager)
                <span class="flex items-center gap-1">
                    <i class="ti ti-user"></i> {{ $project->manager->name }}
                </span>
                @endif
                @if($project->end_date)
                <span class="flex items-center gap-1 {{ $project->end_date->isPast() && !in_array($project->status, ['completed','cancelled']) ? 'text-red-500' : '' }}">
                    <i class="ti ti-calendar"></i> {{ $project->end_date->format('d/m/Y') }}
                </span>
                @endif
            </div>

            {{-- Member avatars --}}
            <div class="flex -space-x-1.5">
                @foreach($project->members->take(5) as $m)
                @if($m->user)
                <div class="w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-white dark:ring-gray-800"
                     style="background-color: var(--color-primary)"
                     title="{{ $m->user->name }}">
                    {{ strtoupper(substr($m->user->name, 0, 1)) }}
                </div>
                @endif
                @endforeach
                @if($project->members->count() > 5)
                <div class="w-5 h-5 rounded-full flex items-center justify-center bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 text-xs font-bold ring-2 ring-white dark:ring-gray-800">
                    +{{ $project->members->count() - 5 }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-24 text-gray-400">
        <i class="ti ti-layout-kanban text-6xl mb-4 block opacity-40"></i>
        <p class="text-lg font-medium">No projects found</p>
        <p class="text-sm mt-1">Create your first project to get started</p>
        <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl text-sm font-medium btn-primary">
            <i class="ti ti-plus"></i> New Project
        </a>
    </div>
    @endforelse

    {{-- Pagination --}}
    @if($projects->hasPages())
    <div class="mt-4">{{ $projects->links() }}</div>
    @endif

</div>
@endsection
