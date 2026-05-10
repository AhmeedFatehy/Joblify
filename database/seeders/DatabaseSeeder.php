<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\Company;
use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        User::updateOrCreate(
            ['email' => 'admin@joblify.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin_password'),
                'role' => UserRole::ADMIN,
            ]
        );

         // Seed users
        User::factory()->count(5)->create();

        // Seed companies
        Company::factory()->count(5)->create();

        // Seed categories
        Category::factory()->count(5)->create();

        // Seed skills
        Skill::factory()->count(5)->create();

        // Seed jobs (assuming Job factory might depend on Company)
        Job::factory()->count(10)->create();

    }
}
