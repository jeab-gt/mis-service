<?php

namespace App\Models;

use App\Traits\HasFactoryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasFactoryScope;

    protected $fillable = [
        'name',
        'name_th',
        'name_en',
        'email',
        'password',
        'section_id',
        'factory_id',
        'employee_code',
        'phone',
        'is_active',
        'is_parent_factory',
        'theme_preference',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'is_parent_factory'  => 'boolean',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relationships ───────────────────────────────────────────────
    public function section()
    {
        return $this->belongsTo(Master::class, 'section_id');
    }

    public function factory()
    {
        return $this->belongsTo(Master::class, 'factory_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AppSubmission::class, 'submitter_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RequestAssignment::class, 'assignee_id');
    }

    public function misNotifications(): HasMany
    {
        return $this->hasMany(MisNotification::class, 'user_id');
    }

    public function factoryRoles(): HasMany
    {
        return $this->hasMany(UserFactoryRole::class, 'user_id');
    }

    // ─── Factory-scoped helpers ──────────────────────────────────────
    public function getRolesForFactory(int $factoryId): \Illuminate\Support\Collection
    {
        return $this->factoryRoles()
            ->where('factory_id', $factoryId)
            ->with('role')
            ->get()
            ->pluck('role');
    }

    public function canInFactory(string $permission, int $factoryId): bool
    {
        if ($this->hasRole('super_admin') || $this->is_parent_factory) {
            return $this->hasPermissionTo($permission);
        }

        $roles = $this->getRolesForFactory($factoryId);
        foreach ($roles as $role) {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }
        }

        return $this->hasPermissionTo($permission);
    }

    // ─── Accessors ───────────────────────────────────────────────────
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'th' && $this->name_th) {
            return $this->name_th;
        }
        if ($locale === 'en' && $this->name_en) {
            return $this->name_en;
        }
        return $this->name;
    }

    public function unreadNotificationsCount(): int
    {
        return $this->misNotifications()->whereNull('read_at')->count();
    }
}
