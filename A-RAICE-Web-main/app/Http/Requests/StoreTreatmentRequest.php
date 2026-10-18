<?php

namespace App\Http\Requests;

use App\Enums\TreatmentTypeEnum;
use App\Models\Treatment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Treatment::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', Rule::enum(TreatmentTypeEnum::class)],
        ];
    }
}
