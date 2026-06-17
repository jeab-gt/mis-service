<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\Flow;
use App\Models\FlowEdge;
use App\Models\FlowNode;
use App\Models\FormTemplate;
use App\Models\Master;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFactoryRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Permissions ────────────────────────────────────────────
        $permissions = [
            'master.view', 'master.create', 'master.edit', 'master.delete',
            'user.view', 'user.create', 'user.edit', 'user.delete',
            'app.view', 'app.create', 'app.edit', 'app.delete',
            'submission.view', 'submission.create', 'submission.approve',
            'submission.assign', 'submission.export',
            'report.view', 'report.export',
            'setting.view', 'setting.edit',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ─── Roles ──────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin',  'guard_name' => 'web']);
        $itManager  = Role::firstOrCreate(['name' => 'it_manager',   'guard_name' => 'web']);
        $itStaff    = Role::firstOrCreate(['name' => 'it_staff',     'guard_name' => 'web']);
        $teamLead   = Role::firstOrCreate(['name' => 'team_lead',    'guard_name' => 'web']);
        $requester  = Role::firstOrCreate(['name' => 'requester',    'guard_name' => 'web']);

        $superAdmin->syncPermissions($permissions);
        $itManager->syncPermissions(array_diff($permissions, ['user.delete', 'setting.edit']));
        $itStaff->syncPermissions(['master.view', 'app.view', 'submission.view', 'submission.create', 'submission.assign', 'report.view']);
        $teamLead->syncPermissions(['submission.view', 'submission.approve', 'report.view', 'report.export']);
        $requester->syncPermissions(['submission.view', 'submission.create']);

        // ─── NPT Group Master Hierarchy ──────────────────────────────
        $nptGroup = Master::firstOrCreate(['code' => 'NPT-COM'], [
            'parent_id' => null, 'factory_id' => null, 'type' => 'company',
            'name_th' => 'กลุ่ม NPT', 'name_en' => 'NPT Group',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $hqFactory = Master::firstOrCreate(['code' => 'NPT-HQ'], [
            'parent_id' => $nptGroup->id, 'factory_id' => null, 'type' => 'factory',
            'name_th' => 'สำนักงานใหญ่ (Head Office)', 'name_en' => 'Head Office',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $hqItDept = Master::firstOrCreate(['code' => 'NPT-HQ-IT'], [
            'parent_id' => $hqFactory->id, 'factory_id' => $hqFactory->id, 'type' => 'department',
            'name_th' => 'แผนก IT (HQ)', 'name_en' => 'IT Department (HQ)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $hqItInfra = Master::firstOrCreate(['code' => 'NPT-HQ-INFRA'], [
            'parent_id' => $hqItDept->id, 'factory_id' => $hqFactory->id, 'type' => 'section',
            'name_th' => 'IT Infrastructure (HQ)', 'name_en' => 'IT Infrastructure (HQ)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $hqItApp = Master::firstOrCreate(['code' => 'NPT-HQ-APP'], [
            'parent_id' => $hqItDept->id, 'factory_id' => $hqFactory->id, 'type' => 'section',
            'name_th' => 'IT Application (HQ)', 'name_en' => 'IT Application (HQ)',
            'sort_order' => 2, 'is_active' => true,
        ]);

        $factory1 = Master::firstOrCreate(['code' => 'NPT-F1'], [
            'parent_id' => $nptGroup->id, 'factory_id' => null, 'type' => 'factory',
            'name_th' => 'โรงงานที่ 1', 'name_en' => 'Factory 1',
            'sort_order' => 2, 'is_active' => true,
        ]);

        $f1ProdDept = Master::firstOrCreate(['code' => 'NPT-F1-PROD'], [
            'parent_id' => $factory1->id, 'factory_id' => $factory1->id, 'type' => 'department',
            'name_th' => 'แผนกผลิต (F1)', 'name_en' => 'Production Dept (F1)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $f1SecA = Master::firstOrCreate(['code' => 'NPT-F1-LA'], [
            'parent_id' => $f1ProdDept->id, 'factory_id' => $factory1->id, 'type' => 'section',
            'name_th' => 'Line A (F1)', 'name_en' => 'Line A (F1)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $f1SecB = Master::firstOrCreate(['code' => 'NPT-F1-LB'], [
            'parent_id' => $f1ProdDept->id, 'factory_id' => $factory1->id, 'type' => 'section',
            'name_th' => 'Line B (F1)', 'name_en' => 'Line B (F1)',
            'sort_order' => 2, 'is_active' => true,
        ]);

        $f1QaDept = Master::firstOrCreate(['code' => 'NPT-F1-QA'], [
            'parent_id' => $factory1->id, 'factory_id' => $factory1->id, 'type' => 'department',
            'name_th' => 'แผนก QA (F1)', 'name_en' => 'QA Department (F1)',
            'sort_order' => 2, 'is_active' => true,
        ]);

        Master::firstOrCreate(['code' => 'NPT-F1-QC'], [
            'parent_id' => $f1QaDept->id, 'factory_id' => $factory1->id, 'type' => 'section',
            'name_th' => 'QC Section (F1)', 'name_en' => 'QC Section (F1)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $f1ItSec = Master::firstOrCreate(['code' => 'NPT-F1-IT'], [
            'parent_id' => $factory1->id, 'factory_id' => $factory1->id, 'type' => 'section',
            'name_th' => 'IT Section (F1)', 'name_en' => 'IT Section (F1)',
            'sort_order' => 3, 'is_active' => true,
        ]);

        $factory2 = Master::firstOrCreate(['code' => 'NPT-F2'], [
            'parent_id' => $nptGroup->id, 'factory_id' => null, 'type' => 'factory',
            'name_th' => 'โรงงานที่ 2', 'name_en' => 'Factory 2',
            'sort_order' => 3, 'is_active' => true,
        ]);

        $f2ProdDept = Master::firstOrCreate(['code' => 'NPT-F2-PROD'], [
            'parent_id' => $factory2->id, 'factory_id' => $factory2->id, 'type' => 'department',
            'name_th' => 'แผนกผลิต (F2)', 'name_en' => 'Production Dept (F2)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $f2SecA = Master::firstOrCreate(['code' => 'NPT-F2-LA'], [
            'parent_id' => $f2ProdDept->id, 'factory_id' => $factory2->id, 'type' => 'section',
            'name_th' => 'Line A (F2)', 'name_en' => 'Line A (F2)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        Master::firstOrCreate(['code' => 'NPT-F2-LB'], [
            'parent_id' => $f2ProdDept->id, 'factory_id' => $factory2->id, 'type' => 'section',
            'name_th' => 'Line B (F2)', 'name_en' => 'Line B (F2)',
            'sort_order' => 2, 'is_active' => true,
        ]);

        $f2MaintDept = Master::firstOrCreate(['code' => 'NPT-F2-MAINT'], [
            'parent_id' => $factory2->id, 'factory_id' => $factory2->id, 'type' => 'department',
            'name_th' => 'แผนกซ่อมบำรุง (F2)', 'name_en' => 'Maintenance Dept (F2)',
            'sort_order' => 2, 'is_active' => true,
        ]);

        Master::firstOrCreate(['code' => 'NPT-F2-MAINT-S'], [
            'parent_id' => $f2MaintDept->id, 'factory_id' => $factory2->id, 'type' => 'section',
            'name_th' => 'Maintenance Section (F2)', 'name_en' => 'Maintenance Section (F2)',
            'sort_order' => 1, 'is_active' => true,
        ]);

        // ─── Users EMP001–EMP008 ─────────────────────────────────────
        $u1 = User::updateOrCreate(['employee_code' => 'EMP001'], [
            'name' => 'System Admin', 'name_th' => 'ผู้ดูแลระบบ', 'name_en' => 'System Administrator',
            'email' => 'admin@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $hqFactory->id, 'section_id' => $hqItInfra->id,
            'is_active' => true, 'is_parent_factory' => false,
        ]);
        $u1->syncRoles(['super_admin']);

        $u2 = User::updateOrCreate(['employee_code' => 'EMP002'], [
            'name' => 'IT Manager HQ', 'name_th' => 'ผู้จัดการ IT (HQ)', 'name_en' => 'IT Manager HQ',
            'email' => 'manager@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $hqFactory->id, 'section_id' => $hqItDept->id,
            'is_active' => true, 'is_parent_factory' => true,
        ]);
        $u2->syncRoles(['it_manager']);

        $u3 = User::updateOrCreate(['employee_code' => 'EMP003'], [
            'name' => 'IT Staff HQ', 'name_th' => 'เจ้าหน้าที่ IT (HQ)', 'name_en' => 'IT Staff HQ',
            'email' => 'staff@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $hqFactory->id, 'section_id' => $hqItApp->id,
            'is_active' => true, 'is_parent_factory' => true,
        ]);
        $u3->syncRoles(['it_staff']);

        $u4 = User::updateOrCreate(['employee_code' => 'EMP004'], [
            'name' => 'IT Manager F1', 'name_th' => 'ผู้จัดการ IT (F1)', 'name_en' => 'IT Manager Factory 1',
            'email' => 'manager.f1@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $factory1->id, 'section_id' => $f1ItSec->id,
            'is_active' => true, 'is_parent_factory' => false,
        ]);
        $u4->syncRoles(['it_manager']);

        $u5 = User::updateOrCreate(['employee_code' => 'EMP005'], [
            'name' => 'Team Lead F1', 'name_th' => 'หัวหน้าทีม (F1)', 'name_en' => 'Team Lead Factory 1',
            'email' => 'lead@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $factory1->id, 'section_id' => $f1SecA->id,
            'is_active' => true, 'is_parent_factory' => false,
        ]);
        $u5->syncRoles(['team_lead']);

        $u6 = User::updateOrCreate(['employee_code' => 'EMP006'], [
            'name' => 'Requester F1', 'name_th' => 'ผู้ขอ (F1)', 'name_en' => 'Requester Factory 1',
            'email' => 'user.f1@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $factory1->id, 'section_id' => $f1SecB->id,
            'is_active' => true, 'is_parent_factory' => false,
        ]);
        $u6->syncRoles(['requester']);

        $u7 = User::updateOrCreate(['employee_code' => 'EMP007'], [
            'name' => 'IT Manager F2', 'name_th' => 'ผู้จัดการ IT (F2)', 'name_en' => 'IT Manager Factory 2',
            'email' => 'manager.f2@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $factory2->id, 'section_id' => $f2ProdDept->id,
            'is_active' => true, 'is_parent_factory' => false,
        ]);
        $u7->syncRoles(['it_manager']);

        $u8 = User::updateOrCreate(['employee_code' => 'EMP008'], [
            'name' => 'Requester F2', 'name_th' => 'ผู้ขอ (F2)', 'name_en' => 'Requester Factory 2',
            'email' => 'user.f2@mis.local', 'password' => bcrypt('password'),
            'factory_id' => $factory2->id, 'section_id' => $f2SecA->id,
            'is_active' => true, 'is_parent_factory' => false,
        ]);
        $u8->syncRoles(['requester']);

        UserFactoryRole::firstOrCreate(['user_id' => $u4->id, 'factory_id' => $factory1->id, 'role_id' => $itManager->id]);
        UserFactoryRole::firstOrCreate(['user_id' => $u5->id, 'factory_id' => $factory1->id, 'role_id' => $teamLead->id]);
        UserFactoryRole::firstOrCreate(['user_id' => $u7->id, 'factory_id' => $factory2->id, 'role_id' => $itManager->id]);

        // ─── Form Templates ──────────────────────────────────────────
        $tplItRequest = FormTemplate::create([
            'name'       => 'IT Request Form',
            'category'   => 'maintenance',
            'created_by' => $u1->id,
            'schema'     => ['fields' => [
                ['id' => 'f1', 'type' => 'text',     'label_th' => 'หัวข้อปัญหา',     'label_en' => 'Issue Title',   'required' => true,  'width' => 'full'],
                ['id' => 'f2', 'type' => 'select',   'label_th' => 'ประเภทอุปกรณ์',   'label_en' => 'Device Type',   'required' => true,  'width' => 'half', 'data_source' => 'option_set', 'option_set_code' => 'device_types'],
                ['id' => 'f3', 'type' => 'select',   'label_th' => 'ระดับความสำคัญ',  'label_en' => 'Priority',      'required' => true,  'width' => 'half', 'data_source' => 'option_set', 'option_set_code' => 'priority_levels'],
                ['id' => 'f4', 'type' => 'textarea', 'label_th' => 'รายละเอียดปัญหา', 'label_en' => 'Problem Detail','required' => true,  'width' => 'full'],
                ['id' => 'f5', 'type' => 'file',     'label_th' => 'แนบรูปภาพ',       'label_en' => 'Attach Image',  'required' => false, 'width' => 'full', 'accept' => 'image/*'],
            ]],
        ]);

        $tplProjectRequest = FormTemplate::create([
            'name'       => 'Project Request Form',
            'category'   => 'development',
            'created_by' => $u1->id,
            'schema'     => ['fields' => [
                ['id' => 'f1', 'type' => 'text',     'label_th' => 'ชื่อโปรเจกต์',   'label_en' => 'Project Name',  'required' => true,  'width' => 'full'],
                ['id' => 'f2', 'type' => 'textarea', 'label_th' => 'วัตถุประสงค์',   'label_en' => 'Objective',     'required' => true,  'width' => 'full'],
                ['id' => 'f3', 'type' => 'textarea', 'label_th' => 'ขอบเขตงาน',      'label_en' => 'Scope',         'required' => true,  'width' => 'full'],
                ['id' => 'f4', 'type' => 'date',     'label_th' => 'วันที่ต้องการ',   'label_en' => 'Required Date', 'required' => true,  'width' => 'half'],
                ['id' => 'f5', 'type' => 'select',   'label_th' => 'แผนก',           'label_en' => 'Department',    'required' => true,  'width' => 'half', 'data_source' => 'option_set', 'option_set_code' => 'departments'],
                ['id' => 'f6', 'type' => 'textarea', 'label_th' => 'หมายเหตุ',       'label_en' => 'Remark',        'required' => false, 'width' => 'full'],
            ]],
        ]);

        $tplItReceive = FormTemplate::create([
            'name'       => 'IT Receive Form',
            'category'   => 'step_form',
            'created_by' => $u1->id,
            'schema'     => ['fields' => [
                ['id' => 'f1', 'type' => 'date',     'label_th' => 'วันที่รับงาน',              'label_en' => 'Received Date',    'required' => true,  'width' => 'half'],
                ['id' => 'f2', 'type' => 'text',     'label_th' => 'ผู้รับงาน',                 'label_en' => 'Received By',      'required' => true,  'width' => 'half', 'readonly' => true],
                ['id' => 'f3', 'type' => 'date',     'label_th' => 'ETA วันที่คาดว่าจะเสร็จ',   'label_en' => 'ETA Date',         'required' => false, 'width' => 'half'],
                ['id' => 'f4', 'type' => 'textarea', 'label_th' => 'หมายเหตุ',                  'label_en' => 'Remark',           'required' => false, 'width' => 'full'],
            ]],
        ]);

        $tplApproval = FormTemplate::create([
            'name'       => 'Approval Form',
            'category'   => 'step_form',
            'created_by' => $u1->id,
            'schema'     => ['fields' => [
                ['id' => 'f1', 'type' => 'radio',    'label_th' => 'ผลการพิจารณา', 'label_en' => 'Decision', 'required' => true, 'width' => 'full', 'options' => [
                    ['value' => 'approved', 'label_th' => 'อนุมัติ',    'label_en' => 'Approve'],
                    ['value' => 'rejected', 'label_th' => 'ไม่อนุมัติ', 'label_en' => 'Reject'],
                ]],
                ['id' => 'f2', 'type' => 'textarea', 'label_th' => 'เหตุผล', 'label_en' => 'Reason', 'required' => true, 'width' => 'full'],
            ]],
        ]);

        $tplRevision = FormTemplate::create([
            'name'       => 'Revision Request Form',
            'category'   => 'revision',
            'created_by' => $u1->id,
            'schema'     => ['fields' => [
                ['id' => 'f1', 'type' => 'textarea', 'label_th' => 'สิ่งที่ต้องแก้ไข',    'label_en' => 'Items to Revise',       'required' => true,  'width' => 'full'],
                ['id' => 'f2', 'type' => 'file',     'label_th' => 'เอกสารเพิ่มเติม',     'label_en' => 'Additional Documents', 'required' => false, 'width' => 'full'],
            ]],
        ]);

        // ─── Flows ───────────────────────────────────────────────────
        $flow1 = Flow::create([
            'name'       => 'IT Standard Approval',
            'created_by' => $u1->id,
        ]);

        $itStaffRoleId    = $itStaff->id;
        $itManagerRoleId  = $itManager->id;

        FlowNode::insert([
            ['flow_id' => $flow1->id, 'node_id' => 'start',          'type' => 'start',          'name_th' => 'เริ่ม',    'name_en' => 'Start',       'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory', 'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null, 'pos_x' => 50,  'pos_y' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'node_id' => 'receive',         'type' => 'approval',       'name_th' => 'รับเรื่อง IT', 'name_en' => 'IT Receive',  'approver_source' => 'role', 'approver_role_id' => $itStaffRoleId, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory', 'action_type' => 'any_one', 'sla_hours' => 4,  'step_form_template_id' => $tplItReceive->id, 'pos_x' => 280, 'pos_y' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'node_id' => 'approve',         'type' => 'approval',       'name_th' => 'อนุมัติ',  'name_en' => 'Approve',     'approver_source' => 'role', 'approver_role_id' => $itManagerRoleId, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory', 'action_type' => 'any_one', 'sla_hours' => 24, 'step_form_template_id' => $tplApproval->id, 'pos_x' => 520, 'pos_y' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'node_id' => 'end_approved',    'type' => 'end_approved',   'name_th' => 'อนุมัติแล้ว', 'name_en' => 'Approved',  'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory', 'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null, 'pos_x' => 760, 'pos_y' => 80,  'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'node_id' => 'end_rejected',    'type' => 'end_rejected',   'name_th' => 'ปฏิเสธ',   'name_en' => 'Rejected',    'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory', 'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null, 'pos_x' => 760, 'pos_y' => 220, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'node_id' => 'return_revision', 'type' => 'return_revision','name_th' => 'ส่งกลับแก้ไข', 'name_en' => 'Return for Revision', 'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory', 'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null, 'pos_x' => 390, 'pos_y' => 320, 'created_at' => now(), 'updated_at' => now()],
        ]);

        FlowEdge::insert([
            ['flow_id' => $flow1->id, 'from_node_id' => 'start',          'to_node_id' => 'receive',         'label' => null,     'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'from_node_id' => 'receive',         'to_node_id' => 'approve',         'label' => 'approve','created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'from_node_id' => 'receive',         'to_node_id' => 'return_revision', 'label' => 'reject', 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'from_node_id' => 'approve',         'to_node_id' => 'end_approved',    'label' => 'approve','created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'from_node_id' => 'approve',         'to_node_id' => 'end_rejected',    'label' => 'reject', 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow1->id, 'from_node_id' => 'return_revision', 'to_node_id' => 'receive',         'label' => null,     'created_at' => now(), 'updated_at' => now()],
        ]);

        $flow2 = Flow::create([
            'name'       => 'Management Approval',
            'created_by' => $u1->id,
        ]);

        FlowNode::insert([
            ['flow_id' => $flow2->id, 'node_id' => 'start',           'type' => 'start',          'name_th' => 'เริ่ม',    'name_en' => 'Start',             'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory',    'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null,              'pos_x' => 50,  'pos_y' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'node_id' => 'manager_approve', 'type' => 'approval',       'name_th' => 'อนุมัติผู้จัดการ', 'name_en' => 'Manager Approval', 'approver_source' => 'role', 'approver_role_id' => $itManagerRoleId, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'parent_factory', 'action_type' => 'any_one', 'sla_hours' => 72, 'step_form_template_id' => $tplApproval->id, 'pos_x' => 300, 'pos_y' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'node_id' => 'end_approved',    'type' => 'end_approved',   'name_th' => 'อนุมัติแล้ว', 'name_en' => 'Approved',         'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory',    'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null,              'pos_x' => 560, 'pos_y' => 80,  'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'node_id' => 'end_rejected',    'type' => 'end_rejected',   'name_th' => 'ปฏิเสธ',   'name_en' => 'Rejected',          'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory',    'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null,              'pos_x' => 560, 'pos_y' => 220, 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'node_id' => 'return_revision', 'type' => 'return_revision','name_th' => 'ส่งกลับแก้ไข', 'name_en' => 'Return for Revision', 'approver_source' => null, 'approver_role_id' => null, 'approver_user_id' => null, 'approver_option_set_code' => null, 'scope' => 'own_factory',    'action_type' => 'any_one', 'sla_hours' => null, 'step_form_template_id' => null,              'pos_x' => 300, 'pos_y' => 320, 'created_at' => now(), 'updated_at' => now()],
        ]);

        FlowEdge::insert([
            ['flow_id' => $flow2->id, 'from_node_id' => 'start',           'to_node_id' => 'manager_approve', 'label' => null,     'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'from_node_id' => 'manager_approve', 'to_node_id' => 'end_approved',    'label' => 'approve','created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'from_node_id' => 'manager_approve', 'to_node_id' => 'end_rejected',    'label' => 'reject', 'created_at' => now(), 'updated_at' => now()],
            ['flow_id' => $flow2->id, 'from_node_id' => 'manager_approve', 'to_node_id' => 'return_revision', 'label' => 'return_revision', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── Apps ────────────────────────────────────────────────────
        App::create([
            'name'                      => 'IT Request แจ้งซ่อม',
            'slug'                      => 'it-request',
            'category'                  => 'maintenance',
            'description'               => 'ระบบแจ้งซ่อมอุปกรณ์ IT',
            'icon'                      => 'ti-tool',
            'is_active'                 => true,
            'created_by'                => $u1->id,
            'initial_form_template_id'  => $tplItRequest->id,
            'revision_form_template_id' => $tplRevision->id,
            'flow_id'                   => $flow1->id,
        ]);

        App::create([
            'name'                      => 'Project Development Request',
            'slug'                      => 'project-request',
            'category'                  => 'development',
            'description'               => 'ระบบขอพัฒนาโปรเจกต์',
            'icon'                      => 'ti-code',
            'is_active'                 => true,
            'created_by'                => $u1->id,
            'initial_form_template_id'  => $tplProjectRequest->id,
            'revision_form_template_id' => $tplRevision->id,
            'flow_id'                   => $flow2->id,
        ]);

        // ─── Default Settings ────────────────────────────────────────
        $defaults = [
            ['key' => 'app_name',            'value' => 'IT MIS System (NPT Group)', 'group' => 'general',      'description' => 'Application Name'],
            ['key' => 'app_logo',            'value' => '',                          'group' => 'general',      'description' => 'Application Logo URL'],
            ['key' => 'default_locale',      'value' => 'th',                        'group' => 'general',      'description' => 'Default Language'],
            ['key' => 'email_notify',        'value' => '1',                         'group' => 'notification', 'description' => 'Enable Email Notification'],
            ['key' => 'overdue_check_hours', 'value' => '24',                        'group' => 'notification', 'description' => 'Check overdue every N hours'],
        ];
        foreach ($defaults as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }

        $this->call(OptionSetSeeder::class);
    }
}
