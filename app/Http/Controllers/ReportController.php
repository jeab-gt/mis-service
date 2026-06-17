<?php

namespace App\Http\Controllers;

use App\Exports\SubmissionsExport;
use App\Models\AppSubmission;
use App\Models\RequestAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private function factoryScope(Request $request, $query)
    {
        $user = auth()->user();
        if ($user->is_parent_factory && $fid = $request->get('factory_id')) {
            $query->where('factory_id', $fid);
        } elseif (!$user->is_parent_factory && $user->factory_id) {
            $query->where('factory_id', $user->factory_id);
        }
        return $query;
    }

    public function index(Request $request)
    {
        return redirect()->route('reports.daily');
    }

    public function daily(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $query = AppSubmission::with(['app', 'submitter', 'latestAssignment.assignee']);
        $this->factoryScope($request, $query);

        $submissions = $query->whereDate('created_at', $date)->get();

        $newCount       = $submissions->count();
        $completedCount = $submissions->whereIn('status', ['approved', 'closed'])->count();
        $pendingList    = $submissions->whereNotIn('status', ['approved', 'rejected', 'closed'])->values();
        $overdueCount   = $pendingList->filter(fn($s) =>
            $s->latestAssignment?->due_date && $s->latestAssignment->due_date->isPast()
        )->count();

        return view('reports.daily', compact('submissions', 'date', 'newCount', 'completedCount', 'pendingList', 'overdueCount'));
    }

    public function weekly(Request $request)
    {
        $start = $request->get('start')
            ? Carbon::parse($request->get('start'))->startOfWeek()
            : now()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $query = AppSubmission::with(['app', 'submitter']);
        $this->factoryScope($request, $query);
        $submissions = $query->whereBetween('created_at', [$start, $end->copy()->endOfDay()])->get();

        // Build per-day chart data
        $days = collect();
        $cur  = $start->copy();
        while ($cur->lte($end)) {
            $d = $cur->toDateString();
            $days->push([
                'date'      => $cur->format('D d/m'),
                'created'   => $submissions->filter(fn($s) => $s->created_at->toDateString() === $d)->count(),
                'completed' => $submissions->filter(fn($s) => $s->closed_at && $s->closed_at->toDateString() === $d)->count(),
            ]);
            $cur->addDay();
        }

        // Avg resolution time (overall)
        $avgHrs = $submissions->filter(fn($s) => $s->submitted_at && $s->closed_at)
            ->average(fn($s) => $s->submitted_at->diffInHours($s->closed_at));

        // Avg resolution time per day (for line chart) — based on when closed
        $resolvedQ = AppSubmission::whereNotNull('submitted_at')->whereNotNull('closed_at');
        $this->factoryScope($request, $resolvedQ);
        $resolvedThisWeek = $resolvedQ->whereBetween('closed_at', [$start, $end->copy()->endOfDay()])->get();

        $avgPerDay = collect();
        $curDay = $start->copy();
        while ($curDay->lte($end)) {
            $d = $curDay->toDateString();
            $dayItems = $resolvedThisWeek->filter(fn($s) => $s->closed_at->toDateString() === $d);
            $avgPerDay->push([
                'date'    => $curDay->format('D d/m'),
                'avg_hrs' => $dayItems->isNotEmpty()
                    ? round($dayItems->avg(fn($s) => $s->submitted_at->diffInHours($s->closed_at)), 1)
                    : null,
            ]);
            $curDay->addDay();
        }

        // Assignee summary table
        $assigneeStats = RequestAssignment::with(['assignee', 'submission.dailyLogs'])
            ->whereHas('submission', function ($q) use ($request, $start, $end) {
                $this->factoryScope($request, $q);
                $q->whereBetween('created_at', [$start, $end->copy()->endOfDay()]);
            })
            ->get()
            ->groupBy('assignee_id')
            ->map(fn($group) => [
                'assignee'     => $group->first()->assignee,
                'assigned'     => $group->count(),
                'completed'    => $group->filter(fn($a) => in_array($a->submission->status, ['approved', 'closed']))->count(),
                'avg_progress' => (int) round($group->avg(fn($a) => $a->submission->dailyLogs->first()?->progress_pct ?? 0)),
            ])
            ->sortByDesc('completed')
            ->values();

        return view('reports.weekly', compact('submissions', 'start', 'end', 'days', 'avgHrs', 'avgPerDay', 'assigneeStats'));
    }

    public function monthly(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $query = AppSubmission::with(['app', 'submitter', 'latestAssignment.assignee']);
        $this->factoryScope($request, $query);
        $submissions = $query->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();

        // Weekly breakdown chart
        $weeks = collect();
        $cur = $start->copy()->startOfWeek();
        while ($cur->lte($end)) {
            $ws = $cur->copy();
            $we = $cur->copy()->endOfWeek()->min($end);
            $label = $ws->format('d/m') . '–' . $we->format('d/m');
            $weeks->push([
                'label'     => $label,
                'created'   => $submissions->filter(fn($s) => $s->created_at->between($ws, $we))->count(),
                'completed' => $submissions->filter(fn($s) => $s->closed_at && Carbon::parse($s->closed_at)->between($ws, $we))->count(),
            ]);
            $cur->addWeek();
        }

        // Top 5 assignees by completed count
        $topAssignees = RequestAssignment::with('assignee')
            ->whereHas('submission', fn($q) => $q->whereIn('status', ['approved', 'closed'])
                ->whereMonth('closed_at', $month)->whereYear('closed_at', $year))
            ->select('assignee_id', DB::raw('count(*) as completed'))
            ->groupBy('assignee_id')
            ->orderByDesc('completed')
            ->limit(5)
            ->get();

        // By app/category pie data
        $byApp = $submissions->groupBy(fn($s) => $s->app?->name ?? 'Unknown')
            ->map->count()
            ->sortDesc()
            ->take(8);

        return view('reports.monthly', compact('submissions', 'weeks', 'month', 'year', 'topAssignees', 'byApp'));
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $from   = $request->get('from', now()->startOfMonth()->toDateString());
        $to     = $request->get('to', now()->toDateString());
        $fid    = auth()->user()->is_parent_factory ? $request->get('factory_id') : auth()->user()->factory_id;

        if ($format === 'pdf') {
            $submissions = AppSubmission::with(['app', 'submitter', 'latestAssignment.assignee'])
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->when($fid, fn($q) => $q->where('factory_id', $fid))
                ->get();
            $pdf = Pdf::loadView('reports.export-pdf', compact('submissions', 'from', 'to'));
            return $pdf->download("report_{$from}_{$to}.pdf");
        }

        return Excel::download(new SubmissionsExport($from, $to, $fid ?: null), "report_{$from}_{$to}.xlsx");
    }
}
