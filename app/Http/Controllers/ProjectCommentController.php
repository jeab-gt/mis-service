<?php

namespace App\Http\Controllers;

use App\Models\ProjectComment;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectCommentController extends Controller
{
    public function store(Request $request, ProjectTask $task)
    {
        $data = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        // Parse @mentions — find @name patterns
        preg_match_all('/@(\w+)/', $data['content'], $matches);
        $mentions = $matches[1] ?? [];

        $comment = ProjectComment::create([
            'task_id'    => $task->id,
            'project_id' => $task->project_id,
            'user_id'    => Auth::id(),
            'content'    => $data['content'],
            'mentions'   => $mentions ?: null,
        ]);

        $comment->load('user');

        return response()->json(['comment' => $comment]);
    }

    public function destroy(ProjectComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            abort(403);
        }

        $comment->delete();

        return response()->json(['ok' => true]);
    }
}
