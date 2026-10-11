<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('material')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'required', 'in:file,link'],
            'external_url' => ['sometimes', 'nullable', 'required_if:type,link', 'url', 'max:2048'],
            'original_name' => ['sometimes', 'nullable', 'required_if:type,file', 'string', 'max:255'],
            'file_size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mime_type' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}