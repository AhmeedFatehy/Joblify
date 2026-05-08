<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Comment;
use App\Models\ModerationAction;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCommentController extends BaseApiController
{
    /**
     * List all comments (admin view) with optional job filter.
     * GET /admin/comments?job_id=1
     */
    public function index(): JsonResponse
    {
        $comments = Comment::with(['user:id,name', 'job:id,title'])
            ->when(request('job_id'), fn ($q) => $q->where('job_id', request('job_id')))
            ->latest()
            ->paginate(20);

        return $this->paginated($comments, 'Comments retrieved successfully');
    }

    /**
     * Remove an inappropriate comment from the platform.
     * DELETE /admin/comments/{comment}
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $admin = Auth::user();

        // Soft-delete the comment
        $comment->delete();

        // Log moderation action
        ModerationAction::create([
            'admin_user_id' => $admin->id,
            'comment_id' => $comment->id,
            'action' => 'delete',
            'reason' => $request->input('reason'),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'comment.delete',
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'meta' => ['job_id' => $comment->job_id, 'reason' => $request->input('reason')],
        ]);

        return $this->noContent('Comment removed by admin');
    }
}
