<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasFactoryScope
{
    /**
     * Scope query to records visible to the current authenticated user.
     * - super_admin → all records
     * - is_parent_factory=true → all records
     * - others → filter by factory_id matching the user's factory
     */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        if (! auth()->check()) {
            return $query->whereRaw('1=0');
        }

        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->is_parent_factory) {
            return $query;
        }

        if ($user->factory_id) {
            return $query->where($this->getTable() . '.factory_id', $user->factory_id);
        }

        return $query;
    }
}
