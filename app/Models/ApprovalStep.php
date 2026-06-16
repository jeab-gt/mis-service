<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class ApprovalStep extends Model
{
    protected $fillable = [
        'app_id',
        'step_order',
        'name_th',
        'name_en',
        'approver_role_id',
        'action_type',
        'sla_hours',
        'scope',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'sla_hours'  => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'step_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'th' ? $this->name_th : $this->name_en;
    }
}
