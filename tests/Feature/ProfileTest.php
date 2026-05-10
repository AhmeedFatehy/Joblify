<?php

use App\Enums\UserRole;
use App\Models\Skill;
use App\Models\User;

describe('Profile Endpoints', function () {
    it('returns profile data with title and skills', function () {
        $user = User::factory()->create([
            'role' => UserRole::CANDIDATE,
            'title' => 'Full Stack Developer',
        ]);
        $skills = Skill::factory(3)->create();
        $user->skills()->attach($skills);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.title', 'Full Stack Developer')
            ->assertJsonStructure(['data' => ['skills']]);

        expect($response->json('data.skills'))->toHaveCount(3);
    });

    it('allows updating profile with title and skills', function () {
        $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
        $skills = Skill::factory(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile', [
                'name' => 'Jane Doe',
                'title' => 'Senior Developer',
                'phone' => '+1234567890',
                'linkedin_url' => 'https://linkedin.com/in/jane',
                'skills' => $skills->pluck('id')->toArray(),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile updated successfully');

        $user->refresh();
        expect($user->title)->toBe('Senior Developer');
        expect($user->skills->count())->toBe(2);
    });

    it('allows clearing skills by sending empty array', function () {
        $user = User::factory()->create(['role' => UserRole::CANDIDATE]);
        $skills = Skill::factory(2)->create();
        $user->skills()->attach($skills);

        expect($user->skills->count())->toBe(2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile', [
                'name' => $user->name,
                'skills' => [],
            ]);

        $response->assertOk();

        $user->refresh();
        expect($user->skills->count())->toBe(0);
    });

    it('validates skill IDs exist in database', function () {
        $user = User::factory()->create(['role' => UserRole::CANDIDATE]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile', [
                'name' => 'Jane Doe',
                'skills' => [999999], // Non-existent skill ID
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['skills.0']);
    });

    it('returns empty skills array when no skills added', function () {
        $user = User::factory()->create(['role' => UserRole::CANDIDATE]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('data.skills', []);
    });
});

