<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Material::class, $this->route('course')]) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['file', 'link'])],
            'external_url' => ['nullable', 'required_if:type,link', 'url', 'max:2048'],
            'file' => ['exclude_unless:type,file', 'required', 'file', 'mimes:pdf,pptx,docx', 'max:20480'],
        ];
    }
}