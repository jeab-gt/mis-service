<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecksheetDailySummary extends Model
{
    protected $table = 'checksheet_daily_summary';

    protected $fillable = [
        'template_id',
        'factory_id',
        'parameter_id',
        'summary_date',
        'avg_value',
        'min_value',
        'max_value',
        'total_count',
        'alert_count',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'avg_value'    => 'float',
        'min_value'    => 'float',
        'max_value'    => 'float',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecksheetTemplate::class, 'template_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ChecksheetParameter::class, 'parameter_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'factory_id');
    }
}
