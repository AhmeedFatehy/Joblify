<?php

namespace App\Models;

use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkType;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'benefits',
        'salary_min',
        'salary_max',
        'location',
        'work_type',
        'experience_level',
        'deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'deadline' => 'date',
            'work_type' => WorkType::class,
            'experience_level' => ExperienceLevel::class,
            'status' => JobStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
