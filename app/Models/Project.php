<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'description', 'objective', 'submission_id',
        'factory_id', 'manager_id', 'start_date', 'end_date',
        'status', 'priority', 'budget', 'progress_pct',
        'color', 'is_cross_factory',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'budget'           => 'decimal:2',
        'progress_pct'     => 'integer',
        'is_cross_factory' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'factory_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AppSubmission::class, 'submission_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('due_date');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role', 'factory_id', 'joined_at')
            ->withTimestamps();
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('super_admin') || ($user->is_parent_factory && $user->hasAnyRole(['it_manager', 'it_staff']))) {
            return $query;
        }
        return $query->where(function ($q) use ($user) {
            $q->where('factory_id', $user->factory_id)
              ->orWhere('is_cross_factory', true)
              ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
        });
    }

    public function recalculateProgress(): void
    {
        $total = $this->tasks()->whereNull('parent_task_id')->count();
        if ($total === 0) {
            $this->update(['progress_pct' => 0]);
            return;
        }
        $done = $this->tasks()->whereNull('parent_task_id')->where('status', 'done')->count();
        $this->update(['progress_pct' => (int) round($done / $total * 100)]);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'planning'  => 'gray',
            'active'    => 'blue',
            'on_hold'   => 'yellow',
            'completed' => 'green',
            'cancelled' => 'red',
            default     => 'gray',
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
