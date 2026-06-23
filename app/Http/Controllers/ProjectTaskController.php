<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailyLog;
use App\Models\ProjectTask;
use App\Models\ProjectTaskChecklist;
use App\Models\ProjectTimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectTaskController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'assignee_id'      => 'nullable|exists:users,id',
            'milestone_id'     => 'nullable|exists:project_milestones,id',
            'parent_task_id'   => 'nullable|exists:project_tasks,id',
            'start_date'       => 'nullable|date',
            'due_date'         => 'nullable|date',
            'estimated_hours'  => 'nullable|numeric|min:0',
            'priority'         => 'required|in:low,medium,high,critical',
            'status'           => 'required|in:todo,in_progress,review,done,cancelled',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = Auth::id();
        $data['sort_order'] = ProjectTask::where('project_id', $project->id)
            ->where('status', $data['status'])
            ->max('sort_order') + 1;

        $task = ProjectTask::create($data);

        $project->recalculateProgress();

        if ($request->expectsJson()) {
            return response()->json(['task' => $task->load(['assignee', 'checklists'])]);
        }

        return back()->with('success', 'Task created.');
    }

    public function update(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'title'           => 'sometimes|required|string|max:255',
            'description'     => 'nullable|string',
            'assignee_id'     => 'nullable|exists:users,id',
            'milestone_id'    => 'nullable|exists:project_milestones,id',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'priority'        => 'sometimes|required|in:low,medium,high,critical',
            'status'          => 'sometimes|required|in:todo,in_progress,review,done,cancelled',
            'progress_pct'    => 'sometimes|integer|min:0|max:100',
        ]);

        if (isset($data['status']) && $data['status'] === 'done' && $task->status !== 'done') {
            $data['completed_at'] = now();
            $data['progress_pct'] = 100;
        }
        if (isset($data['status']) && $data['status'] !== 'done') {
            $data['completed_at'] = null;
        }

        $task->update($data);
        $task->project->recalculateProgress();

        if ($request->expectsJson()) {
            return response()->json(['task' => $task->fresh(['assignee', 'checklists', 'comments'])]);
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroy(ProjectTask $task)
    {
        $project = $task->project;
        $task->delete();
        $project->recalculateProgress();

        return back()->with('success', 'Task deleted.');
    }

    public function updateProgress(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'progress_pct' => 'required|integer|min:0|max:100',
            'detail'       => 'required|string',
        ]);

        $task->update(['progress_pct' => $data['progress_pct']]);

        ProjectDailyLog::create([
            'task_id'      => $task->id,
            'user_id'      => Auth::id(),
            'log_date'     => today(),
            'progress_pct' => $data['progress_pct'],
            'detail'       => $data['detail'],
        ]);

        $task->project->recalculateProgress();

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'tasks'         => 'required|array',
            'tasks.*.id'    => 'required|exists:project_tasks,id',
            'tasks.*.sort'  => 'required|integer',
            'tasks.*.status'=> 'required|in:todo,in_progress,review,done,cancelled',
        ]);

        foreach ($request->tasks as $item) {
            ProjectTask::where('id', $item['id'])->update([
                'sort_order' => $item['sort'],
                'status'     => $item['status'],
            ]);
        }

        // Recalculate progress for the project
        $firstTask = ProjectTask::find($request->tasks[0]['id'] ?? null);
        $firstTask?->project->recalculateProgress();

        return response()->json(['ok' => true]);
    }

    public function addChecklist(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $item = ProjectTaskChecklist::create([
            'task_id'    => $task->id,
            'title'      => $data['title'],
            'sort_order' => $task->checklists()->max('sort_order') + 1,
        ]);

        return response()->json(['item' => $item]);
    }

    public function toggleChecklist(ProjectTaskChecklist $item)
    {
        $completed = !$item->is_completed;
        $item->update([
            'is_completed' => $completed,
            'completed_by' => $completed ? Auth::id() : null,
            'completed_at' => $completed ? now() : null,
        ]);

        return response()->json(['is_completed' => $item->is_completed]);
    }

    public function logTime(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'log_date'    => 'required|date',
            'hours'       => 'required|numeric|min:0.25|max:24',
            'description' => 'nullable|string',
        ]);

        $data['task_id'] = $task->id;
        $data['user_id'] = Auth::id();

        ProjectTimeLog::create($data);

        // Update actual_hours
        $total = $task->timeLogs()->sum('hours');
        $task->update(['actual_hours' => $total]);

        return response()->json(['ok' => true, 'actual_hours' => $total]);
    }
}
