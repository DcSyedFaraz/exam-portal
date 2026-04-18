<?php

namespace App\Console\Commands;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SeedLoadTestStudents extends Command
{
    protected $signature = 'loadtest:seed-students
        {--count=500 : How many load-test students to create}
        {--prefix=LT : Student-number prefix (reserved; must not collide with real classes)}
        {--class=Class One : class_level value stored on the profile}
        {--pin=1234 : 4-digit PIN assigned to every load-test student}
        {--fresh : Delete existing students with the given prefix before seeding}';

    protected $description = 'Bulk-provision predictable student accounts for the k6 load test.';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $prefix = (string) $this->option('prefix');
        $classLevel = (string) $this->option('class');
        $pin = (string) $this->option('pin');

        if ($count < 1) {
            $this->error('--count must be >= 1');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Z]{1,4}$/', $prefix)) {
            $this->error('--prefix must be 1-4 uppercase letters.');

            return self::FAILURE;
        }

        if (! preg_match('/^\d{4}$/', $pin)) {
            $this->error('--pin must be exactly 4 digits.');

            return self::FAILURE;
        }

        $studentRole = Role::where('name', 'student')->first();
        if (! $studentRole) {
            $this->error("The 'student' role is missing. Run `php artisan db:seed --class=RoleSeeder` first.");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info("Removing existing {$prefix}-* students...");
            $profiles = StudentProfile::where('student_number', 'like', $prefix.'-%')->get(['user_id']);
            $userIds = $profiles->pluck('user_id')->all();
            if (! empty($userIds)) {
                DB::table('model_has_roles')->whereIn('model_id', $userIds)->delete();
                StudentProfile::whereIn('user_id', $userIds)->delete();
                User::whereIn('id', $userIds)->forceDelete();
            }
        }

        $pinHash = Hash::make($pin);
        $now = now();
        $created = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($start = 1; $start <= $count; $start += 200) {
            $end = min($start + 199, $count);

            DB::transaction(function () use ($start, $end, $prefix, $classLevel, $pinHash, $now, $studentRole, &$created, $bar) {
                $userRows = [];
                for ($i = $start; $i <= $end; $i++) {
                    $userRows[] = [
                        'name' => "Load Student {$i}",
                        'email' => "loadtest+{$i}@example.test",
                        'password' => $pinHash,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                User::insert($userRows);

                $firstId = User::where('email', 'loadtest+'.$start.'@example.test')->value('id');
                $profileRows = [];
                $roleRows = [];
                for ($i = $start; $i <= $end; $i++) {
                    $userId = $firstId + ($i - $start);
                    $number = sprintf('%s-%06d', $prefix, $i);
                    $profileRows[] = [
                        'user_id' => $userId,
                        'parent_id' => null,
                        'student_number' => $number,
                        'pin' => $pinHash,
                        'class_level' => $classLevel,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $roleRows[] = [
                        'role_id' => $studentRole->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ];
                }
                StudentProfile::insert($profileRows);
                DB::table('model_has_roles')->insert($roleRows);

                $created += ($end - $start + 1);
                $bar->advance($end - $start + 1);
            });
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Created {$created} load-test students.");
        $this->line("First:  {$prefix}-".sprintf('%06d', 1)."  pin={$pin}");
        $this->line("Last:   {$prefix}-".sprintf('%06d', $count)."  pin={$pin}");
        $this->line('Class:  '.$classLevel);

        return self::SUCCESS;
    }
}
