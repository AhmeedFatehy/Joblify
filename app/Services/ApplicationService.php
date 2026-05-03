<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Exceptions\ApiException;
use App\Http\Requests\Apply\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Contracts\Filesystem\Factory as StorageFactory;
use Illuminate\Support\Facades\Auth;

class ApplicationService
{
    public function __construct(
        private StorageFactory $storage
    ) {}

    /**
     * Submit a new job application.
     */
    public function submit(StoreApplicationRequest $request, Job $job): Application
    {
        $this->ensureJobIsOpen($job);
        $this->ensureNotOwnJob($job);
        $this->ensureNotDuplicate($job);

        $resumePath = $this->storage
            ->disk('r2')
            ->putFile('resumes', $request->file('resume'));

        return Application::create([
            'job_id' => $job->id,
            'user_id' => Auth::id(),
            'resume' => $resumePath,
            'cover_letter' => $request->input('cover_letter'),
            'status' => ApplicationStatus::PENDING,
        ]);
    }

    /**
     * Generate a public URL for a stored resume.
     */
    public function resumeUrl(string $path): string
    {
        return $this->storage->disk('r2')->url($path);
    }

    private function ensureJobIsOpen(Job $job): void
    {
        if ($job->status !== JobStatus::APPROVED) {
            throw new ApiException('This job is not open for applications.', 400);
        }

        if ($job->deadline && $job->deadline->isPast()) {
            throw new ApiException('The application deadline has passed.', 400);
        }
    }

    private function ensureNotOwnJob(Job $job): void
    {
        if ($job->company->user_id === Auth::id()) {
            throw new ApiException('You cannot apply to your own job posting.', 403);
        }
    }

    private function ensureNotDuplicate(Job $job): void
    {
        $exists = Application::where('job_id', $job->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($exists) {
            throw new ApiException('You have already applied to this job.', 409);
        }
    }
}
