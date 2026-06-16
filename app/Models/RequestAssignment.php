<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestAssignment extends Model
{
    protected $fillable = [
        'submission_id',
        'assignee_id',
        'assigned_by',
        'due_date',
        'assigned_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'assigned_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AppSubmission::class, 'submission_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
