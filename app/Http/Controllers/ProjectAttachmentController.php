<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectAttachmentController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'file'    => 'required|file|max:20480',
            'task_id' => 'nullable|exists:project_tasks,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('projects/' . $project->id, 'local');

        $attachment = ProjectAttachment::create([
            'project_id' => $project->id,
            'task_id'    => $request->task_id,
            'user_id'    => Auth::id(),
            'file_name'  => $file->getClientOriginalName(),
            'file_path'  => $path,
            'file_size'  => $file->getSize(),
            'mime_type'  => $file->getMimeType(),
        ]);

        return response()->json(['attachment' => $attachment->load('uploader')]);
    }

    public function download(ProjectAttachment $attachment)
    {
        if (!Storage::disk('local')->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment->file_path, $attachment->file_name);
    }

    public function destroy(ProjectAttachment $attachment)
    {
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['ok' => true]);
    }
}
