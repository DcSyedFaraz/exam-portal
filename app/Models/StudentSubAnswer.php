<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentSubAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'sub_item_id',
        'answer_text',
        'marks_awarded',
        'ai_feedback',
        'ai_evaluated',
    ];

    public function subItem()
    {
        return $this->belongsTo(QuestionSubItem::class);
    }

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }
}
