<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Requests\Apply\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Job;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ApplicationController extends BaseApiController
{
    public function __construct(
        private ApplicationService $applicationService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Application::class);
        $user = Auth::user();

        $applicationsQuery = $this->applicationService->getAllQuery();

        if ($user->role === UserRole::CANDIDATE) {
            $applicationsQuery->where('user_id', $user->id);
        } elseif ($user->role === UserRole::EMPLOYER) {
            $applicationsQuery->whereHas('job.company', fn ($q) => $q->where('user_id', $user->id));
        }
        $applications = $applicationsQuery->latest()->paginate(10);

        return $this->success(
            ApplicationResource::collection($applications),
            'Applications retrieved successfully',
            200,
            [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'from' => $applications->firstItem(),
                'to' => $applications->lastItem(),
            ]);
    }

    /**
     * List all applications for a specific job.
     * GET /jobs/{job}/applications
     */
    public function jobApplications(Request $request, Job $job): JsonResponse
    {
        if (Auth::user()->id !== $job->company->user_id) {
            return $this->forbidden('You do not own this job posting.');
        }

        $request->validate([
            'status' => ['sometimes', Rule::in(ApplicationStatus::values())],
        ]);

        $applications = $this->applicationService
            ->getJobApplicationsQuery($job, $request->query('status'))
            ->paginate(15);

        return $this->success(
            ApplicationResource::collection($applications),
            'Applications retrieved successfully',
            200,
            [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'from' => $applications->firstItem(),
                'to' => $applications->lastItem(),
            ]
        );
    }

    /**
     * Submit a new application for a job.
     */
    public function store(StoreApplicationRequest $request, Job $job): JsonResponse
    {
        $application = $this->applicationService->submit($request, $job);

        return $this->created(
            $application->load(['job', 'user']),
            'Application submitted successfully'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $application->load(['job.company', 'user']);

        return $this->success(
            ApplicationResource::make($application),
            'Application details retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(UpdateApplicationStatusRequest $request, Application $application): JsonResponse
    {
        $this->authorize('updateStatus', $application);

        $updatedApplication = $this->applicationService->updateStatus($request, $application);

        return $this->success(
            ApplicationResource::make($updatedApplication->load(['job.company', 'user'])),
            'Application status updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application): JsonResponse
    {
        $this->authorize('delete', $application);

        if (! $application->status->isPending()) {
            return $this->error('Only pending applications can be withdrawn.', 422);
        }

        $application->delete();

        return $this->noContent('Application withdrawn successfully');
    }
}
