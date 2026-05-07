<?php

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can list all comments', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    Comment::factory(5)->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/comments')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('admin can remove any comment', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $comment = Comment::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/comments/{$comment->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

test('non-admin cannot access admin comment moderation', function () {
    $employer = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $comment = Comment::factory()->create();

    $this->actingAs($employer)
        ->deleteJson("/api/admin/comments/{$comment->id}")
        ->assertForbidden();
});

test('admin can filter comments by job', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $job = Job::factory()->approved()->create();
    Comment::factory(3)->create(['job_id' => $job->id]);
    Comment::factory(2)->create();

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/comments?job_id={$job->id}")
        ->assertOk()
        ->json();

    expect($response['meta']['total'])->toBe(3);
});
