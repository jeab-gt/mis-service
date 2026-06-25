<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\ProjectReportSlide;
use App\Models\ProjectReportElement;
use App\Models\ProjectTaskBlocker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectReportController extends Controller
{
    // ─── Chart data (existing) ────────────────────────────────────────────────

    public function burndown(Project $project)
    {
        $start = $project->start_date ?? $project->created_at->toDateString();
        $end   = $project->end_date   ?? now()->toDateString();

        $totalTasks = $project->tasks()->whereNull('parent_task_id')->count();
        $days = $ideal = $actual = [];

        $period = \Carbon\CarbonPeriod::create($start, $end);
        $i      = 0;
        $count  = iterator_count($period->copy());

        foreach ($period as $day) {
            $done = $project->tasks()
                ->whereNull('parent_task_id')
                ->where('status', 'done')
                ->whereDate('completed_at', '<=', $day)
                ->count();
            $days[]  = $day->format('d/m');
            $ideal[] = round($totalTasks - ($totalTasks * $i / max($count - 1, 1)), 1);
            $actual[] = $totalTasks - $done;
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
            $labels[] = $m->user->name ?? 'Unknown';
            $counts[] = $project->tasks()->where('assignee_id', $m->user_id)->whereNotIn('status', ['done', 'cancelled'])->count();
        }

        return response()->json(compact('labels', 'counts'));
    }

    // ─── Report Builder CRUD ──────────────────────────────────────────────────

    public function index(Project $project)
    {
        $reports = ProjectReport::where('project_id', $project->id)
            ->where('is_template', false)
            ->with('creator')
            ->withCount('slides')
            ->latest()
            ->get();

        return view('projects.reports.index', compact('project', 'reports'));
    }

    public function create(Project $project)
    {
        $templates = ProjectReport::where('is_template', true)
            ->orderBy('template_name')
            ->get();

        return view('projects.reports.create', compact('project', 'templates'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'template_id' => 'nullable|exists:project_reports,id',
        ]);

        DB::beginTransaction();
        try {
            $report = ProjectReport::create([
                'project_id'  => $project->id,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'is_template' => false,
                'created_by'  => Auth::id(),
            ]);

            if (!empty($data['template_id'])) {
                $this->cloneFromTemplate($report, (int) $data['template_id']);
            } else {
                ProjectReportSlide::create([
                    'report_id'   => $report->id,
                    'slide_order' => 0,
                    'bg_color'    => '#ffffff',
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('projects.reports.builder', [$project, $report]);
    }

    public function builder(Project $project, ProjectReport $report)
    {
        $report->load(['slides', 'attachments']);
        $kpi         = $this->buildKpi($project);
        $chartData   = $this->buildChartData($project);
        $projectData = $this->buildProjectData($project);

        return view('projects.reports.builder', compact('project', 'report', 'kpi', 'chartData', 'projectData'));
    }

    public function save(Request $request, Project $project, ProjectReport $report)
    {
        $data = $request->validate([
            'title'                       => 'nullable|string|max:255',
            'slides'                      => 'required|array',
            'slides.*.id'                 => 'nullable',
            'slides.*.slide_order'        => 'required|integer',
            'slides.*.bg_color'           => 'nullable|string|max:20',
            'slides.*.notes'              => 'nullable|string',
            'slides.*.html_content'       => 'nullable|string',
            'slides.*.widgets'            => 'nullable|array',
            'slides.*.elements'           => 'nullable|array',
            'slides.*.elements.*.id'      => 'nullable',
            'slides.*.elements.*.type'    => 'nullable|in:text,image,chart,kpi,shape,gantt_mini,milestone_list,team_list,blocker_list,table,divider',
            'slides.*.elements.*.x'       => 'nullable|numeric',
            'slides.*.elements.*.y'       => 'nullable|numeric',
            'slides.*.elements.*.w'       => 'nullable|numeric',
            'slides.*.elements.*.h'       => 'nullable|numeric',
            'slides.*.elements.*.z_index' => 'nullable|integer',
            'slides.*.elements.*.props'   => 'nullable|array',
        ]);

        if (!empty($data['title'])) {
            $report->update(['title' => $data['title']]);
        }

        DB::beginTransaction();
        try {
            $savedSlideIds   = [];
            $savedElementIds = [];

            foreach ($data['slides'] as $slideData) {
                $isNew = !is_numeric($slideData['id'] ?? null);

                if ($isNew) {
                    $slide = ProjectReportSlide::create([
                        'report_id'    => $report->id,
                        'slide_order'  => $slideData['slide_order'],
                        'bg_color'     => $slideData['bg_color'] ?? '#ffffff',
                        'notes'        => $slideData['notes'] ?? null,
                        'html_content' => $slideData['html_content'] ?? null,
                        'widgets_data' => $slideData['widgets'] ?? null,
                    ]);
                } else {
                    $slide = ProjectReportSlide::find($slideData['id']);
                    if (!$slide || $slide->report_id !== $report->id) continue;
                    $slide->update([
                        'slide_order'  => $slideData['slide_order'],
                        'bg_color'     => $slideData['bg_color'] ?? '#ffffff',
                        'notes'        => $slideData['notes'] ?? null,
                        'html_content' => $slideData['html_content'] ?? null,
                        'widgets_data' => $slideData['widgets'] ?? null,
                    ]);
                }
                $savedSlideIds[] = $slide->id;

                foreach ($slideData['elements'] ?? [] as $elData) {
                    $isNewEl = !is_numeric($elData['id'] ?? null);

                    if ($isNewEl) {
                        $element = ProjectReportElement::create([
                            'slide_id' => $slide->id,
                            'type'     => $elData['type'],
                            'x'        => $elData['x'],
                            'y'        => $elData['y'],
                            'w'        => $elData['w'],
                            'h'        => $elData['h'],
                            'z_index'  => $elData['z_index'],
                            'props'    => $elData['props'],
                        ]);
                    } else {
                        $element = ProjectReportElement::find($elData['id']);
                        if (!$element || $element->slide_id !== $slide->id) continue;
                        $element->update([
                            'x'       => $elData['x'],
                            'y'       => $elData['y'],
                            'w'       => $elData['w'],
                            'h'       => $elData['h'],
                            'z_index' => $elData['z_index'],
                            'props'   => $elData['props'],
                        ]);
                    }
                    $savedElementIds[] = $element->id;
                }

                // Remove elements not in this save
                $slide->elements()->whereNotIn('id', collect($savedElementIds)->filter(fn($id) => is_int($id))->values()->all())->delete();
                $savedElementIds = []; // reset per slide
            }

            // Remove deleted slides
            $report->slides()->whereNotIn('id', collect($savedSlideIds)->filter(fn($id) => is_int($id))->values()->all())->delete();

            DB::commit();

            $report->load(['slides']);
            return response()->json([
                'success' => true,
                'slides'  => $report->slides->map(fn($s) => [
                    'id'           => $s->id,
                    'slide_order'  => $s->slide_order,
                    'bg_color'     => $s->bg_color,
                    'notes'        => $s->notes ?? '',
                    'html_content' => $s->html_content ?? '',
                    'widgets_data' => $s->widgets_data ?? [],
                    'elements'     => [],
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function preview(Project $project, ProjectReport $report)
    {
        $report->load(['slides.elements']);
        $kpi         = $this->buildKpi($project);
        $chartData   = $this->buildChartData($project);
        $projectData = $this->buildProjectData($project);

        return view('projects.reports.preview', compact('project', 'report', 'kpi', 'chartData', 'projectData'));
    }

    public function export(Project $project, ProjectReport $report)
    {
        $report->load(['slides.elements']);
        $kpi         = $this->buildKpi($project);
        $chartData   = $this->buildChartData($project);
        $projectData = $this->buildProjectData($project);

        return view('projects.reports.export', compact('project', 'report', 'kpi', 'chartData', 'projectData'));
    }

    public function uploadImage(Request $request, Project $project, ProjectReport $report)
    {
        $request->validate(['image' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg']);

        $file     = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('report-images', $filename, 'public');

        return response()->json(['url' => asset('storage/report-images/' . $filename)]);
    }

    public function destroy(Project $project, ProjectReport $report)
    {
        $report->delete();
        return redirect()->route('projects.reports.index', $project)
            ->with('success', 'Report deleted.');
    }

    public function saveAsTemplate(Request $request, Project $project, ProjectReport $report)
    {
        $data = $request->validate(['template_name' => 'required|string|max:255']);

        DB::beginTransaction();
        try {
            $template = ProjectReport::create([
                'project_id'    => $project->id,
                'title'         => $data['template_name'],
                'is_template'   => true,
                'template_name' => $data['template_name'],
                'created_by'    => Auth::id(),
            ]);
            $this->cloneFromTemplate($template, $report->id, $report);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function templates(Project $project)
    {
        $templates = ProjectReport::where('is_template', true)
            ->with('slides')
            ->orderBy('template_name')
            ->get();

        return view('projects.reports.templates', compact('project', 'templates'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function cloneFromTemplate(ProjectReport $target, int $sourceId, ?ProjectReport $source = null): void
    {
        $source = $source ?? ProjectReport::with('slides.elements')->findOrFail($sourceId);
        if (!$source->relationLoaded('slides')) {
            $source->load('slides.elements');
        }

        foreach ($source->slides as $slide) {
            $newSlide = ProjectReportSlide::create([
                'report_id'    => $target->id,
                'slide_order'  => $slide->slide_order,
                'bg_color'     => $slide->bg_color,
                'notes'        => $slide->notes,
                'html_content' => $slide->html_content,
            ]);
            foreach ($slide->elements as $el) {
                ProjectReportElement::create([
                    'slide_id' => $newSlide->id,
                    'type'     => $el->type,
                    'x'        => $el->x,
                    'y'        => $el->y,
                    'w'        => $el->w,
                    'h'        => $el->h,
                    'z_index'  => $el->z_index,
                    'props'    => $el->props,
                ]);
            }
        }
    }

    private function buildProjectData(Project $project): array
    {
        $milestones = $project->milestones()->orderBy('due_date')->get();
        $members    = $project->members()->with('user:id,name')->get();
        $tasks      = $project->tasks()->whereNull('parent_task_id')
            ->select(['id', 'title', 'start_date', 'due_date', 'status', 'progress_pct'])
            ->get();
        $blockers   = ProjectTaskBlocker::whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->whereNull('resolved_at')
            ->with(['task:id,title', 'reportedBy:id,name'])
            ->latest()
            ->get();

        return [
            'milestones' => $milestones->map(fn($m) => [
                'id'           => $m->id,
                'name'         => $m->name,
                'due_date'     => $m->due_date?->format('Y-m-d'),
                'is_completed' => (bool) $m->is_completed,
            ])->values()->toArray(),
            'members' => $members->map(fn($m) => [
                'id'          => $m->user_id,
                'name'        => $m->user->name ?? '—',
                'role'        => $m->role,
                'tasks_count' => $project->tasks()->whereNull('parent_task_id')->where('assignee_id', $m->user_id)->count(),
            ])->values()->toArray(),
            'tasks' => $tasks->map(fn($t) => [
                'id'           => $t->id,
                'title'        => $t->title,
                'start_date'   => $t->start_date instanceof \Carbon\Carbon ? $t->start_date->format('Y-m-d') : $t->start_date,
                'due_date'     => $t->due_date instanceof \Carbon\Carbon ? $t->due_date->format('Y-m-d') : $t->due_date,
                'status'       => $t->status,
                'progress_pct' => (int) ($t->progress_pct ?? 0),
            ])->values()->toArray(),
            'active_blockers_list' => $blockers->map(fn($b) => [
                'task_title'  => $b->task->title,
                'description' => $b->description,
                'reporter'    => $b->reportedBy?->name ?? '—',
            ])->values()->toArray(),
        ];
    }

    private function buildKpi(Project $project): array
    {
        return [
            'total'        => $project->tasks()->whereNull('parent_task_id')->count(),
            'done'         => $project->tasks()->whereNull('parent_task_id')->where('status', 'done')->count(),
            'in_progress'  => $project->tasks()->whereNull('parent_task_id')->where('status', 'in_progress')->count(),
            'overdue'      => $project->tasks()->whereNull('parent_task_id')
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->count(),
            'progress_pct' => $project->progress_pct ?? 0,
            'members'      => $project->members()->count(),
        ];
    }

    private function buildChartData(Project $project): array
    {
        return [
            'tasksByStatus' => [
                'todo'        => $project->tasks()->whereNull('parent_task_id')->where('status', 'todo')->count(),
                'in_progress' => $project->tasks()->whereNull('parent_task_id')->where('status', 'in_progress')->count(),
                'review'      => $project->tasks()->whereNull('parent_task_id')->where('status', 'review')->count(),
                'done'        => $project->tasks()->whereNull('parent_task_id')->where('status', 'done')->count(),
                'cancelled'   => $project->tasks()->whereNull('parent_task_id')->where('status', 'cancelled')->count(),
            ],
            'tasksByPriority' => [
                'critical' => $project->tasks()->whereNull('parent_task_id')->where('priority', 'critical')->count(),
                'high'     => $project->tasks()->whereNull('parent_task_id')->where('priority', 'high')->count(),
                'medium'   => $project->tasks()->whereNull('parent_task_id')->where('priority', 'medium')->count(),
                'low'      => $project->tasks()->whereNull('parent_task_id')->where('priority', 'low')->count(),
            ],
            'tasksByAssignee' => $project->tasks()
                ->whereNull('parent_task_id')
                ->whereNotNull('assignee_id')
                ->with('assignee:id,name')
                ->get()
                ->groupBy('assignee_id')
                ->map(fn($g) => ['name' => $g->first()->assignee->name, 'count' => $g->count()])
                ->values()
                ->toArray(),
        ];
    }
}
