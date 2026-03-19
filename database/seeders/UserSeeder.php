<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            ['name' => 'Admin User', 'password' => Hash::make('12345678'), 'is_active' => true]
        );
        $admin->assignRole('admin');

        // Parents
        $parent1 = User::firstOrCreate(
            ['email' => 'parent@gmail.com'],
            ['name' => 'Alice Parent', 'password' => Hash::make('12345678'), 'is_active' => true]
        );
        $parent1->assignRole('parent');

        $parent2 = User::firstOrCreate(
            ['email' => 'parent2@gmail.com'],
            ['name' => 'Bob Parent', 'password' => Hash::make('12345678'), 'is_active' => true]
        );
        $parent2->assignRole('parent');

        // Students
        $student1 = User::firstOrCreate(
            ['email' => 'student1@gmail.com'],
            ['name' => 'Charlie Student', 'password' => Hash::make('12345678'), 'is_active' => true]
        );
        $student1->assignRole('student');

        if (!StudentProfile::where('user_id', $student1->id)->exists()) {
            StudentProfile::create([
                'user_id'        => $student1->id,
                'parent_id'      => $parent1->id,
                'student_number' => $this->generateStudentNumber(),
                'pin'            => Hash::make('1234'),
            ]);
        }

        $student2 = User::firstOrCreate(
            ['email' => 'student2@gmail.com'],
            ['name' => 'Diana Student', 'password' => Hash::make('12345678'), 'is_active' => true]
        );
        $student2->assignRole('student');

        if (!StudentProfile::where('user_id', $student2->id)->exists()) {
            StudentProfile::create([
                'user_id'        => $student2->id,
                'parent_id'      => $parent1->id,
                'student_number' => $this->generateStudentNumber(),
                'pin'            => Hash::make('1234'),
            ]);
        }
    }

    protected function generateStudentNumber(): string
    {
        do {
            $date   = now()->format('Ymd');
            $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $number = "STU-{$date}-{$random}";
        } while (StudentProfile::where('student_number', $number)->exists());

        return $number;
    }
}
