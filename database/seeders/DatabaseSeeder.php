<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permissions:sync');

        $hr = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hr->syncPermissions([
            'employees.view', 'employees.create', 'employees.update', 'employees.export',
            'leaves.view', 'leaves.create', 'leaves.update', 'leaves.approve',
            'documents.view', 'documents.create', 'documents.update',
            'users.view',
        ]);

        $payroll = Role::firstOrCreate(['name' => 'payroll', 'guard_name' => 'web']);
        $payroll->syncPermissions([
            'employees.view', 'payroll.view', 'payroll.run', 'payroll.export',
        ]);

        $manager = Role::firstOrCreate(['name' => 'project-manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'employees.view', 'leaves.view', 'leaves.approve', 'assets.view', 'fleet.view',
        ]);

        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web'])
            ->syncPermissions(['leaves.view', 'leaves.create']);

        User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
        ])->assignRole('admin');

        User::factory()->create([
            'name' => 'HR Officer',
            'email' => 'hr@example.com',
        ])->assignRole('hr');

        User::factory()->count(8)->create()->each->assignRole('employee');
    }
}
