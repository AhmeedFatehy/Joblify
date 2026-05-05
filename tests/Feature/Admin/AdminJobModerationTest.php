<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers ──────────────────────────────────────────────────────────────────

function adminUser(): User
{
    return User::factory()->create(['role' => UserRole::ADMIN]);
}

function pendingJob(): Job
{
    return Job::factory()->create(['status' => JobStatus::PENDING]);
}

// ── Index ────────────────────────────────────────────────────────────────────

test('admin can list pending jobs', function () {
    $admin = adminUser();
    Job::factory(3)->create(['status' => JobStatus::PENDING]);

    $this->actingAs($admin)
        ->getJson('/api/admin/jobs?status=pending')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('non-admin cannot access admin jobs list', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);

    $this->actingAs($candidate)
        ->getJson('/api/admin/jobs')
        ->assertForbidden();
});

// ── Approve ───────────────────────────────────────────────────────────────────

test('admin can approve a pending job', function () {
    $admin = adminUser();
    $job   = pendingJob();

    $this->actingAs($admin)
        ->postJson("/api/admin/jobs/{$job->id}/approve")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($job->fresh()->status)->toBe(JobStatus::APPROVED);
});

test('admin cannot approve an already-approved job', function () {
    $admin = adminUser();
    $job   = Job::factory()->approved()->create();

    $this->actingAs($admin)
        ->postJson("/api/admin/jobs/{$job->id}/approve")
        ->assertUnprocessable();
});

test('approving a job notifies the employer', function () {
    $admin = adminUser();
    $job   = pendingJob();

    $this->actingAs($admin)->postJson("/api/admin/jobs/{$job->id}/approve");

    $this->assertDatabaseHas('notifications', [
        'user_id' => $job->company->user_id,
        'type'    => 'job_approved',
    ]);
});

// ── Reject ────────────────────────────────────────────────────────────────────

test('admin can reject a pending job with a reason', function () {
    $admin = adminUser();
    $job   = pendingJob();

    $this->actingAs($admin)
        ->postJson("/api/admin/jobs/{$job->id}/reject", ['reason' => 'Violates guidelines.'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($job->fresh()->status)->toBe(JobStatus::REJECTED);
});

test('rejection requires a reason', function () {
    $admin = adminUser();
    $job   = pendingJob();

    $this->actingAs($admin)
        ->postJson("/api/admin/jobs/{$job->id}/reject", [])
        ->assertUnprocessable();
});

test('rejecting a job notifies the employer', function () {
    $admin = adminUser();
    $job   = pendingJob();

    $this->actingAs($admin)->postJson("/api/admin/jobs/{$job->id}/reject", ['reason' => 'Spam.']);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $job->company->user_id,
        'type'    => 'job_rejected',
    ]);
});