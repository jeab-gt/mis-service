<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'form_schema',
        'flow_schema',
        'theme_config',
        'icon',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'form_schema' => 'array',
        'flow_schema' => 'array',
        'theme_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AppSubmission::class, 'app_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'app_id')->orderBy('step_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
