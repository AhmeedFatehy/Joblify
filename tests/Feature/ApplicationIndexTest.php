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

// ─────────────────────────────────────────────────────────────
// Success Cases
// ─────────────────────────────────────────────────────────────

it('returns only own applications for a candidate', function () {
    $candidateA = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $candidateB = User::factory()->create(['role' => UserRole::CANDIDATE]);

    Application::factory()->count(2)->create(['user_id' => $candidateA->id]);
    Application::factory()->count(3)->create(['user_id' => $candidateB->id]);

    $response = $this->actingAs($candidateA, 'sanctum')
        ->getJson('/api/applications');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Applications retrieved successfully')
        ->assertJsonCount(2, 'data');

    // Ensure every returned application belongs to candidateA
    collect($response->json('data'))->each(
        fn (array $app) => expect($app['user']['id'])->toBe($candidateA->id)
    );
});

it('returns only applications for jobs belonging to the employer', function () {
    $employerA = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyA = Company::factory()->create(['user_id' => $employerA->id]);
    $jobA = Job::factory()->approved()->create(['company_id' => $companyA->id]);

    $employerB = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyB = Company::factory()->create(['user_id' => $employerB->id]);
    $jobB = Job::factory()->approved()->create(['company_id' => $companyB->id]);

    Application::factory()->count(2)->create(['job_id' => $jobA->id]);
    Application::factory()->count(3)->create(['job_id' => $jobB->id]);

    $response = $this->actingAs($employerA, 'sanctum')
        ->getJson('/api/applications');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');

    // Ensure every returned application is for a job owned by employerA's company
    collect($response->json('data'))->each(function (array $app) use ($employerA) {
        $job = Job::find($app['job']['id']);
        expect($job->company->user_id)->toBe($employerA->id);
    });
});

it('returns all applications for an admin', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    Application::factory()->count(5)->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/applications');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(5, 'data');
});

it('includes pagination metadata', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);

    Application::factory()->count(15)->create(['user_id' => $candidate->id]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/applications');

    $response->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 15)
        ->assertJsonPath('meta.last_page', 2);
});

it('eager loads job and user relationships', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);

    Application::factory()->create(['user_id' => $candidate->id]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/applications');

    $response->assertOk();

    $app = $response->json('data.0');

    expect($app)->toHaveKey('job')
        ->and($app['job'])->toHaveKey('id')
        ->and($app)->toHaveKey('user')
        ->and($app['user'])->toHaveKey('id');
});

// ─────────────────────────────────────────────────────────────
// Authorization Errors
// ─────────────────────────────────────────────────────────────

it('rejects unauthenticated requests', function () {
    $response = $this->getJson('/api/applications');

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

// ─────────────────────────────────────────────────────────────
// GET /applications/{id}
// ─────────────────────────────────────────────────────────────

it('allows a candidate to view their own application detail', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->create(['user_id' => $candidate->id]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->getJson("/api/applications/{$application->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Application details retrieved successfully')
        ->assertJsonPath('data.id', $application->id)
        ->assertJsonPath('data.status', $application->status->value)
        ->assertJsonPath('data.status_label', $application->status->label())
        ->assertJsonPath('data.job.id', $application->job->id)
        ->assertJsonPath('data.job.title', $application->job->title)
        ->assertJsonPath('data.job.company.id', $application->job->company->id)
        ->assertJsonPath('data.job.company.name', $application->job->company->name)
        ->assertJsonPath('data.user.id', $candidate->id)
        ->assertJsonPath('data.user.name', $candidate->name)
        ->assertJsonPath('data.user.email', $candidate->email)
        ->assertJsonPath('data.cover_letter', $application->cover_letter)
        ->assertJsonPath('data.created_at', $application->created_at->toDateTimeString());
});

it('allows an employer to view an application for their job', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $company = Company::factory()->create(['user_id' => $employer->id]);
    $job = Job::factory()->approved()->create(['company_id' => $company->id]);
    $application = Application::factory()->create(['job_id' => $job->id]);

    $response = $this->actingAs($employer, 'sanctum')
        ->getJson("/api/applications/{$application->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $application->id);
});

it('allows an admin to view any application', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $application = Application::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/applications/{$application->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $application->id);
});

it('prevents a candidate from viewing another candidate application', function () {
    $candidateA = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $candidateB = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->create(['user_id' => $candidateB->id]);

    $response = $this->actingAs($candidateA, 'sanctum')
        ->getJson("/api/applications/{$application->id}");

    $response->assertForbidden()
        ->assertJsonPath('success', false);
});

it('prevents an employer from viewing an application for another job', function () {
    $employerA = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyA = Company::factory()->create(['user_id' => $employerA->id]);
    $jobA = Job::factory()->approved()->create(['company_id' => $companyA->id]);

    $employerB = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyB = Company::factory()->create(['user_id' => $employerB->id]);
    $jobB = Job::factory()->approved()->create(['company_id' => $companyB->id]);

    $application = Application::factory()->create(['job_id' => $jobB->id]);

    $response = $this->actingAs($employerA, 'sanctum')
        ->getJson("/api/applications/{$application->id}");

    $response->assertForbidden()
        ->assertJsonPath('success', false);
});

it('rejects unauthenticated show requests', function () {
    $application = Application::factory()->create();

    $response = $this->getJson("/api/applications/{$application->id}");

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

// ─────────────────────────────────────────────────────────────
// DELETE /applications/{id}
// ─────────────────────────────────────────────────────────────

it('allows a candidate to withdraw their own pending application', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->create([
        'user_id' => $candidate->id,
        'status' => ApplicationStatus::PENDING,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->deleteJson("/api/applications/{$application->id}");

    $response->assertStatus(204);

    expect(Application::find($application->id))->toBeNull();
});

it('prevents withdrawing an accepted application', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->accepted()->create([
        'user_id' => $candidate->id,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->deleteJson("/api/applications/{$application->id}");

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Only pending applications can be withdrawn.');

    expect(Application::find($application->id))->not->toBeNull();
});

it('prevents withdrawing a rejected application', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->rejected()->create([
        'user_id' => $candidate->id,
    ]);

    $response = $this->actingAs($candidate, 'sanctum')
        ->deleteJson("/api/applications/{$application->id}");

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Only pending applications can be withdrawn.');

    expect(Application::find($application->id))->not->toBeNull();
});

it('prevents a candidate from withdrawing another candidate application', function () {
    $candidateA = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $candidateB = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $application = Application::factory()->create([
        'user_id' => $candidateB->id,
        'status' => ApplicationStatus::PENDING,
    ]);

    $response = $this->actingAs($candidateA, 'sanctum')
        ->deleteJson("/api/applications/{$application->id}");

    $response->assertForbidden()
        ->assertJsonPath('success', false);

    expect(Application::find($application->id))->not->toBeNull();
});

it('prevents an employer from withdrawing an application', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $application = Application::factory()->create([
        'status' => ApplicationStatus::PENDING,
    ]);

    $response = $this->actingAs($employer, 'sanctum')
        ->deleteJson("/api/applications/{$application->id}");

    $response->assertForbidden()
        ->assertJsonPath('success', false);

    expect(Application::find($application->id))->not->toBeNull();
});

// Note: Admins bypass all policies via Gate::before in AppServiceProvider,
// so they CAN withdraw applications. This is consistent with the platform design.

it('rejects unauthenticated delete requests', function () {
    $application = Application::factory()->create();

    $response = $this->deleteJson("/api/applications/{$application->id}");

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});
