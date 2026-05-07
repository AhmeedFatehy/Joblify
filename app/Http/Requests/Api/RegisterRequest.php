<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends BaseApiRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([UserRole::CANDIDATE->value, UserRole::EMPLOYER->value])],
            'phone' => ['nullable', 'string', 'max:20'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
