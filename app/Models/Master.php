<?php

namespace App\Models;

use App\Traits\HasFactoryScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Master extends Model
{
    use HasFactoryScope;

    protected $fillable = [
        'parent_id',
        'factory_id',
        'type',
        'code',
        'name_th',
        'name_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────────
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Master::class, 'parent_id')->orderBy('sort_order');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'section_id');
    }

    public function usersInFactory(): HasMany
    {
        return $this->hasMany(User::class, 'factory_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ───────────────────────────────────────────────────
    public function getFullPathAttribute(): string
    {
        $parts   = [];
        $current = $this;
        while ($current) {
            array_unshift($parts, $current->name_th);
            $current = $current->parent;
        }
        return implode(' > ', $parts);
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'th' ? $this->name_th : $this->name_en;
    }

    // ─── Statics ─────────────────────────────────────────────────────
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'company'    => 'Company',
            'factory'    => 'Factory',
            'plant'      => 'Plant',
            'department' => 'Department',
            'section'    => 'Section',
            'team'       => 'Team',
            'line'       => 'Line',
            default      => $type,
        };
    }

    public static function typeBadgeColor(string $type): string
    {
        return match ($type) {
            'company'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
            'factory'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'plant'      => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
            'department' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
            'section'    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
            'team'       => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
            default      => 'bg-gray-100 text-gray-600',
        };
    }

    public static function typeIcon(string $type): string
    {
        return match ($type) {
            'company'    => 'ti-building-skyscraper',
            'factory'    => 'ti-building-factory-2',
            'plant'      => 'ti-building-warehouse',
            'department' => 'ti-building',
            'section'    => 'ti-users-group',
            'team'       => 'ti-user-group',
            default      => 'ti-circles',
        };
    }

    /** Type order for hierarchy validation */
    public static function typeOrder(): array
    {
        return ['company' => 1, 'factory' => 2, 'plant' => 3, 'department' => 4, 'section' => 5, 'team' => 6, 'line' => 5];
    }

    public function childType(): string
    {
        return match ($this->type) {
            'company'    => 'factory',
            'factory'    => 'plant',
            'plant'      => 'department',
            'department' => 'section',
            'section'    => 'team',
            'team'       => 'line',
            default      => 'section',
        };
    }

    public static function allowedChildren(string $type): array
    {
        return match ($type) {
            'company'    => ['factory'],
            'factory'    => ['plant', 'department'],
            'plant'      => ['department'],
            'department' => ['section', 'team'],
            'section'    => ['team'],
            default      => [],
        };
    }

    /**
     * Walk up the tree to find the factory-level ancestor id.
     */
    public function resolveFactoryId(): ?int
    {
        $current = $this;
        while ($current) {
            if ($current->type === 'factory') {
                return $current->id;
            }
            $current = $current->parent;
        }
        return null;
    }
}
