<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question_text',
        'question_type',
        'marks',
        'order',
        'image_path',
        'correct_answer_text',
        'word_bank_items',
        'ai_max_marks',
    ];

    protected function casts(): array
    {
        return [
            'word_bank_items' => 'array',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function correctOption()
    {
        return $this->hasOne(Option::class)->where('is_correct', true);
    }

    public function subItems()
    {
        return $this->hasMany(QuestionSubItem::class)->orderBy('order');
    }

    public function isAiGraded(): bool
    {
        return in_array($this->question_type, ['fill_blank', 'ai_evaluated', 'picture']);
    }
}
