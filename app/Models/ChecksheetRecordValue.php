<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecksheetRecordValue extends Model
{
    protected $table = 'checksheet_record_values';

    protected $fillable = [
        'record_id',
        'parameter_id',
        'value',
        'is_alert',
        'alert_level',
        'recorded_by',
    ];

    protected $casts = [
        'is_alert' => 'boolean',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(ChecksheetRecord::class, 'record_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ChecksheetParameter::class, 'parameter_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
