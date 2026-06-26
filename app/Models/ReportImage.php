<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportImage extends Model
{
    protected $fillable = ['report_id', 'filename', 'mime_type', 'data', 'size'];

    public function report()
    {
        return $this->belongsTo(ProjectReport::class, 'report_id');
    }

    public function toDataUrl(): string
    {
        return 'data:' . $this->mime_type . ';base64,' . $this->data;
    }
}
