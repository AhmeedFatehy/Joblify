<?php

namespace Database\Factories;

use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkType;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraph(),
            'benefits' => fake()->paragraph(),
            'salary_min' => fake()->numberBetween(30000, 50000),
            'salary_max' => fake()->numberBetween(60000, 150000),
            'location' => fake()->city(),
            'work_type' => fake()->randomElement(WorkType::cases()),
            'experience_level' => fake()->randomElement(ExperienceLevel::cases()),
            'deadline' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'status' => JobStatus::PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::REJECTED,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => fake()->dateTimeBetween('-3 months', '-1 day'),
        ]);
    }
}
