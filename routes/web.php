<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/dashboard'));

Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'setlocale'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
        Route::get('/{app}/designer', [\App\Http\Controllers\Admin\AppController::class, 'designer'])->name('designer');
        Route::get('/{app}/flow', [\App\Http\Controllers\Admin\AppController::class, 'flow'])->name('flow');
        Route::post('/{app}/save-flow', [\App\Http\Controllers\Admin\AppController::class, 'saveFlow'])->name('save-flow');
        Route::get('/{app}/preview', [\App\Http\Controllers\Admin\AppController::class, 'preview'])->name('preview');
    });

    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

    // AJAX: sections cascade dropdown by factory
    Route::get('/factory-sections/{factoryId}', [\App\Http\Controllers\Admin\UserController::class, 'sectionsForFactory'])->name('factory.sections');
});

require __DIR__ . '/auth.php';
