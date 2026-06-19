<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dashboard extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'factory_scope',
        'layout',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'layout'    => 'array',
        'is_public' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id')
            ->orderBy('pos_y')
            ->orderBy('pos_x');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
