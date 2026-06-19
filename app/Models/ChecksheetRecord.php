<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecksheetRecord extends Model
{
    protected $fillable = [
        'template_id',
        'factory_id',
        'record_date',
        'time_slot_id',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'current_node_id',
        'note',
    ];

    protected $casts = [
        'record_date'  => 'date',
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecksheetTemplate::class, 'template_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'factory_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(ChecksheetTimeSlot::class, 'time_slot_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ChecksheetRecordValue::class, 'record_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
