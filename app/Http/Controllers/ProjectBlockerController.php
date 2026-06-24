<?php

namespace App\Http\Controllers;

use App\Models\MisNotification;
use App\Models\ProjectTask;
use App\Models\ProjectTaskBlocker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectBlockerController extends Controller
{
    public function store(Request $request, ProjectTask $task): JsonResponse
    {
        $validated = $request->validate([
            'type'        => 'required|in:technical,resource,dependency,other',
            'description' => 'required|string|max:2000',
        ]);

        $blocker = ProjectTaskBlocker::create([
            'task_id'     => $task->id,
            'reported_by' => Auth::id(),
            'type'        => $validated['type'],
            'description' => $validated['description'],
        ]);

        $task->update(['has_blocker' => true]);

        $blocker->load('reportedBy');

        // Notify project manager + leads
        $task->load('project.members');
        $notifyUserIds = collect([$task->project->manager_id])
            ->merge($task->project->members->where('role', 'lead')->pluck('user_id'))
            ->unique()
            ->filter(fn($id) => $id && $id !== Auth::id());

        $title   = "🚨 Blocker: {$task->title}";
        $body    = Auth::user()->name . " flagged as blocker: {$validated['description']}";

        foreach ($notifyUserIds as $userId) {
            MisNotification::create([
                'user_id'  => $userId,
                'type'     => 'task_blocker',
                'title_th' => $title,
                'title_en' => $title,
                'body_th'  => $body,
                'body_en'  => $body,
                'payload'  => ['task_id' => $task->id, 'project_id' => $task->project_id],
            ]);
        }

        return response()->json([
            'ok'      => true,
            'blocker' => [
                'id'               => $blocker->id,
                'type'             => $blocker->type,
                'description'      => $blocker->description,
                'reported_by_name' => $blocker->reportedBy->name,
                'created_at'       => $blocker->created_at->diffForHumans(),
            ],
        ]);
    }

    public function resolve(Request $request, ProjectTaskBlocker $blocker): JsonResponse
    {
        $validated = $request->validate([
            'resolution_note' => 'required|string|max:2000',
        ]);

        $blocker->update([
            'resolved_at'     => now(),
            'resolved_by'     => Auth::id(),
            'resolution_note' => $validated['resolution_note'],
        ]);

        $task = $blocker->task;
        if (!$task->blockers()->whereNull('resolved_at')->exists()) {
            $task->update(['has_blocker' => false]);
        }

        // Notify assignee + original reporter
        $blocker->load('reportedBy');
        $task->load('assignee');

        $notifyUserIds = collect([$task->assignee_id, $blocker->reported_by])
            ->unique()
            ->filter(fn($id) => $id && $id !== Auth::id());

        $title = "✅ Resolved: {$task->title}";
        $body  = Auth::user()->name . " resolved blocker: {$validated['resolution_note']}";

        foreach ($notifyUserIds as $userId) {
            MisNotification::create([
                'user_id'  => $userId,
                'type'     => 'task_blocker_resolved',
                'title_th' => $title,
                'title_en' => $title,
                'body_th'  => $body,
                'body_en'  => $body,
                'payload'  => ['task_id' => $task->id, 'project_id' => $task->project_id],
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
