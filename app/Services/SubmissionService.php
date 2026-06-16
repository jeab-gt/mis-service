<?php

namespace App\Services;

use App\Models\AppSubmission;
use App\Models\ApprovalAction;
use App\Models\ApprovalStep;
use App\Models\OptionSet;
use App\Models\RequestDailyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SubmissionService
{
    public function __construct(protected NotificationService $notificationService) {}

    // ─── Submit ──────────────────────────────────────────────────────

    public function submit(AppSubmission $submission): void
    {
        if ($this->isGraphFlow($submission->app)) {
            $this->submitGraph($submission);
        } else {
            $this->submitLegacy($submission);
        }
    }

    private function submitGraph(AppSubmission $submission): void
    {
        $schema = $submission->app->flow_schema;
        $nodes  = collect($schema['nodes'] ?? []);
        $edges  = collect($schema['edges'] ?? []);

        $startNode = $nodes->firstWhere('type', 'start');
        if (!$startNode) return;

        $firstEdge = $edges->firstWhere('from', $startNode['id']);
        $firstNode = $firstEdge ? $nodes->firstWhere('id', $firstEdge['to']) : null;

        $submission->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
            'current_step' => $firstNode ? $firstNode['id'] : $startNode['id'],
        ]);

        activity()->performedOn($submission)->causedBy(auth()->user())->log('submitted');

        if ($firstNode) {
            $this->notifyApproversForNode($firstNode, $submission);
        }
    }

    private function submitLegacy(AppSubmission $submission): void
    {
        $submission->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
            'current_step' => '1',
        ]);

        activity()->performedOn($submission)->causedBy(auth()->user())->log('submitted');

        $step = $submission->app->approvalSteps()->where('step_order', 1)->first();
        if ($step) {
            $users = $this->getApproversForStep($submission, $step);
            foreach ($users as $u) {
                $this->notificationService->notify($u, 'approval_required', [
                    'submission_id' => $submission->id,
                    'app_name'      => $submission->app->name,
                ]);
            }
        }
    }

    // ─── Approve / Reject ────────────────────────────────────────────

    public function approve(AppSubmission $submission, User $actor, string $action, ?string $comment = null): void
    {
        if ($this->isGraphFlow($submission->app)) {
            $this->approveGraph($submission, $actor, $action, $comment);
        } else {
            $this->approveLegacy($submission, $actor, $action, $comment);
        }
    }

    private function approveGraph(AppSubmission $submission, User $actor, string $action, ?string $comment = null): void
    {
        $schema        = $submission->app->flow_schema;
        $nodes         = collect($schema['nodes'] ?? []);
        $edges         = collect($schema['edges'] ?? []);
        $currentNodeId = $submission->current_step;
        $currentNode   = $nodes->firstWhere('id', $currentNodeId);

        ApprovalAction::create([
            'submission_id' => $submission->id,
            'step_id'       => null,
            'node_id'       => $currentNodeId,
            'actor_id'      => $actor->id,
            'action'        => $action,
            'comment'       => $comment,
            'acted_at'      => now(),
        ]);

        activity()->performedOn($submission)->causedBy($actor)->log($action);

        // For all_must: check if all approvers for this node have acted
        $actionType = $currentNode['action_type'] ?? 'any_one';
        if ($actionType === 'all_must') {
            $approvers   = $this->getApproversForNode($currentNode, $submission);
            $actedIds    = ApprovalAction::where('submission_id', $submission->id)
                ->where('node_id', $currentNodeId)
                ->where('action', $action)
                ->pluck('actor_id');
            $allActed = $approvers->pluck('id')->diff($actedIds)->isEmpty();
            if (!$allActed) {
                return; // Still waiting for others
            }
        }

        // Follow the edge matching the action
        $nextEdge = $edges->first(
            fn($e) => $e['from'] === $currentNodeId && ($e['label'] ?? null) === $action
        ) ?? $edges->firstWhere('from', $currentNodeId);

        if (!$nextEdge) return;

        $nextNode = $nodes->firstWhere('id', $nextEdge['to']);
        if (!$nextNode) return;

        $this->transitionToNode($submission, $nextNode);
    }

    private function transitionToNode(AppSubmission $submission, array $node): void
    {
        match ($node['type']) {
            'approval' => $submission->update(['current_step' => $node['id'], 'status' => 'in_review']),
            'end_approved' => $submission->update(['status' => 'approved', 'closed_at' => now()]),
            'end_rejected' => $submission->update(['status' => 'rejected', 'closed_at' => now()]),
            'return_revision' => $submission->update(['current_step' => $node['id'], 'status' => 'returned']),
            default => null,
        };

        match ($node['type']) {
            'approval' => $this->notifyApproversForNode($node, $submission),
            'end_approved', 'end_rejected' => $this->notificationService->notify(
                $submission->submitter,
                'approval_result',
                ['submission_id' => $submission->id, 'result' => $node['type'] === 'end_approved' ? 'approved' : 'rejected']
            ),
            'return_revision' => $this->notificationService->notify(
                $submission->submitter,
                'approval_result',
                ['submission_id' => $submission->id, 'result' => 'returned']
            ),
            default => null,
        };
    }

    private function approveLegacy(AppSubmission $submission, User $actor, string $action, ?string $comment = null): void
    {
        if ($action === 'reject') {
            $this->reject($submission, $actor, $comment);
            return;
        }

        $currentStep = $submission->app->approvalSteps()
            ->where('step_order', (int) $submission->current_step)
            ->first();

        ApprovalAction::create([
            'submission_id' => $submission->id,
            'step_id'       => $currentStep?->id,
            'actor_id'      => $actor->id,
            'action'        => $action,
            'comment'       => $comment,
            'acted_at'      => now(),
        ]);

        activity()->performedOn($submission)->causedBy($actor)->log($action);

        $totalSteps = $submission->app->approvalSteps()->count();
        $nextOrder  = (int) $submission->current_step + 1;

        if ($nextOrder <= $totalSteps) {
            $submission->update(['current_step' => (string) $nextOrder, 'status' => 'in_review']);
            $nextStep = $submission->app->approvalSteps()->where('step_order', $nextOrder)->first();
            if ($nextStep) {
                foreach ($this->getApproversForStep($submission, $nextStep) as $u) {
                    $this->notificationService->notify($u, 'approval_required', [
                        'submission_id' => $submission->id,
                        'app_name'      => $submission->app->name,
                        'step'          => $nextStep->name_th,
                    ]);
                }
            }
        } else {
            $submission->update(['status' => 'approved', 'closed_at' => now()]);
            $this->notificationService->notify($submission->submitter, 'approval_result', [
                'submission_id' => $submission->id,
                'result'        => 'approved',
            ]);
        }
    }

    public function reject(AppSubmission $submission, User $actor, ?string $comment = null): void
    {
        $currentStep = $submission->app->approvalSteps()
            ->where('step_order', (int) $submission->current_step)
            ->first();

        ApprovalAction::create([
            'submission_id' => $submission->id,
            'step_id'       => $currentStep?->id,
            'actor_id'      => $actor->id,
            'action'        => 'reject',
            'comment'       => $comment,
            'acted_at'      => now(),
        ]);

        $submission->update(['status' => 'rejected', 'closed_at' => now()]);

        activity()->performedOn($submission)->causedBy($actor)->log('rejected');

        $this->notificationService->notify($submission->submitter, 'approval_result', [
            'submission_id' => $submission->id,
            'result'        => 'rejected',
            'comment'       => $comment,
        ]);
    }

    // ─── Resubmit after revision ─────────────────────────────────────

    public function resubmitAfterRevision(AppSubmission $submission, array $newFormData): void
    {
        $schema        = $submission->app->flow_schema;
        $nodes         = collect($schema['nodes'] ?? []);
        $edges         = collect($schema['edges'] ?? []);
        $currentNodeId = $submission->current_step;
        $returnNode    = $nodes->firstWhere('id', $currentNodeId);

        if (!$returnNode || $returnNode['type'] !== 'return_revision') {
            throw new \LogicException('Submission is not in return_revision state');
        }

        $nextEdge = $edges->firstWhere('from', $currentNodeId);
        $nextNode = $nextEdge ? $nodes->firstWhere('id', $nextEdge['to']) : null;

        $submission->update([
            'form_data'    => $newFormData,
            'status'       => 'submitted',
            'submitted_at' => now(),
            'current_step' => $nextNode ? $nextNode['id'] : $currentNodeId,
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

    private function isGraphFlow(\App\Models\App $app): bool
    {
        $schema = $app->flow_schema;
        return isset($schema['nodes']) && !empty($schema['nodes']);
    }

    private function notifyApproversForNode(array $node, AppSubmission $submission): void
    {
        foreach ($this->getApproversForNode($node, $submission) as $u) {
            $this->notificationService->notify($u, 'approval_required', [
                'submission_id' => $submission->id,
                'app_name'      => $submission->app->name,
                'step'          => $node['name_th'] ?? '',
            ]);
        }
    }

    private function getApproversForNode(array $node, AppSubmission $submission): Collection
    {
        $source = $node['approver_source'] ?? 'role';
        $scope  = $node['scope'] ?? 'own_factory';

        switch ($source) {
            case 'role':
                $roleName = $node['approver_role'] ?? '';
                $query    = User::role($roleName)->where('is_active', true);
                return match ($scope) {
                    'own_factory'    => $query->where('factory_id', $submission->factory_id)->get(),
                    'parent_factory' => $query->where('is_parent_factory', true)->get(),
                    default          => $query->get(),
                };

            case 'specific_user':
                $userId = $node['approver_user_id'] ?? null;
                return $userId ? User::where('id', $userId)->where('is_active', true)->get() : collect();

            case 'option_set':
                $code   = $node['approver_option_set'] ?? null;
                $optSet = $code ? OptionSet::where('code', $code)->first() : null;
                if (!$optSet) return collect();
                $ids = array_column($optSet->getOptions($submission->factory_id), 'value');
                return User::whereIn('id', $ids)->where('is_active', true)->get();

            default:
                return collect();
        }
    }

    private function getApproversForStep(AppSubmission $submission, ApprovalStep $step): Collection
    {
        $roleName = $step->approverRole->name;
        $scope    = $step->scope ?? 'own_factory';

        $query = User::role($roleName)->where('is_active', true);

        return match ($scope) {
            'own_factory'    => $query->where('factory_id', $submission->factory_id)->get(),
            'parent_factory' => $query->where('is_parent_factory', true)->get(),
            'any_factory'    => $query->get(),
            default          => $query->get(),
        };
    }
}
