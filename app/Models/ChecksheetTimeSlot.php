<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecksheetTimeSlot extends Model
{
    protected $fillable = [
        'template_id',
        'label',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecksheetTemplate::class, 'template_id');
    }
}
