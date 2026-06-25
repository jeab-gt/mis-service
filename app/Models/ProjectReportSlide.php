<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectReportSlide extends Model
{
    protected $fillable = ['report_id', 'slide_order', 'bg_color', 'notes', 'html_content'];

    public function report()
    {
        return $this->belongsTo(ProjectReport::class, 'report_id');
    }

    public function elements()
    {
        return $this->hasMany(ProjectReportElement::class, 'slide_id')->orderBy('z_index');
    }
}
