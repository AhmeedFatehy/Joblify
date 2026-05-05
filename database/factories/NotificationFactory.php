<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type'    => fake()->randomElement([
                'job_approved',
                'job_rejected',
                'application_status_changed',
            ]),
            'message' => fake()->sentence(),
            'is_read' => fake()->boolean(30), // 30% chance of being read
        ];
    }

    public function unread(): static
    {
        return $this->state(fn () => ['is_read' => false]);
    }

    public function read(): static
    {
        return $this->state(fn () => ['is_read' => true]);
    }
}