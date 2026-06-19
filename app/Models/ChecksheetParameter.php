<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecksheetParameter extends Model
{
    protected $fillable = [
        'template_id',
        'name',
        'slug',
        'unit',
        'type',
        'options',
        'spec_min',
        'spec_max',
        'spec_target',
        'alert_on',
        'alert_level',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_active'   => 'boolean',
        'spec_min'    => 'float',
        'spec_max'    => 'float',
        'spec_target' => 'float',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecksheetTemplate::class, 'template_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ChecksheetRecordValue::class, 'parameter_id');
    }

    /**
     * Check a value against spec limits.
     * Returns alert_level string ('warning' or 'critical') or null if no alert.
     */
    public function checkValue(mixed $value): ?string
    {
        if ($this->type !== 'number') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $num = (float) $value;

        $triggered = false;

        switch ($this->alert_on) {
            case 'above_max':
                if ($this->spec_max !== null && $num > $this->spec_max) {
                    $triggered = true;
                }
                break;
            case 'below_min':
                if ($this->spec_min !== null && $num < $this->spec_min) {
                    $triggered = true;
                }
                break;
            case 'both':
                if ($this->spec_max !== null && $num > $this->spec_max) {
                    $triggered = true;
                } elseif ($this->spec_min !== null && $num < $this->spec_min) {
                    $triggered = true;
                }
                break;
            case 'none':
            default:
                return null;
        }

        return $triggered ? $this->alert_level : null;
    }
}
