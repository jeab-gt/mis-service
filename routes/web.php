<?php

use App\Http\Controllers\ApplicationsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\ProjectBlockerController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectReportAttachmentController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/dashboard'));

Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'setlocale'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/applications', [ApplicationsController::class, 'index'])->name('applications.index');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Theme / card-style preferences
    Route::post('/user/theme',      [\App\Http\Controllers\ThemeController::class, 'update'])->name('user.theme');
    Route::post('/user/card-style', [\App\Http\Controllers\ThemeController::class, 'updateCardStyle'])->name('user.card-style');

    // Submissions
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [SubmissionController::class, 'index'])->name('index');
        Route::get('/{app}/create', [SubmissionController::class, 'create'])->name('create');
        Route::post('/{app}', [SubmissionController::class, 'store'])->name('store');
        Route::get('/{submission}', [SubmissionController::class, 'show'])->name('show');
        Route::post('/{submission}/approve', [SubmissionController::class, 'approve'])->name('approve');
        Route::post('/{submission}/assign', [SubmissionController::class, 'assign'])->name('assign');
        Route::post('/{submission}/log', [SubmissionController::class, 'addLog'])->name('log');
        Route::post('/{submission}/resubmit', [SubmissionController::class, 'resubmit'])->name('resubmit');
    });

    // Tasks
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/schedule', [TaskController::class, 'schedule'])->name('schedule');
        Route::post('/progress/{submission}', [TaskController::class, 'updateProgress'])->name('progress');
        Route::post('/log/{submission}', [TaskController::class, 'storeLog'])->name('log');
        Route::get('/logs/{submission}', [TaskController::class, 'getLogs'])->name('logs');
        Route::post('/move/{assignment}', [TaskController::class, 'moveCard'])->name('move');
    });

    // Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/',                                            [ProjectController::class, 'index'])->name('index');
        Route::get('/create',                                     [ProjectController::class, 'create'])->name('create');
        Route::post('/',                                          [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}',                                  [ProjectController::class, 'show'])->name('show');
        Route::get('/{project}/edit',                             [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project}',                                  [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}',                               [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('/{project}/members',                         [ProjectController::class, 'addMember'])->name('members.add');
        Route::delete('/{project}/members/{user}',                [ProjectController::class, 'removeMember'])->name('members.remove');
        Route::post('/{project}/tasks',                           [ProjectTaskController::class, 'store'])->name('tasks.store');
        Route::post('/{project}/attachments',                     [ProjectAttachmentController::class, 'store'])->name('attachments.store');

        // Reports (chart data)
        Route::get('/{project}/reports/burndown',                   [ProjectReportController::class, 'burndown'])->name('reports.burndown');
        Route::get('/{project}/reports/workload',                   [ProjectReportController::class, 'workload'])->name('reports.workload');

        // Report Builder
        Route::get('/{project}/reports',                            [ProjectReportController::class, 'index'])->name('reports.index');
        Route::get('/{project}/reports/create',                     [ProjectReportController::class, 'create'])->name('reports.create');
        Route::post('/{project}/reports',                           [ProjectReportController::class, 'store'])->name('reports.store');
        Route::get('/{project}/reports/templates',                  [ProjectReportController::class, 'templates'])->name('reports.templates');
        Route::get('/{project}/reports/{report}/builder',           [ProjectReportController::class, 'builder'])->name('reports.builder');
        Route::put('/{project}/reports/{report}/save',              [ProjectReportController::class, 'save'])->name('reports.save');
        Route::get('/{project}/reports/{report}/preview',           [ProjectReportController::class, 'preview'])->name('reports.preview');
        Route::get('/{project}/reports/{report}/export',            [ProjectReportController::class, 'export'])->name('reports.export');
        Route::post('/{project}/reports/{report}/save-as-template', [ProjectReportController::class, 'saveAsTemplate'])->name('reports.save-as-template');
        Route::post('/{project}/reports/{report}/upload-image',    [ProjectReportController::class, 'uploadImage'])->name('reports.upload-image');
        Route::delete('/{project}/reports/{report}',                [ProjectReportController::class, 'destroy'])->name('reports.destroy');

        // Report Attachments
        Route::post('/{project}/reports/{report}/attachments',      [ProjectReportAttachmentController::class, 'store'])->name('reports.attachments.store');
        Route::delete('/{project}/reports/{report}/attachments/{attachment}', [ProjectReportAttachmentController::class, 'destroy'])->name('reports.attachments.destroy');
    });

    // Project tasks (task-level operations)
    Route::prefix('project-tasks')->name('project-tasks.')->group(function () {
        Route::put('/{task}',                                     [ProjectTaskController::class, 'update'])->name('update');
        Route::delete('/{task}',                                  [ProjectTaskController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/log-time',                           [ProjectTaskController::class, 'logTime'])->name('log-time');
        Route::post('/{task}/progress',                           [ProjectTaskController::class, 'updateProgress'])->name('progress');
        Route::post('/{task}/checklist',                          [ProjectTaskController::class, 'addChecklist'])->name('checklist.add');
        Route::patch('/checklist/{item}',                         [ProjectTaskController::class, 'toggleChecklist'])->name('checklist.toggle');
        Route::post('/{task}/comments',                           [ProjectCommentController::class, 'store'])->name('comments.store');
        Route::post('/reorder',                                   [ProjectTaskController::class, 'reorder'])->name('reorder');
    });

    // Project blockers
    Route::post('/projects/tasks/{task}/blockers',    [ProjectBlockerController::class, 'store'])->name('project-blockers.store');
    Route::patch('/projects/blockers/{blocker}/resolve', [ProjectBlockerController::class, 'resolve'])->name('project-blockers.resolve');

    // Project comments + attachments
    Route::delete('/project-comments/{comment}',                  [ProjectCommentController::class, 'destroy'])->name('project-comments.destroy');
    Route::get('/project-attachments/{attachment}/download',      [ProjectAttachmentController::class, 'download'])->name('project-attachments.download');
    Route::delete('/project-attachments/{attachment}',            [ProjectAttachmentController::class, 'destroy'])->name('project-attachments.destroy');

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('can:report.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('/weekly', [ReportController::class, 'weekly'])->name('weekly');
        Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('/export', [ReportController::class, 'export'])->name('export')->middleware('can:report.export');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
    });
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'setlocale', 'role:super_admin|it_manager'])->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
    Route::resource('masters', \App\Http\Controllers\Admin\MasterController::class);

    // Option Sets
    Route::prefix('option-sets')->name('option-sets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OptionSetController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\OptionSetController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\OptionSetController::class, 'store'])->name('store');
        Route::get('/{optionSet}/edit', [\App\Http\Controllers\Admin\OptionSetController::class, 'edit'])->name('edit');
        Route::put('/{optionSet}', [\App\Http\Controllers\Admin\OptionSetController::class, 'update'])->name('update');
        Route::delete('/{optionSet}', [\App\Http\Controllers\Admin\OptionSetController::class, 'destroy'])->name('destroy');
        Route::get('/{optionSet}/options', [\App\Http\Controllers\Admin\OptionSetController::class, 'options'])->name('options');
    });

    Route::prefix('apps')->name('apps.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AppController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\AppController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AppController::class, 'store'])->name('store');
        Route::get('/{app}/edit', [\App\Http\Controllers\Admin\AppController::class, 'edit'])->name('edit');
        Route::put('/{app}', [\App\Http\Controllers\Admin\AppController::class, 'update'])->name('update');
        Route::delete('/{app}', [\App\Http\Controllers\Admin\AppController::class, 'destroy'])->name('destroy');
        Route::get('/{app}/preview', [\App\Http\Controllers\Admin\AppController::class, 'preview'])->name('preview');
    });

    // Form Templates (Form Library)
    Route::prefix('form-templates')->name('form-templates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FormTemplateController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\FormTemplateController::class, 'store'])->name('store');
        Route::get('/{formTemplate}/designer', [\App\Http\Controllers\Admin\FormTemplateController::class, 'designer'])->name('designer');
        Route::post('/{formTemplate}/save', [\App\Http\Controllers\Admin\FormTemplateController::class, 'save'])->name('save');
        Route::put('/{formTemplate}', [\App\Http\Controllers\Admin\FormTemplateController::class, 'update'])->name('update');
        Route::post('/{formTemplate}/duplicate', [\App\Http\Controllers\Admin\FormTemplateController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{formTemplate}', [\App\Http\Controllers\Admin\FormTemplateController::class, 'destroy'])->name('destroy');
    });

    // Flows (Flow Library)
    Route::prefix('flows')->name('flows.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FlowController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\FlowController::class, 'store'])->name('store');
        Route::get('/{flow}/designer', [\App\Http\Controllers\Admin\FlowController::class, 'designer'])->name('designer');
        Route::post('/{flow}/save', [\App\Http\Controllers\Admin\FlowController::class, 'save'])->name('save');
        Route::post('/{flow}/duplicate', [\App\Http\Controllers\Admin\FlowController::class, 'duplicate'])->name('duplicate');
        Route::delete('/{flow}', [\App\Http\Controllers\Admin\FlowController::class, 'destroy'])->name('destroy');
    });

    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

    // AJAX: sections cascade dropdown by factory
    Route::get('/factory-sections/{factoryId}', [\App\Http\Controllers\Admin\UserController::class, 'sectionsForFactory'])->name('factory.sections');

    // Checksheet Templates (admin) — index redirects to App Builder
    Route::prefix('checksheets')->name('checksheets.')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.apps.index'))->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ChecksheetTemplateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ChecksheetTemplateController::class, 'store'])->name('store');
        Route::get('/{template}/edit', [\App\Http\Controllers\Admin\ChecksheetTemplateController::class, 'edit'])->name('edit');
        Route::get('/{template}/builder', [\App\Http\Controllers\Admin\ChecksheetTemplateController::class, 'builder'])->name('builder');
        Route::post('/{template}/save', [\App\Http\Controllers\Admin\ChecksheetTemplateController::class, 'save'])->name('save');
        Route::delete('/{template}', [\App\Http\Controllers\Admin\ChecksheetTemplateController::class, 'destroy'])->name('destroy');
    });

    // Data Management
    Route::get('/settings/data-management', [\App\Http\Controllers\Admin\DataManagementController::class, 'index'])->name('data-management.index');
    Route::post('/settings/data-management/archive', [\App\Http\Controllers\Admin\DataManagementController::class, 'archive'])->name('data-management.archive');
    Route::delete('/settings/data-management/drop-archive', [\App\Http\Controllers\Admin\DataManagementController::class, 'dropArchive'])->name('data-management.drop');

    // App Categories
    Route::prefix('app-categories')->name('app-categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AppCategoryController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\AppCategoryController::class, 'store'])->name('store');
        Route::put('/{appCategory}', [\App\Http\Controllers\Admin\AppCategoryController::class, 'update'])->name('update');
        Route::delete('/{appCategory}', [\App\Http\Controllers\Admin\AppCategoryController::class, 'destroy'])->name('destroy');
    });
});

