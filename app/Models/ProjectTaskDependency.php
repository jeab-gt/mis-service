<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskDependency extends Model
{
    protected $fillable = ['task_id', 'depends_on_task_id', 'type'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'depends_on_task_id');
    }
}
