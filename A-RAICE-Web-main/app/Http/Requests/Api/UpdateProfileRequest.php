<?php

namespace App\Http\Requests\Api;

use App\Enums\MunicipalityEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'barangay' => ['sometimes', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'string', 'max:20'],
            'municipality' => ['sometimes', Rule::enum(MunicipalityEnum::class)],
            'farm_lat' => ['sometimes', 'numeric', 'between:14.3,14.95'],
            'farm_long' => ['sometimes', 'numeric', 'between:120.2,120.7'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'farm_lat.between' => 'Latitude must be within Bataan (between 14.3 and 14.95).',
            'farm_long.between' => 'Longitude must be within Bataan (between 120.2 and 120.7).',
        ];
    }
}
