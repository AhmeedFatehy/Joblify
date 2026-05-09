<?php

use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

// ─────────────────────────────────────────────────────────────
// Public / Guest Access
// ─────────────────────────────────────────────────────────────

it('allows guests to list approved jobs', function () {
    Job::factory()->approved()->count(3)->create();
    Job::factory()->count(2)->create(['status' => JobStatus::PENDING]);

    $response = $this->getJson('/api/jobs');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

it('allows guests to view a specific job', function () {
    $job = Job::factory()->approved()->create();

    $response = $this->getJson("/api/jobs/{$job->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $job->id);
});

it('increments job views when shown', function () {
    $job = Job::factory()->approved()->create(['views' => 10]);

    $this->getJson("/api/jobs/{$job->id}");

    expect($job->fresh()->views)->toBe(11);
});

// ─────────────────────────────────────────────────────────────
// CRUD Operations & Authorization
// ─────────────────────────────────────────────────────────────

it('allows an employer to create a job', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $company = Company::factory()->create(['user_id' => $employer->id]);
    
    $category = Category::factory()->create();
    $skill = Skill::factory()->create();

    $jobData = [
        'title' => 'Software Engineer',
        'description' => 'A great job description with enough length.',
        'requirements' => 'Skill A, Skill B',
        'benefits' => 'Health insurance',
        'salary_min' => 5000,
        'salary_max' => 10000,
        'location' => 'New York',
        'work_type' => WorkType::REMOTE->value,
        'experience_level' => ExperienceLevel::MID->value,
        'deadline' => now()->addMonth()->format('Y-m-d'),
        'categories' => [$category->id],
        'skills' => [$skill->id],
        'company_id' => $company->id,
    ];

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson('/api/jobs', $jobData);

    $response->assertStatus(200) // Controller returns success() which is 200
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Software Engineer')
        ->assertJsonPath('data.status', JobStatus::PENDING->value);

    $this->assertDatabaseHas('job_listings', [
        'title' => 'Software Engineer',
        'company_id' => $company->id,
        'status' => JobStatus::PENDING->value,
    ]);
});

it('prevents a candidate from creating a job', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);
    
    $jobData = [
        'title' => 'Software Engineer',
        'description' => 'A great job description with enough length.',
        'location' => 'New York',
        'work_type' => WorkType::REMOTE->value,
        'experience_level' => ExperienceLevel::MID->value,
        'company_id' => Company::factory()->create()->id,
    ];

    $response = $this->actingAs($candidate, 'sanctum')
        ->postJson('/api/jobs', $jobData);

    $response->assertForbidden();
});

