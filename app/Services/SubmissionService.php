<?php

namespace App\Services;

use App\Models\AppSubmission;
use App\Models\ApprovalAction;
use App\Models\Flow;
use App\Models\FlowNode;
use App\Models\OptionSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SubmissionService
{
    public function __construct(protected NotificationService $notificationService) {}

    // ─── Submit ──────────────────────────────────────────────────────

    public function submit(AppSubmission $submission): void
    {
        $flow = $submission->app->flow;

        if (!$flow) {
            $submission->update(['status' => 'submitted', 'submitted_at' => now()]);
            activity()->performedOn($submission)->causedBy(auth()->user())->log('submitted');
            return;
        }

        $startNode = $flow->getStartNode();
        if (!$startNode) {
            $submission->update(['status' => 'submitted', 'submitted_at' => now()]);
            return;
        }

        $nextNodes = $flow->getNextNodes($startNode->node_id);
        $firstNode = $nextNodes->first();

        $submission->update([
            'status'          => 'submitted',
            'submitted_at'    => now(),
            'current_node_id' => $firstNode ? $firstNode->node_id : $startNode->node_id,
        ]);

        activity()->performedOn($submission)->causedBy(auth()->user())->log('submitted');

        if ($firstNode) {
            $this->notifyApproversForNode($firstNode, $submission);
        }
    }

    // ─── Approve / Reject / Return ───────────────────────────────────

    public function approve(AppSubmission $submission, User $actor, string $action, ?string $comment = null, ?array $stepFormData = null): void
    {
        $flow          = $submission->app->flow;
        $currentNodeId = $submission->current_node_id;
        $currentNode   = $flow?->getNodeById($currentNodeId);

        ApprovalAction::create([
            'submission_id'  => $submission->id,
            'node_id'        => $currentNodeId,
            'actor_id'       => $actor->id,
            'action'         => $action,
            'comment'        => $comment,
            'step_form_data' => $stepFormData ?: null,
            'acted_at'       => now(),
        ]);

        activity()->performedOn($submission)->causedBy($actor)->log($action);

        if (!$flow || !$currentNode) {
            return;
        }

        // all_must: wait until all approvers have acted
        if ($currentNode->action_type === 'all_must') {
            $approvers = $this->getApproversForNode($currentNode, $submission);
            $actedIds  = ApprovalAction::where('submission_id', $submission->id)
                ->where('node_id', $currentNodeId)
                ->where('action', $action)
                ->pluck('actor_id');
            if ($approvers->pluck('id')->diff($actedIds)->isNotEmpty()) {
                return;
            }
        }

        // Find next edge matching action label, fallback to unlabeled edge
        $nextEdge = $flow->edges()
            ->where('from_node_id', $currentNodeId)
            ->where('label', $action)
            ->first()
            ?? $flow->edges()
                ->where('from_node_id', $currentNodeId)
                ->whereNull('label')
                ->first();

        if (!$nextEdge) return;

        $nextNode = $flow->getNodeById($nextEdge->to_node_id);
        if (!$nextNode) return;

        $this->transitionToNode($submission, $nextNode);
    }

    private function transitionToNode(AppSubmission $submission, FlowNode $node): void
    {
        match ($node->type) {
            'approval'        => $submission->update(['current_node_id' => $node->node_id, 'status' => 'in_review']),
            'end_approved'    => $submission->update(['status' => 'approved', 'closed_at' => now()]),
            'end_rejected'    => $submission->update(['status' => 'rejected', 'closed_at' => now()]),
            'return_revision' => $submission->update(['current_node_id' => $node->node_id, 'status' => 'returned']),
            default           => null,
        };

        match ($node->type) {
            'approval'     => $this->notifyApproversForNode($node, $submission),
            'end_approved' => $this->notificationService->notify(
                $submission->submitter, 'approval_result',
                ['submission_id' => $submission->id, 'result' => 'approved']
            ),
            'end_rejected' => $this->notificationService->notify(
                $submission->submitter, 'approval_result',
                ['submission_id' => $submission->id, 'result' => 'rejected']
            ),
            'return_revision' => $this->notificationService->notify(
                $submission->submitter, 'approval_result',
                ['submission_id' => $submission->id, 'result' => 'returned']
            ),
            default => null,
        };
    }

    // ─── Resubmit after revision ─────────────────────────────────────

    public function resubmitAfterRevision(AppSubmission $submission, array $newFormData): void
    {
        $flow          = $submission->app->flow;
        $currentNodeId = $submission->current_node_id;
        $returnNode    = $flow?->getNodeById($currentNodeId);

        if (!$returnNode || $returnNode->type !== 'return_revision') {
            throw new \LogicException('Submission is not in return_revision state');
        }

        $nextEdge = $flow->edges()->where('from_node_id', $currentNodeId)->first();
        $nextNode = $nextEdge ? $flow->getNodeById($nextEdge->to_node_id) : null;

        $submission->update([
            'form_data'       => array_merge($submission->form_data ?? [], $newFormData),
            'status'          => 'submitted',
            'submitted_at'    => now(),
            'current_node_id' => $nextNode ? $nextNode->node_id : $currentNodeId,
        ]);

        if ($nextNode) {
            $this->notifyApproversForNode($nextNode, $submission);
        }
    }

    // ─── Progress ────────────────────────────────────────────────────

    public function getProgress(AppSubmission $submission): int
    {
        $latest = $submission->dailyLogs()->latest('log_date')->first();
        return $latest ? $latest->progress_pct : 0;
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function notifyApproversForNode(FlowNode $node, AppSubmission $submission): void
    {
        foreach ($this->getApproversForNode($node, $submission) as $u) {
            $this->notificationService->notify($u, 'approval_required', [
                'submission_id' => $submission->id,
                'app_name'      => $submission->app->name,
                'step'          => $node->name_th ?? '',
            ]);
        }
    }

    public function getApproversForNode(FlowNode $node, AppSubmission $submission): Collection
    {
        $source = $node->approver_source ?? 'role';
        $scope  = $node->scope ?? 'own_factory';

        switch ($source) {
            case 'role':
                if (!$node->approverRole) return collect();
                $query = User::role($node->approverRole->name)->where('is_active', true);
                return match ($scope) {
                    'own_factory'    => $query->where('factory_id', $submission->factory_id)->get(),
                    'parent_factory' => $query->where('is_parent_factory', true)->get(),
                    default          => $query->get(),
                };

            case 'specific_user':
                return $node->approver_user_id
                    ? User::where('id', $node->approver_user_id)->where('is_active', true)->get()
                    : collect();

            case 'option_set':
                $code   = $node->approver_option_set_code;
                $optSet = $code ? OptionSet::where('code', $code)->first() : null;
                if (!$optSet) return collect();
                $ids = array_column($optSet->getOptions($submission->factory_id), 'value');
                return User::whereIn('id', $ids)->where('is_active', true)->get();

            default:
                return collect();
        }
    }
}
