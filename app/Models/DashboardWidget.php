<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    protected $fillable = [
        'dashboard_id',
        'widget_type',
        'title',
        'title_en',
        'config',
        'pos_x',
        'pos_y',
        'width',
        'height',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