it('allows owner to update their job', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $company = Company::factory()->create(['user_id' => $employer->id]);
    $job = Job::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($employer, 'sanctum')
        ->patchJson("/api/jobs/{$job->id}", [
            'title' => 'Updated Title',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('prevents non-owner employer from updating a job', function () {
    $employerA = User::factory()->create(['role' => UserRole::EMPLOYER]);
    Company::factory()->create(['user_id' => $employerA->id]);
    
    $employerB = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $companyB = Company::factory()->create(['user_id' => $employerB->id]);
    $jobB = Job::factory()->create(['company_id' => $companyB->id]);

    $response = $this->actingAs($employerA, 'sanctum')
        ->patchJson("/api/jobs/{$jobB->id}", [
            'title' => 'Unauthorized Update',
        ]);

    $response->assertForbidden();
});

it('allows admin to update any job', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $job = Job::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/jobs/{$job->id}", [
            'title' => 'Admin Updated',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Admin Updated');
});

it('allows owner to delete their job', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $company = Company::factory()->create(['user_id' => $employer->id]);
    $job = Job::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($employer, 'sanctum')
        ->deleteJson("/api/jobs/{$job->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('job_listings', ['id' => $job->id]);
});

// ─────────────────────────────────────────────────────────────
// Search & Filtering
// ─────────────────────────────────────────────────────────────

it('filters jobs by location', function () {
    Job::factory()->approved()->create(['location' => 'Cairo']);
    Job::factory()->approved()->create(['location' => 'Alexandria']);

    $response = $this->getJson('/api/jobs?location=Cairo');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.location', 'Cairo');
});

it('filters jobs by category', function () {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    $jobA = Job::factory()->approved()->create();
    $jobA->categories()->attach($categoryA);

    $jobB = Job::factory()->approved()->create();
    $jobB->categories()->attach($categoryB);

    $response = $this->getJson("/api/jobs?category_id={$categoryA->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $jobA->id);
});

it('filters jobs by experience level', function () {
    Job::factory()->approved()->create(['experience_level' => ExperienceLevel::ENTRY]);
    Job::factory()->approved()->create(['experience_level' => ExperienceLevel::SENIOR]);

    $response = $this->getJson('/api/jobs?experience_level=' . ExperienceLevel::ENTRY->value);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.experience_level', ExperienceLevel::ENTRY->value);
});

it('filters jobs by salary range', function () {
    // Job 1: 5k - 10k
    Job::factory()->approved()->create(['salary_min' => 5000, 'salary_max' => 10000]);
    // Job 2: 12k - 20k
    Job::factory()->approved()->create(['salary_min' => 12000, 'salary_max' => 20000]);

    // Search for 8k - 15k (Should overlap with both)
    $response = $this->getJson('/api/jobs?salary_min=8000&salary_max=15000');
    $response->assertOk()->assertJsonCount(2, 'data');

    // Search for 3k - 4k (Should overlap with none)
    $response = $this->getJson('/api/jobs?salary_min=3000&salary_max=4000');
    $response->assertOk()->assertJsonCount(0, 'data');
});

it('filters jobs by posting date range', function () {
    // Yesterday
    Job::factory()->approved()->create(['created_at' => now()->subDays(2)]);
    // Today
    Job::factory()->approved()->create(['created_at' => now()]);

    $response = $this->getJson('/api/jobs?posted_within=24h');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('combines multiple filters correctly', function () {
    $category = Category::factory()->create();
    
    // Match
    $job = Job::factory()->approved()->create([
        'location' => 'Cairo',
        'experience_level' => ExperienceLevel::MID,
    ]);
    $job->categories()->attach($category);

    // No match (wrong location)
    Job::factory()->approved()->create([
        'location' => 'Giza',
        'experience_level' => ExperienceLevel::MID,
    ]);

    $response = $this->getJson("/api/jobs?location=Cairo&experience_level=" . ExperienceLevel::MID->value . "&category_id={$category->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// ─────────────────────────────────────────────────────────────
// Pagination
// ─────────────────────────────────────────────────────────────

it('paginates results', function () {
    Job::factory()->approved()->count(15)->create();

    $response = $this->getJson('/api/jobs?per_page=10');

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 15)
        ->assertJsonPath('meta.last_page', 2);
});

it('clamps per_page between 10 and 20', function () {
    Job::factory()->approved()->count(25)->create();

    // Too small -> 10
    $response = $this->getJson('/api/jobs?per_page=5');
    $response->assertJsonCount(10, 'data');

    // Too large -> 20
    $response = $this->getJson('/api/jobs?per_page=100');
    $response->assertJsonCount(20, 'data');
});

// ─────────────────────────────────────────────────────────────
// Search Accuracy (Requires MySQL FullText)
// ─────────────────────────────────────────────────────────────

it('searches by title and description', function () {
    // Note: This test might fail on some CI environments if FullText is not properly indexed immediately.
    // However, for testing purposes, we assume the DB supports it.
    
    $job1 = Job::factory()->approved()->create([
        'title' => 'Backend PHP Developer LaravelSpecialist',
        'description' => 'Looking for a PHP expert with deep knowledge in Laravel framework and database optimization.',
    ]);
    
    $job2 = Job::factory()->approved()->create([
        'title' => 'Frontend React Engineer',
        'description' => 'Looking for a Javascript expert with deep knowledge in React and modern UI libraries.',
    ]);

    // Search for "LaravelSpecialist"
    $response = $this->getJson('/api/jobs?search=+LaravelSpecialist');
    $response->assertOk();
    
    $data = $response->json('data');
    $ids = collect($data)->pluck('id')->toArray();
    
    // We expect job1 to be in results. 
    // In some test environments, FullText might need a commit or have delay, 
    // but with InnoDB it should work.
    expect($ids)->toContain($job1->id);
    expect($ids)->not->toContain($job2->id);
})->skip(fn() => DB::connection()->getDriverName() !== 'mysql', 'FullText search requires MySQL');
