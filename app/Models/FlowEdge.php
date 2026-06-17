<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowEdge extends Model
{
    protected $fillable = [
        'flow_id',
        'from_node_id',
        'to_node_id',
        'label',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }
}
