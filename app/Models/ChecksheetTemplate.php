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
        'category_id',
        'dashboard_id',
        'allowed_roles',
        'allowed_factories',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'allowed_roles'     => 'array',
        'allowed_factories' => 'array',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(AppCategory::class, 'category_id');
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class, 'dashboard_id');
    }
}
