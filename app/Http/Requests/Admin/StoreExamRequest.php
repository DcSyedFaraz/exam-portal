<?php

namespace App\Http\Requests\Admin;

use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'passing_marks'    => 'required|integer|min:1',
            'total_marks'      => 'required|integer|min:1|gte:passing_marks',
            'class_level'      => ['nullable', Rule::in(StudentProfile::CLASS_LEVELS)],
        ];
    }
}
