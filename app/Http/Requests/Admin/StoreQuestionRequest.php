<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text'          => 'required|string',
            'question_type'          => 'required|in:mcq,true_false,match',
            'marks'                  => 'required|integer|min:1',
            'options'                => 'required|array|min:2',
            'options.*.text'         => 'required|string',
            'options.*.is_correct'   => 'required|in:0,1',
            'options.*.match_pair'   => 'nullable|string',
        ];
    }
}
