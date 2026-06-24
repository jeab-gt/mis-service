<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectReportAttachment extends Model
{
    protected $fillable = ['report_id', 'filename', 'original_name', 'mime_type', 'size', 'uploaded_by'];

    public function report()
    {
        return $this->belongsTo(ProjectReport::class, 'report_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset('storage/report-attachments/' . $this->filename);
    }
}
