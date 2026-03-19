<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function messages(): array
    {
        return [
            'pin.digits'              => 'PIN must be exactly 4 digits.',
            'pin_confirmation.same'   => 'PIN confirmation does not match.',
        ];
    }
}
