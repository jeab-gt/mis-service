<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTaskBlocker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = Project::with(['manager', 'factory', 'members.user'])
            ->forUser($user);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->priority && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }
        if ($request->factory_id) {
            $query->where('factory_id', $request->factory_id);
        }
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->my_projects) {
            $query->where(fn($q) => $q
                ->where('manager_id', $user->id)
                ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id))
            );
        }

        $projects  = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $factories = Master::where('type', 'factory')->orderBy('name_th')->get();

        return view('projects.index', compact('projects', 'factories'));
    }

    public function create()
    {
        $user      = Auth::user();
        $factories = Master::where('type', 'factory')->orderBy('name_th')->get();
        $users     = User::where('is_active', true)->orderBy('name')->get();

        return view('projects.create', compact('factories', 'users', 'user'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'objective'        => 'nullable|string',
            'factory_id'       => 'required|exists:masters,id',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'priority'         => 'required|in:low,medium,high,critical',
            'color'            => 'nullable|string|max:20',
            'budget'           => 'nullable|numeric|min:0',
            'is_cross_factory' => 'boolean',
            'member_ids'       => 'nullable|array',
            'member_ids.*'     => 'exists:users,id',
        ]);

        $data['manager_id']       = Auth::id();
        $data['status']           = 'planning';
        $data['is_cross_factory'] = $request->boolean('is_cross_factory');

        $project = Project::create($data);

        // Add manager as member
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => Auth::id(),
            'factory_id' => $data['factory_id'],
            'role'       => 'manager',
            'joined_at'  => now(),
        ]);

        // Add additional members
        foreach ($request->input('member_ids', []) as $uid) {
            if ($uid == Auth::id()) continue;
            $member = User::find($uid);
            if (!$member) continue;
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $uid],
                ['factory_id' => $member->factory_id ?? $data['factory_id'], 'role' => 'member', 'joined_at' => now()]
            );
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load([
            'manager', 'factory', 'submission',
            'members.user.factory',
            'milestones',
            'tasks' => fn($q) => $q->whereNull('parent_task_id')->with(['assignee', 'checklists', 'subtasks', 'comments', 'activeBlocker.reportedBy']),
            'comments.user',
            'attachments.uploader',
        ]);

        $memberUserIds = $project->members->pluck('user_id')->toArray();
        $memberUsers   = User::whereIn('id', $memberUserIds)->get();

        $kpi = [
            'total'       => $project->tasks->count(),
            'done'        => $project->tasks->where('status', 'done')->count(),
            'in_progress' => $project->tasks->where('status', 'in_progress')->count(),
            'overdue'     => $project->tasks->filter(fn($t) => $t->isOverdue())->count(),
        ];

        $upcomingMilestones = $project->milestones
            ->where('is_completed', false)
            ->sortBy('due_date')
            ->take(3);

        $kanbanGroups = [
            'todo'        => $project->tasks->where('status', 'todo')->sortBy('sort_order')->values(),
            'in_progress' => $project->tasks->where('status', 'in_progress')->sortBy('sort_order')->values(),
            'review'      => $project->tasks->where('status', 'review')->sortBy('sort_order')->values(),
            'done'        => $project->tasks->where('status', 'done')->sortBy('sort_order')->values(),
        ];

        $allUsers = User::where('is_active', true)->orderBy('name')->get();

        $activeBlockers = ProjectTaskBlocker::whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->whereNull('resolved_at')
            ->with(['task', 'reportedBy'])
            ->latest()
            ->get();

        return view('projects.show', compact(
            'project', 'kpi', 'upcomingMilestones',
            'kanbanGroups', 'memberUsers', 'allUsers', 'activeBlockers'
        ));
    }

    public function edit(Project $project)
    {
        $factories = Master::where('type', 'factory')->orderBy('name_th')->get();
        $users     = User::where('is_active', true)->orderBy('name')->get();

        return view('projects.edit', compact('project', 'factories', 'users'));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'objective'        => 'nullable|string',
            'factory_id'       => 'required|exists:masters,id',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date',
            'status'           => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority'         => 'required|in:low,medium,high,critical',
            'color'            => 'nullable|string|max:20',
            'budget'           => 'nullable|numeric|min:0',
            'is_cross_factory' => 'boolean',
        ]);

        $data['is_cross_factory'] = $request->boolean('is_cross_factory');
        $project->update($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    public function addMember(Request $request, Project $project)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:manager,lead,member,reviewer',
        ]);

        $user = User::findOrFail($data['user_id']);

        ProjectMember::firstOrCreate(
            ['project_id' => $project->id, 'user_id' => $data['user_id']],
            [
                'factory_id' => $user->factory_id ?? $project->factory_id,
                'role'       => $data['role'],
                'joined_at'  => now(),
            ]
        );

        return back()->with('success', 'Member added.');
    }

    public function removeMember(Project $project, User $user)
    {
        ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'Member removed.');
    }

    public function updateProgress(Project $project)
    {
        $project->recalculateProgress();
        return response()->json(['progress_pct' => $project->progress_pct]);
    }
}
