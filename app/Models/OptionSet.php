<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class OptionSet extends Model
{
    protected $fillable = [
        'code',
        'name_th',
        'name_en',
        'source_type',
        'master_type',
        'filter_by_factory',
        'description',
    ];

    protected $casts = [
        'filter_by_factory' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OptionSetItem::class)->orderBy('sort_order');
    }

    public function getOptions(?int $factoryId = null): array
    {
        return match ($this->source_type) {
            'static' => $this->items()
                ->where('is_active', true)
                ->get()
                ->map(fn($i) => ['value' => $i->value, 'label_th' => $i->label_th, 'label_en' => $i->label_en])
                ->toArray(),

            'master' => Master::where('type', $this->master_type)
                ->when($this->filter_by_factory && $factoryId, fn($q) => $q->where('factory_id', $factoryId))
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($m) => ['value' => (string) $m->id, 'label_th' => $m->name_th, 'label_en' => $m->name_en])
                ->toArray(),

            'users' => User::where('is_active', true)
                ->when($this->filter_by_factory && $factoryId, fn($q) => $q->where('factory_id', $factoryId))
                ->get()
                ->map(fn($u) => ['value' => (string) $u->id, 'label_th' => $u->name, 'label_en' => $u->name])
                ->toArray(),

            'roles' => Role::all()
                ->map(fn($r) => ['value' => $r->name, 'label_th' => $r->name, 'label_en' => $r->name])
                ->toArray(),

            default => [],
        };
    }
}
