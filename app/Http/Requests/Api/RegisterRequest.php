<?php

namespace App\Http\Requests\Api;

use App\Enums\MunicipalityEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'barangay' => ['required', 'string', 'max:255'],
            'municipality' => ['required', Rule::enum(MunicipalityEnum::class)],
            'contact_number' => ['required', 'string', 'max:20'],
            'farm_lat' => ['required', 'numeric', 'between:14.3,14.95'],
            'farm_long' => ['required', 'numeric', 'between:120.2,120.7'],
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
