<?php

namespace App\Console\Commands;

use App\Models\RequestAssignment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOverdue extends Command
{
    protected $signature = 'mis:check-overdue';
    protected $description = 'Notify assignees and managers about overdue tasks';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $overdue = RequestAssignment::with(['submission.app', 'assignee', 'assigner'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->whereHas('submission', fn($q) => $q->whereNotIn('status', ['approved', 'rejected', 'closed']))
            ->get();

        $this->info("Found {$overdue->count()} overdue assignment(s).");

        foreach ($overdue as $assignment) {
            $submission = $assignment->submission;
            $payload = [
                'submission_id' => $submission->id,
                'app_name'      => $submission->app?->name ?? '',
                'due_date'      => $assignment->due_date?->format('d/m/Y'),
            ];

            // Notify the assignee
            if ($assignment->assignee) {
                $this->notificationService->notify($assignment->assignee, 'overdue', $payload);
            }

            // Notify team_lead / it_manager in the same factory
            if ($submission->factory_id) {
                $managers = \App\Models\User::role(['team_lead', 'it_manager'])
                    ->where('factory_id', $submission->factory_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $assignment->assignee_id)
                    ->get();

                foreach ($managers as $manager) {
                    $this->notificationService->notify($manager, 'overdue', $payload);
                }
            }

            Log::info("Overdue notification sent for submission #{$submission->id}");
        }

        $this->info('Done.');
    }
}
