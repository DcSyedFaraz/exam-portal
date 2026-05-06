<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionSubItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'label',
        'sub_question_text',
        'correct_answer',
        'marks',
        'order',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
