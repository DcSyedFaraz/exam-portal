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

    public const CLASS_LEVEL_PREFIXES = [
        'Class One' => 'P1',
        'Class Two' => 'P2',
        'Class Three' => 'P3',
        'Class Four' => 'P4',
        'Class Five' => 'P5',
        'Class Six' => 'P6',
        'Class Seven' => 'P7',
        'Form I' => 'F1',
        'Form II' => 'F2',
        'Form III' => 'F3',
        'Form IV' => 'F4',
        'Form V' => 'F5',
        'Form VI' => 'F6',
    ];

    public static function prefixForClassLevel(?string $classLevel): string
    {
        if (! $classLevel) {
            return 'GN';
        }

        return self::CLASS_LEVEL_PREFIXES[$classLevel] ?? 'GN';
    }

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
