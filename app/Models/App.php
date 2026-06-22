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
        'category_id',
        'app_type',
        'dashboard_id',
        'allowed_roles',
        'allowed_factories',
        'description',
        'theme_config',
        'icon',
        'is_active',
        'created_by',
        'initial_form_template_id',
        'revision_form_template_id',
        'flow_id',
    ];

    protected $casts = [
        'theme_config'      => 'array',
        'allowed_roles'     => 'array',
        'allowed_factories' => 'array',
        'is_active'         => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AppSubmission::class, 'app_id');
    }

    public function initialFormTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'initial_form_template_id');
    }

    public function revisionFormTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'revision_form_template_id');
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AppCategory::class, 'category_id');
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class, 'dashboard_id');
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
