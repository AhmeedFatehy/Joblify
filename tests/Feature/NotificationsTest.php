<?php

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── List ──────────────────────────────────────────────────────────────────────

test('user can list their notifications', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    Notification::factory(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['notifications', 'unread_count']]);
});

test('user only sees their own notifications', function () {
    $user  = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $other = User::factory()->create(['role' => UserRole::CANDIDATE]);

    Notification::factory(2)->create(['user_id' => $user->id]);
    Notification::factory(5)->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->getJson('/api/notifications')->json();

    expect($response['data']['notifications']['total'])->toBe(2);
});

test('user can filter unread notifications', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    Notification::factory(2)->create(['user_id' => $user->id, 'is_read' => false]);
    Notification::factory(3)->create(['user_id' => $user->id, 'is_read' => true]);

    $response = $this->actingAs($user)->getJson('/api/notifications?unread=true')->json();

    expect($response['data']['notifications']['total'])->toBe(2);
});

// ── Mark Single Read ──────────────────────────────────────────────────────────

test('user can mark a notification as read', function () {
    $user         = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $notification = Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);

    $this->actingAs($user)
        ->patchJson("/api/notifications/{$notification->id}/read")
        ->assertOk();

    expect($notification->fresh()->is_read)->toBeTrue();
});

test('user cannot mark another user\'s notification as read', function () {
    $user  = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $other = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $n     = Notification::factory()->create(['user_id' => $other->id, 'is_read' => false]);

    $this->actingAs($user)
        ->patchJson("/api/notifications/{$n->id}/read")
        ->assertForbidden();
});

// ── Mark All Read ─────────────────────────────────────────────────────────────

test('user can mark all notifications as read', function () {
    $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
    Notification::factory(4)->create(['user_id' => $user->id, 'is_read' => false]);

    $this->actingAs($user)
        ->postJson('/api/notifications/read-all')
        ->assertOk();

    expect(
        Notification::where('user_id', $user->id)->where('is_read', false)->count()
    )->toBe(0);
});

// ── Delete ────────────────────────────────────────────────────────────────────

test('user can delete their notification', function () {
    $user         = User::factory()->create(['role' => UserRole::CANDIDATE]);
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/api/notifications/{$notification->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('unauthenticated user cannot access notifications', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();
});