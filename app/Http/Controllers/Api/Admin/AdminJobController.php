<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminJobController extends BaseApiController
{
    /**
     * List all pending jobs awaiting moderation.
     * GET /admin/jobs?status=pending
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $jobs = Job::with(['company'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return $this->paginated($jobs, 'Jobs retrieved successfully');
    }

    /**
     * Approve a pending job posting.
     * POST /admin/jobs/{job}/approve
     */
    public function approve(Job $job): JsonResponse
    {
        if ($job->status !== JobStatus::PENDING) {
            return $this->error(
                'Only pending jobs can be approved. Current status: '.$job->status->value,
                422
            );
        }

        $job->update(['status' => JobStatus::APPROVED]);

        // Notify the employer
        $job->company->user->notifications()->create([
            'type' => 'job_approved',
            'message' => "Your job posting \"{$job->title}\" has been approved and is now live.",
            'is_read' => false,
        ]);

        return $this->success(
            $job->fresh(['company']),
            'Job approved successfully'
        );
    }

    /**
     * Reject a pending job posting with a reason.
     * POST /admin/jobs/{job}/reject
     */
    public function reject(Request $request, Job $job): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($job->status !== JobStatus::PENDING) {
            return $this->error(
                'Only pending jobs can be rejected. Current status: '.$job->status->value,
                422
            );
        }

        $job->update(['status' => JobStatus::REJECTED]);

        // Notify the employer with the rejection reason
        $job->company->user->notifications()->create([
            'type' => 'job_rejected',
            'message' => "Your job posting \"{$job->title}\" was rejected. Reason: {$request->reason}",
            'is_read' => false,
        ]);

        return $this->success(
            $job->fresh(['company']),
            'Job rejected successfully'
        );
    }
}
