<?php

namespace App\Http\Requests;

use App\Enums\TreatmentTypeEnum;
use App\Models\Treatment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $treatment = $this->route('treatment');

        return $treatment instanceof Treatment
            && ($this->user()?->can('update', $treatment) ?? false);
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
