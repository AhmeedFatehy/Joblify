<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Apply\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Job;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

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

        if($user->role === UserRole::CANDIDATE) {
            $applicationsQuery->where('user_id', $user->id);
        }
        elseif ($user->role === UserRole::EMPLOYER) {
            $applicationsQuery->whereHas('job.company', fn($q)=> $q->where('user_id', $user->id));
        }
        return $this->paginated($applicationsQuery->latest()->paginate(10), 'Applications retrieved successfully');
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Application $application): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application): JsonResponse
    {
        //
    }
}
