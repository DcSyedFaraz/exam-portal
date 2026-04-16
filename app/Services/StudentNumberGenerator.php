<?php

namespace App\Services;

use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

class StudentNumberGenerator
{
    public static function next(string $classLevel): string
    {
        $prefix = StudentProfile::prefixForClassLevel($classLevel);

        return DB::transaction(function () use ($prefix) {
            $latest = DB::table('student_profiles')
                ->where('student_number', 'like', $prefix.'-%')
                ->lockForUpdate()
                ->orderByDesc('student_number')
                ->value('student_number');

            $next = 1;
            if (is_string($latest) && preg_match('/^'.preg_quote($prefix, '/').'-(\d{6})$/', $latest, $m)) {
                $next = ((int) $m[1]) + 1;
            }

            return sprintf('%s-%06d', $prefix, $next);
        });
    }
}
