<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppCategory extends Model
{
    protected $fillable = [
        'name_th',
        'name_en',
        'icon',
        'color',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function apps(): HasMany
    {
        return $this->hasMany(App::class, 'category_id');
    }

    public function checksheets(): HasMany
    {
        return $this->hasMany(ChecksheetTemplate::class, 'category_id');
    }
}
