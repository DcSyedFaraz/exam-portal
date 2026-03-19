<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage exams',
            'manage students',
            'view results',
            'take exams',
            'manage own students',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $adminRole   = Role::firstOrCreate(['name' => 'admin']);
        $parentRole  = Role::firstOrCreate(['name' => 'parent']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);

        $adminRole->syncPermissions(['manage exams', 'manage students', 'view results']);
        $parentRole->syncPermissions(['manage own students', 'view results']);
        $studentRole->syncPermissions(['take exams', 'view results']);
    }
}
