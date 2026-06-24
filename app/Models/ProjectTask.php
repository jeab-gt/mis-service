<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectTask extends Model
{
    protected $fillable = [
        'project_id', 'parent_task_id', 'milestone_id',
        'title', 'description', 'assignee_id', 'created_by',
        'start_date', 'due_date', 'completed_at',
        'estimated_hours', 'actual_hours',
        'priority', 'status', 'progress_pct', 'sort_order', 'labels', 'has_blocker',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'due_date'        => 'date',
        'completed_at'    => 'datetime',
        'labels'          => 'array',
        'estimated_hours' => 'decimal:2',
        'actual_hours'    => 'decimal:2',
        'progress_pct'    => 'integer',
        'sort_order'      => 'integer',
        'has_blocker'     => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id')->orderBy('sort_order');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(ProjectTaskChecklist::class, 'task_id')->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class, 'task_id')->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class, 'task_id')->latest();
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class, 'task_id')->latest('log_date');
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(ProjectDailyLog::class, 'task_id')->latest('log_date');
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(ProjectTaskBlocker::class, 'task_id');
    }

    public function activeBlocker(): HasOne
    {
        return $this->hasOne(ProjectTaskBlocker::class, 'task_id')
            ->whereNull('resolved_at')
            ->latest();
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectTask::class,
            'project_task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withPivot('type')->withTimestamps();
    }

    public function isOverdue(): bool
    {
        return $this->due_date && !in_array($this->status, ['done', 'cancelled']) && now()->gt($this->due_date);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'todo'        => 'gray',
            'in_progress' => 'blue',
            'review'      => 'yellow',
            'done'        => 'green',
            'cancelled'   => 'red',
            default       => 'gray',
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'low'      => 'gray',
            'medium'   => 'blue',
            'high'     => 'orange',
            'critical' => 'red',
            default    => 'gray',
        };
    }
}
