<?php

namespace App\Http\Requests;

use App\Enums\ExperienceLevel;
use App\Enums\WorkType;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreJobRequest extends BaseApiRequest
{
    private static function WORK_TYPES(): string
    {
        return implode(',', WorkType::values());
    }

    private static function EXPERIENCE_LEVELS(): string
    {
        return implode(',', ExperienceLevel::values());
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'location' => 'required|string',
            'work_type' => 'required|in:'.self::WORK_TYPES(),
            'experience_level' => 'required|in:'.self::EXPERIENCE_LEVELS(),
            'deadline' => 'nullable|date|after_or_equal:today',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'skills' => 'nullable|array',
            'skills.*' => 'integer|exists:skills,id',
            'company_id' => 'required|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a job title.',
            'title.max' => 'Please enter a valid job title.',
            'description.required' => 'Please enter a job description.',
            'salary_min.numeric' => 'Please enter a valid minimum salary.',
            'salary_max.numeric' => 'Please enter a valid maximum salary.',
            'location.required' => 'Please enter a job location.',
            'work_type.required' => 'Please select a work type.',
            'work_type.in' => 'The work type must be one of: '.self::WORK_TYPES(),
            'experience_level.required' => 'Please select an experience level.',
            'experience_level.in' => 'The experience level must be one of: '.self::EXPERIENCE_LEVELS(),
            'deadline.date' => 'The deadline must be a valid date.',
            'deadline.after_or_equal' => 'The deadline must be a date after or equal to today.',
            'categories.*.exists' => 'The selected category is invalid.',
            'skills.*.exists' => 'The selected skill is invalid.',
            'company_id.required' => 'Please select a company.',
            'company_id.exists' => 'The selected company is invalid.',
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
            'company_id' => 'Company',
        ];
    }
}
