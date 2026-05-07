<?php

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── List ──────────────────────────────────────────────────────────────────────

test('anyone authenticated can list comments on a job', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();
    Comment::factory(3)->create(['job_id' => $job->id]);

    $this->actingAs($user)
        ->getJson("/api/jobs/{$job->id}/comments")
        ->assertOk()
        ->assertJsonPath('success', true);
});

// ── Create ────────────────────────────────────────────────────────────────────

test('authenticated user can post a comment on a job', function () {
    $user = User::factory()->create(['role' => UserRole::EMPLOYER]);
    $job = Job::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson("/api/jobs/{$job->id}/comments", ['content' => 'Great opportunity!'])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('comments', [
        'job_id' => $job->id,
        'user_id' => $user->id,
        'content' => 'Great opportunity!',
    ]);
});

test('comment content is required', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $job = Job::factory()->approved()->create();

    $this->actingAs($user)
        ->postJson("/api/jobs/{$job->id}/comments", [])
        ->assertUnprocessable();
});

// ── Update ────────────────────────────────────────────────────────────────────

test('user can update their own comment', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $comment = Comment::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson("/api/comments/{$comment->id}", ['content' => 'Updated text'])
        ->assertOk();

    expect($comment->fresh()->content)->toBe('Updated text');
});

test('user cannot update someone else\'s comment', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $other = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $comment = Comment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->putJson("/api/comments/{$comment->id}", ['content' => 'Hijack'])
        ->assertForbidden();
});

// ── Delete ────────────────────────────────────────────────────────────────────

test('user can delete their own comment', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $comment = Comment::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/api/comments/{$comment->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

test('user cannot delete someone else\'s comment', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $other = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $comment = Comment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->deleteJson("/api/comments/{$comment->id}")
        ->assertForbidden();
});

test('admin can delete any comment', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $comment = Comment::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/comments/{$comment->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
