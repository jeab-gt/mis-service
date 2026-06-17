<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flow extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(FlowNode::class, 'flow_id');
    }

    public function edges(): HasMany
    {
        return $this->hasMany(FlowEdge::class, 'flow_id');
    }

    public function apps(): HasMany
    {
        return $this->hasMany(App::class, 'flow_id');
    }

    public function getStartNode(): ?FlowNode
    {
        return $this->nodes()->where('type', 'start')->first();
    }

    public function getNodeById(string $nodeId): ?FlowNode
    {
        return $this->nodes()->where('node_id', $nodeId)->first();
    }

    public function getNextNodes(string $fromNodeId, ?string $label = null): \Illuminate\Database\Eloquent\Collection
    {
        $edgeQ = $this->edges()->where('from_node_id', $fromNodeId);
        if ($label !== null) {
            $edgeQ->where('label', $label);
        }
        $toIds = $edgeQ->pluck('to_node_id');
        return $this->nodes()->whereIn('node_id', $toIds)->get();
    }
}
