<?php

namespace Database\Seeders;

use App\Models\Master;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $hqFactory = Master::where('code', 'NPT-HQ')->first();
        $f1Factory = Master::where('code', 'NPT-F1')->first() ?? $hqFactory;

        $emp002 = User::where('employee_code', 'EMP002')->first();
        $emp003 = User::where('employee_code', 'EMP003')->first();
        $emp004 = User::where('employee_code', 'EMP004')->first();
        $emp005 = User::where('employee_code', 'EMP005')->first();
        $emp006 = User::where('employee_code', 'EMP006')->first();

        if (!$emp002 || !$hqFactory) {
            $this->command->warn('ProjectSeeder: Required users/factories not found. Skipping.');
            return;
        }

        // ─── Project 1: IT Infrastructure Upgrade ────────────────────
        $p1 = Project::firstOrCreate(['name' => 'IT Infrastructure Upgrade'], [
            'description'   => 'Upgrade all core IT infrastructure including servers, network switches, and security systems.',
            'objective'     => 'Modernize IT infrastructure to support business growth and improve system reliability.',
            'factory_id'    => $hqFactory->id,
            'manager_id'    => $emp002->id,
            'start_date'    => '2026-06-01',
            'end_date'      => '2026-09-30',
            'status'        => 'active',
            'priority'      => 'high',
            'color'         => '#6366f1',
            'is_cross_factory' => false,
            'progress_pct'  => 30,
        ]);

        // Members P1
        $p1Members = array_filter([$emp002, $emp003, $emp004], fn($u) => $u !== null);
        foreach ($p1Members as $u) {
            ProjectMember::firstOrCreate(
                ['project_id' => $p1->id, 'user_id' => $u->id],
                ['factory_id' => $hqFactory->id, 'role' => $u->id === $emp002->id ? 'manager' : 'member', 'joined_at' => now()]
            );
        }

        // Milestones P1
        $ms1 = ProjectMilestone::firstOrCreate(['project_id' => $p1->id, 'name' => 'Planning Complete'], [
            'due_date' => '2026-06-15', 'is_completed' => true, 'completed_at' => '2026-06-15',
        ]);
        $ms2 = ProjectMilestone::firstOrCreate(['project_id' => $p1->id, 'name' => 'Development & Setup'], [
            'due_date' => '2026-07-31',
        ]);
        $ms3 = ProjectMilestone::firstOrCreate(['project_id' => $p1->id, 'name' => 'Testing & QA'], [
            'due_date' => '2026-08-31',
        ]);
        $ms4 = ProjectMilestone::firstOrCreate(['project_id' => $p1->id, 'name' => 'Go Live'], [
            'due_date' => '2026-09-30',
        ]);

        // Tasks P1
        $tasksP1 = [
            ['Requirements analysis', 'done', 'high', $emp002, '2026-06-01', '2026-06-10', $ms1->id, 100],
            ['Network topology design', 'done', 'high', $emp003, '2026-06-05', '2026-06-15', $ms1->id, 100],
            ['Vendor selection', 'in_progress', 'medium', $emp002, '2026-06-16', '2026-06-30', null, 60],
            ['Server procurement', 'in_progress', 'high', $emp003, '2026-06-20', '2026-07-15', $ms2->id, 40],
            ['Network switch installation', 'todo', 'high', $emp004, '2026-07-01', '2026-07-20', $ms2->id, 0],
            ['Firewall configuration', 'todo', 'critical', $emp003, '2026-07-10', '2026-07-25', $ms2->id, 0],
            ['Server OS installation', 'todo', 'high', $emp004, '2026-07-20', '2026-07-31', $ms2->id, 0],
            ['Integration testing', 'todo', 'high', $emp002, '2026-08-01', '2026-08-20', $ms3->id, 0],
            ['Performance testing', 'todo', 'medium', $emp003, '2026-08-15', '2026-08-31', $ms3->id, 0],
            ['Go-live cutover', 'todo', 'critical', $emp002, '2026-09-25', '2026-09-30', $ms4->id, 0],
        ];

        $createdTasksP1 = [];
        foreach ($tasksP1 as $i => [$title, $status, $priority, $assignee, $start, $due, $msId, $pct]) {
            $task = ProjectTask::firstOrCreate(['project_id' => $p1->id, 'title' => $title], [
                'status'       => $status,
                'priority'     => $priority,
                'assignee_id'  => $assignee?->id,
                'created_by'   => $emp002->id,
                'start_date'   => $start,
                'due_date'     => $due,
                'milestone_id' => $msId,
                'progress_pct' => $pct,
                'sort_order'   => $i,
                'completed_at' => $status === 'done' ? now() : null,
            ]);
            $createdTasksP1[] = $task;
        }

        // Add dependency: task index 4 depends on task index 3
        if (count($createdTasksP1) >= 5) {
            \App\Models\ProjectTaskDependency::firstOrCreate([
                'task_id'            => $createdTasksP1[4]->id,
                'depends_on_task_id' => $createdTasksP1[3]->id,
            ], ['type' => 'finish_to_start']);
        }

        // Subtask on task index 2
        if (count($createdTasksP1) >= 3) {
            ProjectTask::firstOrCreate(
                ['project_id' => $p1->id, 'title' => 'Compare vendor quotes', 'parent_task_id' => $createdTasksP1[2]->id],
                ['status' => 'done', 'priority' => 'medium', 'assignee_id' => $emp003?->id,
                 'created_by' => $emp002->id, 'due_date' => '2026-06-25', 'progress_pct' => 100, 'completed_at' => now()]
            );
        }

        // ─── Project 2: Quality System Improvement ───────────────────
        $p2 = Project::firstOrCreate(['name' => 'Quality System Improvement'], [
            'description'      => 'Improve quality management systems across factories to meet ISO 9001:2015 requirements.',
            'objective'        => 'Achieve ISO certification and reduce defect rates by 30%.',
            'factory_id'       => $f1Factory->id,
            'manager_id'       => $emp005 ? $emp005->id : $emp002->id,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-12-31',
            'status'           => 'planning',
            'priority'         => 'medium',
            'color'            => '#16a34a',
            'is_cross_factory' => true,
            'progress_pct'     => 0,
        ]);

        // Members P2
        $p2Manager = $emp005 ?? $emp002;
        ProjectMember::firstOrCreate(
            ['project_id' => $p2->id, 'user_id' => $p2Manager->id],
            ['factory_id' => $f1Factory->id, 'role' => 'manager', 'joined_at' => now()]
        );
        if ($emp006) {
            ProjectMember::firstOrCreate(
                ['project_id' => $p2->id, 'user_id' => $emp006->id],
                ['factory_id' => $hqFactory->id, 'role' => 'member', 'joined_at' => now()]
            );
        }

        // Tasks P2
        $tasksP2 = [
            ['Gap analysis vs ISO 9001', 'todo', 'high', $p2Manager, '2026-07-01', '2026-07-31', 0],
            ['Define quality KPIs', 'todo', 'medium', $emp006, '2026-07-15', '2026-08-15', 0],
            ['Document quality procedures', 'todo', 'medium', $p2Manager, '2026-08-01', '2026-09-30', 0],
            ['Staff training program', 'todo', 'high', $emp006, '2026-09-01', '2026-10-31', 0],
            ['Internal audit', 'todo', 'critical', $p2Manager, '2026-11-01', '2026-11-30', 0],
        ];

        foreach ($tasksP2 as $i => [$title, $status, $priority, $assignee, $start, $due, $pct]) {
            ProjectTask::firstOrCreate(['project_id' => $p2->id, 'title' => $title], [
                'status'       => $status,
                'priority'     => $priority,
                'assignee_id'  => $assignee?->id,
                'created_by'   => $p2Manager->id,
                'start_date'   => $start,
                'due_date'     => $due,
                'progress_pct' => $pct,
                'sort_order'   => $i,
            ]);
        }
    }
}
