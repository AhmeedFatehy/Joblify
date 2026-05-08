<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use Exception;

class JobController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $jobs = Job::with('company', 'categories', 'skills')->get();

            return $this->success(JobResource::collection($jobs), 'Jobs retrieved successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJobRequest $request)
    {
        try {
            $this->authorize('create', Job::class);
            $validatedData = $request->validated();
            $validatedData['company_id'] = $request->user()->company->id;
            $validatedData['status'] = JobStatus::PENDING->value;
            $job = Job::create($validatedData);

            if ($request->filled('categories')) {
                $job->categories()->sync($request->categories);
            }
            if ($request->filled('skills')) {
                $job->skills()->sync($request->skills);
            }

            $job->load('company', 'categories', 'skills');

            return $this->success(new JobResource($job), 'Job created successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Job $job)
    {
        try {
            // Increment total views counter for analytics (simple total views).
            try {
                $job->increment('views');
                $job->refresh();
            } catch (Exception $e) {
                // swallow increment errors to avoid breaking read endpoint
            }

            $job->load('company', 'categories', 'skills');

            return $this->success(JobResource::make($job), 'Job retrieved successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJobRequest $request, Job $job)
    {
        try {
            $this->authorize('update', $job);
            $validatedData = $request->validated();
            $job->update($validatedData);

            if ($request->filled('categories')) {
                $job->categories()->sync($request->categories);
            }
            if ($request->filled('skills')) {
                $job->skills()->sync($request->skills);
            }

            $job->load('company', 'categories', 'skills');

            return $this->success(new JobResource($job), 'Job updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Job $job)
    {
        $this->authorize('delete', $job);
        $job->delete();

        return $this->success(null, 'Job deleted successfully');
    }

    public function employerJobs(Request $request)
    {
        $jobs = Job::where('company_id', $request->user()->company->id)
            ->with('company', 'categories', 'skills')
            ->latest()
            ->get();

        return JobResource::collection($jobs);
    }
}
