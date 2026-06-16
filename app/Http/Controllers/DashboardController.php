<?php

namespace App\Http\Controllers;

use App\Models\AppSubmission;
use App\Models\RequestAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $now  = now();
        $today = $now->toDateString();

        $stats = [
            'open'      => AppSubmission::forUser($user)->where('status', 'submitted')->count(),
            'in_review' => AppSubmission::forUser($user)->where('status', 'in_review')->count(),
            'closed'    => AppSubmission::forUser($user)
                                ->whereIn('status', ['approved', 'closed'])
                                ->whereMonth('closed_at', $now->month)
                                ->whereYear('closed_at', $now->year)
                                ->count(),
            'overdue'   => AppSubmission::forUser($user)
                                ->whereNotIn('status', ['approved', 'closed', 'rejected'])
                                ->whereHas('assignments', fn($q) => $q->where('due_date', '<', $today))
                                ->count(),
        ];

        // Chart: submissions per day last 7 days
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $daily[] = ['date' => $date, 'count' => AppSubmission::forUser($user)->whereDate('created_at', $date)->count()];
        }

        // Chart: by status
        $byStatus = AppSubmission::forUser($user)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // My tasks
        $myTasks = RequestAssignment::with(['submission.app', 'submission.dailyLogs'])
            ->where('assignee_id', $user->id)
            ->whereHas('submission', fn($q) => $q->whereNotIn('status', ['approved', 'rejected', 'closed']))
            ->latest('assigned_at')
            ->take(10)
            ->get();

        return view('dashboard.index', compact('stats', 'daily', 'byStatus', 'myTasks'));
    }
}
