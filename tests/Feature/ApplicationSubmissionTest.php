<?php

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('r2');
});

// ─────────────────────────────────────────────────────────────
// Success Cases
// ─────────────────────────────────────────────────────────────

it('allows a candidate to apply for an approved job', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
            'cover_letter' => 'I am very interested in this position.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Application submitted successfully')
        ->assertJsonPath('data.job_id', $job->id)
        ->assertJsonPath('data.user_id', $candidate->id)
        ->assertJsonPath('data.status', ApplicationStatus::PENDING->value)
        ->assertJsonPath('data.cover_letter', 'I am very interested in this position.');

    Storage::disk('r2')->assertExists($response->json('data.resume'));
});

it('allows application without cover letter', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.cover_letter', null);
});

// ─────────────────────────────────────────────────────────────
// Validation Errors
// ─────────────────────────────────────────────────────────────

it('requires a resume file', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'cover_letter' => 'I am interested.',
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['resume']);
});

it('rejects invalid file types', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.jpg', 100),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['resume']);
});

it('rejects files larger than 5MB', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 5121), // 5MB + 1KB
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['resume']);
});

// ─────────────────────────────────────────────────────────────
// Authorization / Business Rule Errors
// ─────────────────────────────────────────────────────────────

it('rejects unauthenticated requests', function () {
    $job = Job::factory()->approved()->create();

    $response = $this->postJson("/api/jobs/{$job->id}/apply", [
        'resume' => UploadedFile::fake()->create('resume.pdf', 100),
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('prevents employers from applying', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertForbidden()
        ->assertJsonPath('success', false);
});

it('prevents admins from applying', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $job = Job::factory()->approved()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertForbidden()
        ->assertJsonPath('success', false);
});

it('prevents applying to a pending job', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->create(['status' => JobStatus::PENDING]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertBadRequest()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'This job is not open for applications.');
});

it('prevents applying to a rejected job', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->create(['status' => JobStatus::REJECTED]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertBadRequest()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'This job is not open for applications.');
});

it('prevents applying after the deadline', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->expired()->create();

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertBadRequest()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The application deadline has passed.');
});

it('prevents employer from applying to their own job', function () {
    // To test the own-job rule, we need a candidate who also owns a company/job
    // This tests the controller/service layer business rule
    $employer = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $company = Company::factory()->create(['user_id' => $employer->id]);
    $job = Job::factory()->approved()->create(['company_id' => $company->id]);

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

    $response->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'You cannot apply to your own job posting.');
});

it('prevents duplicate applications', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    // First application
    $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100),
        ])
        ->assertCreated();

    // Duplicate attempt
    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson("/api/jobs/{$job->id}/apply", [
            'resume' => UploadedFile::fake()->create('resume2.pdf', 100),
        ]);

    $response->assertStatus(409)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'You have already applied to this job.');
});
