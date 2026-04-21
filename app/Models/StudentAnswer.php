<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option_id',
        'match_selections',
        'is_correct',
        'marks_awarded',
    ];

    protected function casts(): array
    {
        return [
            'is_correct'       => 'boolean',
            'match_selections' => 'array',  // { "optionId": "submittedValue", ... }
        ];
    }

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(Option::class, 'selected_option_id');
    }
}
