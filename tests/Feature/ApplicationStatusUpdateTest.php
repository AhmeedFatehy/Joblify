<?php

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('r2');
});

function statusUpdateEmployerWithCompany(): User
{
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    Company::factory()->create(['user_id' => $employer->id]);

    return $employer;
}

// ── Success Cases ────────────────────────────────────────────────────────────

test('employer can accept a pending application', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $response = $this->actingAs($employer)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::ACCEPTED->value,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ApplicationStatus::ACCEPTED->value)
        ->assertJsonPath('data.rejection_reason', null);

    expect($application->fresh()->status)->toBe(ApplicationStatus::ACCEPTED);
});

test('employer can reject an application with a reason', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $response = $this->actingAs($employer)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::REJECTED->value,
            'rejection_reason' => 'We decided to move forward with another candidate.',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', ApplicationStatus::REJECTED->value)
        ->assertJsonPath('data.rejection_reason', 'We decided to move forward with another candidate.');

    expect($application->fresh()->status)->toBe(ApplicationStatus::REJECTED);
});

test('employer can reject an application without a reason', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $response = $this->actingAs($employer)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::REJECTED->value,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rejection_reason', null);
});

// ── Authorization ────────────────────────────────────────────────────────────

test('unauthenticated user cannot update application status', function () {
    $application = Application::factory()->create();
    $this->patchJson("/api/applications/{$application->id}/status", [
        'status' => ApplicationStatus::ACCEPTED->value,
    ])->assertUnauthorized();
});

test('admin can update any application status via gate bypass', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $application = Application::factory()->create();
    $this->actingAs($admin)->patchJson("/api/applications/{$application->id}/status", [
        'status' => ApplicationStatus::ACCEPTED->value,
    ])->assertOk();
});

test('candidate cannot update application status', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->create(['user_id' => $candidate->id]);

    $this->actingAs($candidate)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::ACCEPTED->value,
        ])
        ->assertForbidden();
});

test('employer cannot update application for another employers job', function () {
    $employer1 = statusUpdateEmployerWithCompany();
    $employer2 = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer2->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $this->actingAs($employer1)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::ACCEPTED->value,
        ])
        ->assertForbidden();
});

// ── Validation ───────────────────────────────────────────────────────────────

test('status must be accepted or rejected', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $this->actingAs($employer)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::PENDING->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('rejection reason is prohibited when status is accepted', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $this->actingAs($employer)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::ACCEPTED->value,
            'rejection_reason' => 'Some reason',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rejection_reason']);
});

// ── Business Rules ───────────────────────────────────────────────────────────

test('only pending applications can be updated', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->accepted()->create(['job_id' => $job->id]);

    $this->actingAs($employer)
        ->patchJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::REJECTED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Only pending applications can be updated.');
});

// ── Notifications ────────────────────────────────────────────────────────────

test('accepting application sends notification to candidate', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $this->actingAs($employer)->patchJson("/api/applications/{$application->id}/status", [
        'status' => ApplicationStatus::ACCEPTED->value,
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $application->user_id,
        'type' => 'application_status_changed',
    ]);
});

test('rejection notification includes reason when provided', function () {
    $employer = statusUpdateEmployerWithCompany();
    $job = Job::factory()->create(['company_id' => $employer->company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $this->actingAs($employer)->patchJson("/api/applications/{$application->id}/status", [
        'status' => ApplicationStatus::REJECTED->value,
        'rejection_reason' => 'Missing required skills.',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $application->user_id,
        'type' => 'application_status_changed',
        'message' => 'Your application for "'.$job->title.'" has been Rejected. Reason: Missing required skills.',
    ]);
});
