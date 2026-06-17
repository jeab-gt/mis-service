<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowNode extends Model
{
    protected $fillable = [
        'flow_id',
        'node_id',
        'type',
        'name_th',
        'name_en',
        'approver_source',
        'approver_role_id',
        'approver_user_id',
        'approver_option_set_code',
        'scope',
        'action_type',
        'sla_hours',
        'step_form_template_id',
        'pos_x',
        'pos_y',
    ];

    protected $casts = [
        'sla_hours' => 'integer',
        'pos_x'     => 'integer',
        'pos_y'     => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'approver_role_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function stepFormTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'step_form_template_id');
    }
}
