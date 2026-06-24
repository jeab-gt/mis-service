<?php

namespace App\Http\Controllers;

use App\Models\ProjectReport;
use App\Models\ProjectReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectReportAttachmentController extends Controller
{
    public function store(Request $request, ProjectReport $report)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg',
        ]);

        $file     = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->storeAs('report-attachments', $filename, 'public');

        $attachment = ProjectReportAttachment::create([
            'report_id'     => $report->id,
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => Auth::id(),
        ]);

        return response()->json([
            'id'  => $attachment->id,
            'url' => asset('storage/report-attachments/' . $filename),
        ]);
    }

    public function destroy(ProjectReport $report, ProjectReportAttachment $attachment)
    {
        Storage::disk('public')->delete('report-attachments/' . $attachment->filename);
        $attachment->delete();

        return response()->json(['success' => true]);
    }
}
