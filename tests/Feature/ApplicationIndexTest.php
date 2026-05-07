<?php

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
        fn (array $app) => expect($app['user_id'])->toBe($candidateA->id)
    );
});

it('returns only applications for jobs belonging to the employer', function () {
    $employerA = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyA  = Company::factory()->create(['user_id' => $employerA->id]);
    $jobA      = Job::factory()->approved()->create(['company_id' => $companyA->id]);

    $employerB = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyB  = Company::factory()->create(['user_id' => $employerB->id]);
    $jobB      = Job::factory()->approved()->create(['company_id' => $companyB->id]);

    Application::factory()->count(2)->create(['job_id' => $jobA->id]);
    Application::factory()->count(3)->create(['job_id' => $jobB->id]);

    $response = $this->actingAs($employerA, 'sanctum')
        ->getJson('/api/applications');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');

    // Ensure every returned application is for a job owned by employerA's company
    collect($response->json('data'))->each(function (array $app) use ($employerA) {
        $job = Job::find($app['job_id']);
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
