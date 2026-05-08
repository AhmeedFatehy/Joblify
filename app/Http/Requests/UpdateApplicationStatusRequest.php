<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends BaseApiRequest
{
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
            'status' => ['required', 'string', Rule::in([
                ApplicationStatus::ACCEPTED->value,
                ApplicationStatus::REJECTED->value,
            ])],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:2000',
                'prohibited_unless:status,'.ApplicationStatus::REJECTED->value,
            ],
        ];
    }
}
