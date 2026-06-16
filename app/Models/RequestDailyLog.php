<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDailyLog extends Model
{
    protected $fillable = [
        'submission_id',
        'user_id',
        'log_date',
        'progress_pct',
        'detail',
    ];

    protected $casts = [
        'log_date' => 'date',
        'progress_pct' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AppSubmission::class, 'submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
