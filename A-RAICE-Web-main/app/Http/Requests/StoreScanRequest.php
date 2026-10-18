<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('gps_lng') && ! $this->has('gps_long')) {
            $this->merge(['gps_long' => $this->input('gps_lng')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'image' => ['required', 'image', 'max:5120'],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_long' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        if ($this->user()->role === UserRole::Farmer) {
            $rules['farmer_id'] = ['prohibited'];
        } else {
            $rules['farmer_id'] = ['required', 'integer', 'exists:farmer_profiles,id'];
        }

        return $rules;
    }
}
