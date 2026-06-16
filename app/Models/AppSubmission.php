<?php

namespace App\Models;

use App\Traits\HasFactoryScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppSubmission extends Model
{
    use HasFactoryScope;

    protected $table = 'app_submissions';

    protected $fillable = [
        'app_id',
        'submitter_id',
        'factory_id',
        'form_data',
        'current_step',
        'status',
        'submitted_at',
        'closed_at',
    ];

    protected $casts = [
        'form_data'   => 'array',
        'submitted_at' => 'datetime',
        'closed_at'    => 'datetime',
        'current_step' => 'string',
    ];

    // ─── Relationships ───────────────────────────────────────────────
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'factory_id');
    }

    public function approvalActions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'submission_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RequestAssignment::class, 'submission_id');
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(RequestDailyLog::class, 'submission_id');
    }

    public function latestAssignment()
    {
        return $this->hasOne(RequestAssignment::class, 'submission_id')->latest('assigned_at');
    }

    // ─── Scopes ──────────────────────────────────────────────────────
    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('super_admin') || ($user->is_parent_factory && $user->hasAnyRole(['it_manager', 'it_staff']))) {
            return $query;
        }
        if ($user->hasAnyRole(['it_manager', 'it_staff', 'team_lead'])) {
            return $user->factory_id
                ? $query->where('factory_id', $user->factory_id)
                : $query;
        }
        return $query->where('submitter_id', $user->id);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ─── Accessors ───────────────────────────────────────────────────
    public function getTitleAttribute(): string
    {
        if ($this->form_data && $this->relationLoaded('app') && $this->app) {
            foreach ($this->app->form_schema['fields'] ?? [] as $field) {
                if ($field['type'] === 'text' && !empty($this->form_data[$field['id']])) {
                    return (string) $this->form_data[$field['id']];
                }
            }
        }
        return '#' . $this->id;
    }

    public function getProgressAttribute(): int
    {
        $latest = $this->dailyLogs()->orderByDesc('log_date')->orderByDesc('id')->first();
        return $latest ? $latest->progress_pct : 0;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'gray',
            'submitted' => 'blue',
            'in_review' => 'yellow',
            'approved'  => 'green',
            'rejected'  => 'red',
            'closed'    => 'purple',
            'returned'  => 'orange',
            default     => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['approved', 'rejected', 'closed'])) {
            return false;
        }
        $assignment = $this->latestAssignment;
        if ($assignment && $assignment->due_date) {
            return now()->gt($assignment->due_date);
        }
        return false;
    }
}
