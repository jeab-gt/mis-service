<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProjectReportController extends Controller
{
    public function index(Project $project)
    {
        return redirect()->route('projects.show', $project);
    }

    public function burndown(Project $project)
    {
        $start = $project->start_date ?? $project->created_at->toDateString();
        $end   = $project->end_date   ?? now()->toDateString();

        $totalTasks = $project->tasks()->whereNull('parent_task_id')->count();
        $days       = [];
        $ideal      = [];
        $actual     = [];

        $period = \Carbon\CarbonPeriod::create($start, $end);
        $i      = 0;
        $count  = iterator_count($period->copy());

        foreach ($period as $day) {
            $done = $project->tasks()
                ->whereNull('parent_task_id')
                ->where('status', 'done')
                ->whereDate('completed_at', '<=', $day)
                ->count();

            $days[]   = $day->format('d/m');
            $ideal[]  = round($totalTasks - ($totalTasks * $i / max($count - 1, 1)), 1);
            $actual[]  = $totalTasks - $done;
            $i++;
        }

        return response()->json(compact('days', 'ideal', 'actual'));
    }

    public function workload(Project $project)
    {
        $members = $project->members()->with('user')->get();
        $labels  = [];
        $counts  = [];

        foreach ($members as $m) {
            $labels[]  = $m->user->name ?? 'Unknown';
            $counts[]  = $project->tasks()->where('assignee_id', $m->user_id)->whereNotIn('status', ['done', 'cancelled'])->count();
        }

        return response()->json(compact('labels', 'counts'));
    }
}
