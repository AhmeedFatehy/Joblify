<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends BaseApiController
{
    /**
     * List all comments for a job.
     * GET /jobs/{job}/comments
     */
    public function index(Job $job): JsonResponse
    {
        $comments = $job->comments()
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return $this->paginated($comments, 'Comments retrieved successfully');
    }

    /**
     * Post a comment on a job.
     * POST /jobs/{job}/comments
     */
    public function store(Request $request, Job $job): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment = $job->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        return $this->created(
            $comment->load('user:id,name'),
            'Comment posted successfully'
        );
    }

    /**
     * Update the authenticated user's comment.
     * PUT /comments/{comment}
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update(['content' => $request->content]);

        return $this->success(
            $comment->load('user:id,name'),
            'Comment updated successfully'
        );
    }

    /**
     * Delete own comment (candidates/employers) or any comment (admin via policy).
     * DELETE /comments/{comment}
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return $this->noContent('Comment deleted successfully');
    }
}
