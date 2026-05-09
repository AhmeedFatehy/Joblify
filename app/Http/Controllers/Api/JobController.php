<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Job::query()->where('status', JobStatus::APPROVED->value)
                ->with('company', 'categories', 'skills');


            if ($request->filled('search')) {
                $search = $request->search;

                $query->selectRaw(
                        'job_listings.*, MATCH(title, description) AGAINST(? IN BOOLEAN MODE) AS relevance',
                        [$search]
                    )
                    ->whereRaw(
                        'MATCH(title, description) AGAINST(? IN BOOLEAN MODE)',
                        [$search]
                    );
            }

            // Filter by location
            if ($request->filled('location')) {
                $query->where('location', 'like', "%{$request->location}%");
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('id', $request->category_id);
                });
            }

            // Filter by experience level
            if ($request->filled('experience_level')) {
                $query->where('experience_level', $request->experience_level);
            }

            // Filter by salary range
            $salaryMin = $request->query('salary_min');
            $salaryMax = $request->query('salary_max');

            if ($salaryMin !== null || $salaryMax !== null) {
                $query->where(function ($q) use ($salaryMin, $salaryMax) {
                    if ($salaryMin !== null && $salaryMax !== null) {
                        $q->where('salary_min', '<=', $salaryMax)
                          ->where('salary_max', '>=', $salaryMin);
                    } elseif ($salaryMin !== null) {
                        $q->where('salary_max', '>=', $salaryMin);
                    } else {
                        $q->where('salary_min', '<=', $salaryMax);
                    }
                });
            }

            // Filter by post date
            if ($request->filled('posted_within')) {
                $range = $request->posted_within;
                $date = match ($range) {
                    '24h' => now()->subDay(),
                    'week' => now()->subWeek(),
                    'month' => now()->subMonth(),
                    default => null,
                };

                if ($date) {
                    $query->where('created_at', '>=', $date);
                }
            }

            // Sort: relevance (default, by created_at) or date
            $sort = $request->get('sort', 'relevance');
            if ($sort === 'date' || ! $request->filled('search')) {
                $query->orderBy('created_at', 'desc');
            } else {
                $query->orderByDesc('relevance')
                      ->orderBy('created_at', 'desc');
            }

            // Pagination: 10-20 per page, default 10
            $perPage = $request->get('per_page', 10);
            $perPage = min(max($perPage, 10), 20); // Clamp to 10-20
            $jobs = $query->paginate($perPage)->withQueryString();;

            return $this->paginated($jobs, 'Jobs retrieved successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJobRequest $request)
    {
        $this->authorize('create', Job::class);

        try {
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
        $this->authorize('update', $job);

        try {
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

        try {
            $job->delete();
            return $this->success(null, 'Job deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
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