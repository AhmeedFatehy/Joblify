<?php

namespace App\Http\Requests;

use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkType;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateJobRequest extends BaseApiRequest
{
    private static function WORK_TYPES(): string
    {
        return implode(',', WorkType::values());
    }

    private static function EXPERIENCE_LEVELS(): string
    {
        return implode(',', ExperienceLevel::values());
    }

    private static function JOB_STATUS(): string
    {
        return implode(',', JobStatus::values());
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'location' => 'nullable|string',
            'work_type' => 'nullable|in:'.self::WORK_TYPES(),
            'experience_level' => 'nullable|in:'.self::EXPERIENCE_LEVELS(),
            'deadline' => 'nullable|date|after_or_equal:today',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'skills' => 'nullable|array',
            'skills.*' => 'integer|exists:skills,id',
            'status' => 'nullable|in:'.self::JOB_STATUS(),
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Please enter a valid job title. Max length is 255 characters.',
            'salary_min.numeric' => 'Please enter a valid minimum salary.',
            'salary_max.numeric' => 'Please enter a valid maximum salary.',
            'work_type.in' => 'The work type must be one of: '.self::WORK_TYPES(),
            'experience_level.in' => 'The experience level must be one of: '.self::EXPERIENCE_LEVELS(),
            'deadline.date' => 'The deadline must be a valid date.',
            'deadline.after_or_equal' => 'The deadline must be a date after or equal to today.',
            'categories.*.exists' => 'The selected category is invalid.',
            'skills.*.exists' => 'The selected skill is invalid.',
            'status.in' => 'The job status must be one of: '.self::JOB_STATUS(),
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Job Title',
            'description' => 'Job Description',
            'requirements' => 'Job Requirements',
            'benefits' => 'Job Benefits',
            'salary_min' => 'Minimum Salary',
            'salary_max' => 'Maximum Salary',
            'location' => 'Job Location',
            'work_type' => 'Work Type',
            'experience_level' => 'Experience Level',
            'deadline' => 'Deadline',
            'categories' => 'Categories',
            'skills' => 'Skills',
            'status' => 'Job Status',
        ];
    }
}
