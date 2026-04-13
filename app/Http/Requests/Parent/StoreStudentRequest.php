<?php

namespace App\Http\Requests\Parent;

use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:100',
            'pin'              => 'required|digits:4',
            'pin_confirmation' => 'required|same:pin',
            'class_level'      => ['required', Rule::in(StudentProfile::CLASS_LEVELS)],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.digits'              => 'PIN must be exactly 4 digits.',
            'pin_confirmation.same'   => 'PIN confirmation does not match.',
            'class_level.required'    => 'Please select a class level.',
            'class_level.in'          => 'The selected class level is not valid.',
        ];
    }
}
