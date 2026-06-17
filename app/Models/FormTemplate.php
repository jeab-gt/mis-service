<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'schema',
        'category',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'schema'    => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appsAsInitial(): HasMany
    {
        return $this->hasMany(App::class, 'initial_form_template_id');
    }

    public function appsAsRevision(): HasMany
    {
        return $this->hasMany(App::class, 'revision_form_template_id');
    }

    public function flowNodes(): HasMany
    {
        return $this->hasMany(FlowNode::class, 'step_form_template_id');
    }

    public function getFieldCountAttribute(): int
    {
        return count($this->schema['fields'] ?? []);
    }
}
