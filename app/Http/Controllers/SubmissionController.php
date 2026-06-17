<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppSubmission;
use App\Models\RequestAssignment;
use App\Models\RequestDailyLog;
use App\Models\User;
use App\Services\SubmissionService;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function __construct(protected SubmissionService $service) {}

    public function index(Request $request)
    {
        $query = AppSubmission::with(['app', 'submitter', 'latestAssignment.assignee'])
            ->forUser(auth()->user());

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($appId = $request->get('app_id')) {
            $query->where('app_id', $appId);
        }
        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();
        $apps = App::active()->get();
        return view('submissions.index', compact('submissions', 'apps'));
    }

    public function create(App $app)
    {
        $app->load('initialFormTemplate');
        return view('submissions.create', compact('app'));
    }

    public function store(Request $request, App $app)
    {
        $app->load('initialFormTemplate');
        $fields = $app->initialFormTemplate?->schema['fields'] ?? [];
        $rules  = [];

        foreach ($fields as $field) {
            if (!empty($field['required'])) {
                $rules["form_{$field['id']}"] = 'required';
            }
        }
        $request->validate($rules);

        $formData = [];
        foreach ($fields as $field) {
            $key = "form_{$field['id']}";
            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('submissions', 'public');
                $formData[$field['id']] = $path;
            } else {
                $formData[$field['id']] = $request->get($key);
            }
        }

        $submission = AppSubmission::create([
            'app_id'       => $app->id,
            'submitter_id' => auth()->id(),
            'factory_id'   => auth()->user()->factory_id,
            'form_data'    => $formData,
            'status'       => 'draft',
        ]);

        $this->service->submit($submission);

        return redirect()->route('submissions.show', $submission)->with('success', 'ส่งคำร้องสำเร็จ');
    }

    public function show(AppSubmission $submission)
    {
        $this->authorizeView($submission);
        $submission->load([
            'app.initialFormTemplate',
            'app.revisionFormTemplate',
            'app.flow.nodes.stepFormTemplate',
            'app.flow.edges',
            'approvalActions.actor',
            'assignments.assignee',
            'dailyLogs.user',
            'submitter',
        ]);

        $currentNode = $submission->app->flow?->getNodeById($submission->current_node_id);
        $staff       = User::role(['it_staff', 'it_manager'])->active()->get();

        return view('submissions.show', compact('submission', 'staff', 'currentNode'));
    }

    public function approve(Request $request, AppSubmission $submission)
    {
        $data = $request->validate([
            'action'       => 'required|in:approve,reject,return_revision',
            'comment'      => 'nullable|string|max:1000',
            'step_form_data' => 'nullable|array',
        ]);

        $this->service->approve($submission, auth()->user(), $data['action'], $data['comment'] ?? null);

        return redirect()->route('submissions.show', $submission)->with('success', 'บันทึกการดำเนินการสำเร็จ');
    }

    public function assign(Request $request, AppSubmission $submission)
    {
        $data = $request->validate([
            'assignee_id' => 'required|exists:users,id',
            'due_date'    => 'nullable|date',
        ]);

        RequestAssignment::create([
            'submission_id' => $submission->id,
            'assignee_id'   => $data['assignee_id'],
            'assigned_by'   => auth()->id(),
            'due_date'      => $data['due_date'] ?? null,
            'assigned_at'   => now(),
        ]);

        $submission->update(['status' => 'in_review']);

        return back()->with('success', 'มอบหมายงานสำเร็จ');
    }

    public function addLog(Request $request, AppSubmission $submission)
    {
        $data = $request->validate([
            'progress_pct' => 'required|integer|min:0|max:100',
            'detail'       => 'required|string|max:1000',
            'log_date'     => 'required|date',
        ]);

        RequestDailyLog::create([
            'submission_id' => $submission->id,
            'user_id'       => auth()->id(),
            'log_date'      => $data['log_date'],
            'progress_pct'  => $data['progress_pct'],
            'detail'        => $data['detail'],
        ]);

        if ($data['progress_pct'] >= 100) {
            $submission->update(['status' => 'closed', 'closed_at' => now()]);
        }

        return back()->with('success', 'บันทึก Daily Log สำเร็จ');
    }

    public function resubmit(Request $request, AppSubmission $submission)
    {
        if ($submission->status !== 'returned') {
            abort(403, 'Submission is not in returned state');
        }
        if ($submission->submitter_id !== auth()->id()) {
            abort(403);
        }

        $submission->load('app.revisionFormTemplate');
        $fields = $submission->app->revisionFormTemplate?->schema['fields'] ?? [];
        $rules  = [];

        foreach ($fields as $field) {
            if (!empty($field['required'])) {
                $rules["form_{$field['id']}"] = 'required';
            }
        }
        $request->validate($rules);

        $formData = [];
        foreach ($fields as $field) {
            $key = "form_{$field['id']}";
            $formData[$field['id']] = $request->get($key);
        }

        $this->service->resubmitAfterRevision($submission, $formData);

        return redirect()->route('submissions.show', $submission)->with('success', 'ส่งคำร้องใหม่สำเร็จ');
    }

    protected function authorizeView(AppSubmission $submission): void
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'it_manager', 'it_staff', 'team_lead'])) {
            if ($submission->submitter_id !== $user->id) {
                abort(403);
            }
        }
    }
}
