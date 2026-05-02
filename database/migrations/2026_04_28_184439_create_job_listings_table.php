<?php

use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();

            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();

            $table->string('location');
            $table->enum('work_type', WorkType::values());
            $table->enum('experience_level', ExperienceLevel::values());

            $table->date('deadline')->nullable();

            $table->enum('status', JobStatus::values())->default(JobStatus::PENDING->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
