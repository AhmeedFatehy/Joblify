<?php

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function employerWithCompany(): User
{
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    Company::factory()->create(['user_id' => $employer->id]);

    return $employer;
}

// ── Analytics Overview ────────────────────────────────────────────────────────

test('employer can view their analytics', function () {
    $employer = employerWithCompany();

    $this->actingAs($employer)
        ->getJson('/api/employer/analytics')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'company',
                'total_jobs',
                'total_applications',
                'applications_by_status',
                'top_jobs',
                'recent_applications',
            ],
        ]);
});

test('candidate cannot access employer analytics', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);

    $this->actingAs($candidate)
        ->getJson('/api/employer/analytics')
        ->assertForbidden();
});

// ── Job Applications ──────────────────────────────────────────────────────────

test('employer can view applications for their job', function () {
    $employer = employerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    Application::factory(3)->create(['job_id' => $job->id]);

    $this->actingAs($employer)
        ->getJson("/api/employer/jobs/{$job->id}/applications")
        ->assertOk();
});

test('employer cannot view applications for another employer\'s job', function () {
    $employer = employerWithCompany();
    $employer2 = employerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer2->company->id]);

    $this->actingAs($employer)
        ->getJson("/api/employer/jobs/{$job->id}/applications")
        ->assertForbidden();
});
