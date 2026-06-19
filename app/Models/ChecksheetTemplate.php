<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecksheetTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'slug',
        'frequency',
        'flow_id',
        'factory_scope',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(ChecksheetParameter::class, 'template_id')->orderBy('sort_order');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(ChecksheetTimeSlot::class, 'template_id')->orderBy('sort_order');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ChecksheetRecord::class, 'template_id');
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
