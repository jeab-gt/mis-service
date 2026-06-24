<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'title', 'description',
        'is_template', 'template_name', 'created_by',
    ];

    protected $casts = [
        'is_template' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function slides()
    {
        return $this->hasMany(ProjectReportSlide::class, 'report_id')->orderBy('slide_order');
    }

    public function attachments()
    {
        return $this->hasMany(ProjectReportAttachment::class, 'report_id');
    }
}
