<?php

namespace App\Services;

use App\Models\MisNotification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, string $type, array $data = []): void
    {
        $titles = $this->getTitles($type, $data);

        MisNotification::create([
            'user_id'   => $user->id,
            'type'      => $type,
            'title_th'  => $titles['th'],
            'title_en'  => $titles['en'],
            'body_th'   => $titles['body_th'] ?? null,
            'body_en'   => $titles['body_en'] ?? null,
            'payload'   => $data,
        ]);
    }

    protected function getTitles(string $type, array $data): array
    {
        return match($type) {
            'approval_required' => [
                'th' => 'รออนุมัติ: ' . ($data['app_name'] ?? ''),
                'en' => 'Approval Required: ' . ($data['app_name'] ?? ''),
                'body_th' => 'มี Request ใหม่รออนุมัติ',
                'body_en' => 'A new request is waiting for your approval',
            ],
            'approval_result' => [
                'th' => ($data['result'] === 'approved' ? 'อนุมัติแล้ว' : 'ปฏิเสธแล้ว'),
                'en' => ($data['result'] === 'approved' ? 'Request Approved' : 'Request Rejected'),
                'body_th' => $data['comment'] ?? null,
                'body_en' => $data['comment'] ?? null,
            ],
            'assigned' => [
                'th' => 'ได้รับมอบหมายงานใหม่',
                'en' => 'New Assignment',
                'body_th' => 'คุณได้รับมอบหมายงาน Request #' . ($data['submission_id'] ?? ''),
                'body_en' => 'You have been assigned to Request #' . ($data['submission_id'] ?? ''),
            ],
            'overdue' => [
                'th' => 'งานเกินกำหนด',
                'en' => 'Overdue Task',
                'body_th' => 'Request #' . ($data['submission_id'] ?? '') . ' เกินกำหนดเวลาแล้ว',
                'body_en' => 'Request #' . ($data['submission_id'] ?? '') . ' is overdue',
            ],
            'task_done' => [
                'th' => 'งานเสร็จสิ้น',
                'en' => 'Task Completed',
                'body_th' => ($data['assignee_name'] ?? '') . ' ดำเนินการ Request #' . ($data['submission_id'] ?? '') . ' เสร็จสิ้นแล้ว (100%)',
                'body_en' => ($data['assignee_name'] ?? '') . ' completed Request #' . ($data['submission_id'] ?? '') . ' (100%)',
            ],
            default => ['th' => 'แจ้งเตือน', 'en' => 'Notification'],
        };
    }
}
