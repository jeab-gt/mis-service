<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MisNotification extends Model
{
    protected $table = 'mis_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title_th',
        'title_en',
        'body_th',
        'body_en',
        'payload',
        'read_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'th' ? $this->title_th : $this->title_en;
    }

    public function getBodyAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'th' ? $this->body_th : $this->body_en;
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
