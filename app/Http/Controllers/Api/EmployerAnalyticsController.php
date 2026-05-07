<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Job;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployerAnalyticsController extends BaseApiController
{
    /**
     * Analytics overview for the authenticated employer.
     * GET /employer/analytics
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== UserRole::EMPLOYER || ! $user->company) {
            return $this->forbidden('You must be an employer with a company to access analytics.');
        }

        $company = $user->company;
        $jobIds = $company->jobs()->pluck('id');

        $totalApplications = Application::whereIn('job_id', $jobIds)->count();

        $byStatus = Application::whereIn('job_id', $jobIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $topJobs = $company->jobs()
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->take(5)
            ->get(['id', 'title', 'status', 'applications_count']);

        $recentApplications = Application::whereIn('job_id', $jobIds)
            ->with(['user:id,name,email', 'job:id,title'])
            ->latest()
            ->take(10)
            ->get();

        return $this->success([
            'company' => $company->only('id', 'name'),
            'total_jobs' => $company->jobs()->count(),
            'total_applications' => $totalApplications,
            'applications_by_status' => [
                'pending' => $byStatus[ApplicationStatus::PENDING->value] ?? 0,
                'accepted' => $byStatus[ApplicationStatus::ACCEPTED->value] ?? 0,
                'rejected' => $byStatus[ApplicationStatus::REJECTED->value] ?? 0,
            ],
            'top_jobs' => $topJobs,
            'recent_applications' => $recentApplications,
        ], 'Analytics retrieved successfully');
    }

    /**
     * Applications for a specific job the employer owns.
     * GET /employer/jobs/{job}/applications
     */
    public function jobApplications(Job $job): JsonResponse
    {
        $user = Auth::user();

        if ($job->company->user_id !== $user->id) {
            return $this->forbidden('You do not own this job posting.');
        }

        $applications = $job->applications()
            ->with('user:id,name,email,phone,linkedin_url')
            ->latest()
            ->paginate(15);

        return $this->paginated($applications, 'Applications retrieved successfully');
    }

    /**
     * Accept or reject an application.
     * PATCH /employer/applications/{application}
     */
    public function updateApplicationStatus(Request $request, Application $application): JsonResponse
    {
        $user = Auth::user();

        if ($application->job->company->user_id !== $user->id) {
            return $this->forbidden('You do not own the job for this application.');
        }

        $request->validate([
            'status' => ['required', Rule::in([
                ApplicationStatus::ACCEPTED->value,
                ApplicationStatus::REJECTED->value,
            ])],
        ]);

        $application->update(['status' => $request->status]);

        // Notify the candidate
        app(NotificationService::class)
            ->notifyApplicationStatusChanged($application->load('job'));

        return $this->success(
            $application->fresh(['user:id,name', 'job:id,title']),
            'Application status updated successfully'
        );
    }
}
