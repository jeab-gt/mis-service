<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyLog extends Model
{
    protected $fillable = ['task_id', 'user_id', 'log_date', 'progress_pct', 'detail'];

    protected $casts = [
        'log_date'     => 'date',
        'progress_pct' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
