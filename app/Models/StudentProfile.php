<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    public const CLASS_LEVELS = [
        'Class One',
        'Class Two',
        'Class Three',
        'Class Four',
        'Class Five',
        'Class Six',
        'Class Seven',
        'Form I',
        'Form II',
        'Form III',
        'Form IV',
        'Form V',
        'Form VI',
    ];

    protected $fillable = [
        'user_id',
        'parent_id',
        'student_number',
        'pin',
        'class_level',
    ];

    protected $hidden = ['pin'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