// Checksheets (user-facing)
Route::prefix('checksheets')->name('checksheets.')->middleware(['auth', 'setlocale'])->group(function () {
    Route::get('/', [\App\Http\Controllers\ChecksheetEntryController::class, 'index'])->name('index');
    Route::get('/{template}/records', [\App\Http\Controllers\ChecksheetEntryController::class, 'records'])->name('records');
    Route::get('/{template}/fill', [\App\Http\Controllers\ChecksheetEntryController::class, 'fill'])->name('fill');
    Route::post('/{template}/fill', [\App\Http\Controllers\ChecksheetEntryController::class, 'store'])->name('store');
    Route::post('/records/{record}/submit', [\App\Http\Controllers\ChecksheetEntryController::class, 'submit'])->name('submit');
});

// Dashboards
Route::prefix('dashboards')->name('dashboards.')->middleware(['auth', 'setlocale'])->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardBuilderController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\DashboardBuilderController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\DashboardBuilderController::class, 'store'])->name('store');
    Route::get('/{dashboard}', [\App\Http\Controllers\DashboardBuilderController::class, 'show'])->name('show');
    Route::get('/{dashboard}/edit', [\App\Http\Controllers\DashboardBuilderController::class, 'edit'])->name('edit');
    Route::post('/{dashboard}/save-layout', [\App\Http\Controllers\DashboardBuilderController::class, 'saveLayout'])->name('save-layout');
    Route::delete('/{dashboard}', [\App\Http\Controllers\DashboardBuilderController::class, 'destroy'])->name('destroy');
});

// Widget data API
Route::middleware(['auth'])->get('/api/dashboard-widgets/{widget}/data', [\App\Http\Controllers\DashboardWidgetController::class, 'data'])->name('api.widget.data');

require __DIR__ . '/auth.php';
