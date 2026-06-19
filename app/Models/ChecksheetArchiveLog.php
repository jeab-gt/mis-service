<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecksheetArchiveLog extends Model
{
    protected $fillable = [
        'year',
        'table_name',
        'rows_archived',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
