<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    protected $fillable = [
        'submission_id',
        'node_id',
        'actor_id',
        'action',
        'comment',
        'step_form_data',
        'acted_at',
    ];

    protected $casts = [
        'acted_at'       => 'datetime',
        'step_form_data' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AppSubmission::class, 'submission_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
