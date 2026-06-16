<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    protected $fillable = [
        'submission_id',
        'step_id',
        'node_id',
        'actor_id',
        'action',
        'comment',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AppSubmission::class, 'submission_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
