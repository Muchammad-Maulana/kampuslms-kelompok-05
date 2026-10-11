<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageEnrollment', $this->route('course')) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['sometimes', 'array'],
            'student_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'mahasiswa')),
            ],
        ];
    }
}