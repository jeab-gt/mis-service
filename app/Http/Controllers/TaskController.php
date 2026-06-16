<?php

namespace App\Http\Controllers;

use App\Models\AppSubmission;
use App\Models\RequestAssignment;
use App\Models\RequestDailyLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index()
    {
        $user = auth()->user();
        $assignments = RequestAssignment::with([
                'submission.app',
                'submission.dailyLogs' => fn($q) => $q->orderByDesc('log_date')->orderByDesc('id'),
            ])
            ->where('assignee_id', $user->id)
            ->latest('assigned_at')
            ->get();

        // Compute progress and group by kanban column
        $todo       = collect();
        $inProgress = collect();
        $done       = collect();

        foreach ($assignments as $a) {
            $pct    = $a->submission->dailyLogs->first()?->progress_pct ?? 0;
            $status = $a->submission->status;

            if (in_array($status, ['approved', 'rejected', 'closed']) || $pct >= 100) {
                $done->push($a->setAttribute('_progress', $pct));
            } elseif ($a->submission->dailyLogs->isNotEmpty()) {
                $inProgress->push($a->setAttribute('_progress', $pct));
            } else {
                $todo->push($a->setAttribute('_progress', 0));
            }
        }

        return view('tasks.index', compact('todo', 'inProgress', 'done'));
    }

    public function schedule(Request $request)
    {
        $user      = auth()->user();
        $view      = $request->get('view', 'week');   // week | month
        $sectionId = $request->get('section_id');

        if ($view === 'week') {
            $startDate = $request->get('start')
                ? Carbon::parse($request->get('start'))->startOfWeek()
                : now()->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();
        } else {
            $month = $request->get('month', now()->month);
            $year  = $request->get('year', now()->year);
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate   = $startDate->copy()->endOfMonth();
        }

        $canSeeAll = $user->hasAnyRole(['super_admin', 'it_manager', 'team_lead']);

        $query = RequestAssignment::with(['submission.app', 'submission.dailyLogs', 'assignee.section', 'assignee.roles'])
            ->when(!$canSeeAll, fn($q) => $q->where('assignee_id', $user->id))
            ->when($user->factory_id && !$user->is_parent_factory, fn($q) => $q->whereHas('submission', fn($sq) => $sq->where('factory_id', $user->factory_id)))
            ->when($sectionId, fn($q) => $q->whereHas('assignee', fn($uq) => $uq->where('section_id', $sectionId)))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('assigned_at', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('assigned_at', '<=', $endDate)
                         ->where(function ($q3) use ($startDate) {
                             $q3->whereNull('due_date')->orWhere('due_date', '>=', $startDate);
                         });
                  });
            })
            ->orderBy('due_date');

        $assignments = $query->get();

        // Group by assignee
        $byAssignee = $assignments->groupBy('assignee_id');

        // Section list for filter
        $sections = \App\Models\Master::where('type', 'section')
            ->when(!$user->is_parent_factory && $user->factory_id,
                fn($q) => $q->where('parent_id', $user->factory_id))
            ->orderBy('name_th')
            ->get();

        // Build timeline days array
        $days = collect();
        $cur  = $startDate->copy();
        while ($cur->lte($endDate)) {
            $days->push($cur->copy());
            $cur->addDay();
        }

        return view('tasks.schedule', compact(
            'assignments', 'byAssignee', 'days',
            'startDate', 'endDate', 'view', 'sections', 'sectionId'
        ));
    }

    public function storeLog(Request $request, AppSubmission $submission)
    {
        $data = $request->validate([
            'progress_pct' => 'required|integer|min:0|max:100',
            'detail'       => 'required|string|max:2000',
            'log_date'     => 'required|date',
        ]);

        RequestDailyLog::create([
            'submission_id' => $submission->id,
            'user_id'       => auth()->id(),
            'log_date'      => $data['log_date'],
            'progress_pct'  => $data['progress_pct'],
            'detail'        => $data['detail'],
        ]);

        // Notify on completion
        if ($data['progress_pct'] >= 100) {
            $assignment = $submission->latestAssignment()->with('assigner')->first();
            if ($assignment?->assigner) {
                $this->notificationService->notify($assignment->assigner, 'task_done', [
                    'submission_id' => $submission->id,
                    'app_name'      => $submission->app?->name ?? '',
                    'assignee_name' => auth()->user()->name,
                ]);
            }
        }

        // Return updated logs for the modal
        $logs = RequestDailyLog::with('user')
            ->where('submission_id', $submission->id)
            ->orderByDesc('log_date')
            ->get()
            ->map(fn($l) => [
                'log_date'    => $l->log_date->format('d/m/Y'),
                'progress_pct'=> $l->progress_pct,
                'detail'      => $l->detail,
                'user_name'   => $l->user?->name ?? '-',
            ]);

        return response()->json(['success' => true, 'progress' => $data['progress_pct'], 'logs' => $logs]);
    }

    public function getLogs(AppSubmission $submission)
    {
        $logs = RequestDailyLog::with('user')
            ->where('submission_id', $submission->id)
            ->orderByDesc('log_date')
            ->get()
            ->map(fn($l) => [
                'log_date'     => $l->log_date->format('d/m/Y'),
                'progress_pct' => $l->progress_pct,
                'detail'       => $l->detail,
                'user_name'    => $l->user?->name ?? '-',
            ]);

        return response()->json(['logs' => $logs, 'progress' => $logs->first()['progress_pct'] ?? 0]);
    }

    public function moveCard(Request $request, RequestAssignment $assignment)
    {
        $data = $request->validate(['column' => 'required|in:todo,in_progress,done']);
        $submission = $assignment->submission;

        // Validate: can't move to done without 100%
        $pct = $submission->progress;
        if ($data['column'] === 'done' && $pct < 100) {
            return response()->json(['success' => false, 'message' => 'Progress must be 100% to mark as done'], 422);
        }

        $statusMap = [
            'todo'        => 'submitted',
            'in_progress' => 'in_review',
            'done'        => 'closed',
        ];
        $submission->update(['status' => $statusMap[$data['column']]]);

        return response()->json(['success' => true]);
    }

    public function updateProgress(Request $request, AppSubmission $submission)
    {
        $data = $request->validate([
            'progress_pct' => 'required|integer|min:0|max:100',
            'detail'       => 'required|string|max:1000',
        ]);

        RequestDailyLog::create([
            'submission_id' => $submission->id,
            'user_id'       => auth()->id(),
            'log_date'      => now()->toDateString(),
            'progress_pct'  => $data['progress_pct'],
            'detail'        => $data['detail'],
        ]);

        return response()->json(['success' => true]);
    }
}
