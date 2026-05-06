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
        'answer_text',
        'is_correct',
        'marks_awarded',
        'ai_feedback',
        'ai_evaluated',
    ];

    protected function casts(): array
    {
        return [
            'is_correct'       => 'boolean',
            'ai_evaluated'     => 'boolean',
            'match_selections' => 'array',
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
