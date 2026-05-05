<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

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
    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return $this->noContent('Comment removed by admin');
    }
}