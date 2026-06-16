<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class UserFactoryRole extends Model
{
    protected $fillable = ['user_id', 'factory_id', 'role_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'factory_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
