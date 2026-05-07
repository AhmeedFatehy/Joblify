<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view dashboard stats', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $this->actingAs($admin)
        ->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['users', 'jobs', 'applications', 'companies', 'comments'],
        ]);
});

test('admin can view recent activity', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $this->actingAs($admin)
        ->getJson('/api/admin/dashboard/activity')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['recent_users', 'recent_jobs', 'recent_applications'],
        ]);
});

test('candidate cannot access admin dashboard', function () {
    $candidate = User::factory()->create(['role' => UserRole::CANDIDATE]);

    $this->actingAs($candidate)
        ->getJson('/api/admin/dashboard')
        ->assertForbidden();
});

test('unauthenticated user cannot access admin dashboard', function () {
    $this->getJson('/api/admin/dashboard')
        ->assertUnauthorized();
});
