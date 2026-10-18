<?php

namespace App\Http\Requests;

use App\Models\Disease;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Disease::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:diseases,name'],
            'description' => ['required', 'string'],
            'prevention_tips' => ['required', 'string'],
            'causes' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'string'],
            'history' => ['nullable', 'string'],
            'sources' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
