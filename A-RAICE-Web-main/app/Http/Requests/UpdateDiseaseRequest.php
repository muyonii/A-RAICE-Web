<?php

namespace App\Http\Requests;

use App\Models\Disease;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $disease = $this->route('disease');

        return $disease instanceof Disease
            && ($this->user()?->can('update', $disease) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $disease = $this->route('disease');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('diseases', 'name')->ignore($disease instanceof Disease ? $disease->id : null),
            ],
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
