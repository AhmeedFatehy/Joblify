<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployerAnalyticsController extends BaseApiController
{
    /**
     * Analytics overview for the authenticated employer.
     * GET /employer/analytics
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== UserRole::EMPLOYER || ! $user->company) {
            return $this->forbidden('You must be an employer with a company to access analytics.');
        }

        $company = $user->company;
        $jobIds = $company->jobs()->pluck('id');

        // Date range filtering for applications
        $from = $request->query('from');
        $to = $request->query('to');

        $applicationsQuery = Application::whereIn('job_id', $jobIds);
        if ($from) {
            $applicationsQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $applicationsQuery->where('created_at', '<=', $to);
        }

        $totalApplications = $applicationsQuery->count();

        $byStatusQuery = Application::whereIn('job_id', $jobIds);
        if ($from) {
            $byStatusQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $byStatusQuery->where('created_at', '<=', $to);
        }

        $byStatus = $byStatusQuery
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top jobs include views and application counts; conversion rate = applications/views (if views > 0)
        $topJobs = $company->jobs()
            ->withCount(['applications' => function ($q) use ($from, $to) {
                if ($from) {
                    $q->where('created_at', '>=', $from);
                }
                if ($to) {
                    $q->where('created_at', '<=', $to);
                }
            }])
            ->orderByDesc('applications_count')
            ->take(5)
            ->get(['id', 'title', 'status', 'views']);

        $recentApplications = $applicationsQuery
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
            'top_jobs' => $topJobs->map(function ($job) {
                $apps = $job->applications_count ?? 0;
                $views = $job->views ?? 0;
                $conversion = ($views > 0) ? round($apps / $views, 4) : 0;

                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                    'applications_count' => $apps,
                    'views' => $views,
                    'conversion_rate' => $conversion,
                ];
            }),
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

        $applicationsQuery = $job->applications()->with('user:id,name,email,phone,linkedin_url');

        // Support optional date filtering
        $from = request()->query('from');
        $to = request()->query('to');
        if ($from) {
            $applicationsQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $applicationsQuery->where('created_at', '<=', $to);
        }

        $applications = $applicationsQuery->latest()->paginate(15);

        return $this->paginated($applications, 'Applications retrieved successfully');
    }
}
