<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\Application;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends BaseApiController
{
    /**
     * Platform overview metrics.
     * GET /admin/dashboard
     */
    public function index(): JsonResponse
    {
        $stats = [
            'users' => $this->userStats(),
            'jobs' => $this->jobStats(),
            'applications' => $this->applicationStats(),
            'companies' => Company::count(),
            'comments' => Comment::count(),
        ];

        return $this->success($stats, 'Dashboard data retrieved successfully');
    }

    /**
     * Recent activity feed for the admin.
     * GET /admin/dashboard/activity
     */
    public function activity(): JsonResponse
    {
        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'role', 'created_at']);
        $recentJobs = Job::with('company:id,name')->latest()->take(5)->get(['id', 'company_id', 'title', 'status', 'created_at']);
        $recentApps = Application::with(['user:id,name', 'job:id,title'])->latest()->take(5)->get();

        return $this->success([
            'recent_users' => $recentUsers,
            'recent_jobs' => $recentJobs,
            'recent_applications' => $recentApps,
        ], 'Recent activity retrieved successfully');
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    private function userStats(): array
    {
        return [
            'total' => User::count(),
            'candidates' => User::where('role', UserRole::CANDIDATE)->count(),
            'employers' => User::where('role', UserRole::EMPLOYER)->count(),
            'admins' => User::where('role', UserRole::ADMIN)->count(),
        ];
    }

    private function jobStats(): array
    {
        return [
            'total' => Job::count(),
            'pending' => Job::where('status', JobStatus::PENDING)->count(),
            'approved' => Job::where('status', JobStatus::APPROVED)->count(),
            'rejected' => Job::where('status', JobStatus::REJECTED)->count(),
        ];
    }

    private function applicationStats(): array
    {
        return [
            'total' => Application::count(),
            'pending' => Application::where('status', ApplicationStatus::PENDING)->count(),
            'accepted' => Application::where('status', ApplicationStatus::ACCEPTED)->count(),
            'rejected' => Application::where('status', ApplicationStatus::REJECTED)->count(),
        ];
    }
}
