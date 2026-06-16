<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionSetItem extends Model
{
    protected $fillable = [
        'option_set_id',
        'value',
        'label_th',
        'label_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function optionSet(): BelongsTo
    {
        return $this->belongsTo(OptionSet::class);
    }
}
