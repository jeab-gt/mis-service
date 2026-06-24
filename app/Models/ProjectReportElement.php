<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectReportElement extends Model
{
    protected $fillable = ['slide_id', 'type', 'x', 'y', 'w', 'h', 'z_index', 'props'];

    protected $casts = [
        'props'   => 'array',
        'x'       => 'float',
        'y'       => 'float',
        'w'       => 'float',
        'h'       => 'float',
        'z_index' => 'integer',
    ];

    public function slide()
    {
        return $this->belongsTo(ProjectReportSlide::class, 'slide_id');
    }
}
