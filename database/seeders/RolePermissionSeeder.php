<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage companies',
            'manage users',
            'manage employees',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'manage contracts',
            'generate contracts',
            'manage leave requests',
            'approve leave requests',
            'manage payroll',
            'generate payslips',
            'validate payslips',
            'send payslips',
            'view own documents',
            'download own payslips',
            'manage subscription',
            'manage billing',
            'manage demo requests',
            'manage support tickets',
            'reply support tickets',
            'view audit logs',
            'manage settings',
            'view own support tickets',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('Super Admin')->givePermissionTo($permissions);
        Role::findOrCreate('Company Owner')->givePermissionTo($permissions);
        Role::findOrCreate('RH Manager')->givePermissionTo(['manage employees', 'view employees', 'manage contracts', 'generate contracts', 'manage leave requests', 'approve leave requests', 'manage support tickets', 'reply support tickets']);
        Role::findOrCreate('Payroll Manager')->givePermissionTo(['view employees', 'manage payroll', 'generate payslips', 'validate payslips', 'send payslips']);
        Role::findOrCreate('Accountant')->givePermissionTo(['view employees', 'manage payroll', 'generate payslips']);
        Role::findOrCreate('Employee')->givePermissionTo(['view own documents', 'download own payslips', 'view own support tickets']);
        Role::findOrCreate('Cabinet Owner')->givePermissionTo(['manage companies', 'view employees', 'manage payroll', 'manage billing', 'manage support tickets', 'reply support tickets']);
        Role::findOrCreate('Cabinet Staff')->givePermissionTo(['view employees', 'manage payroll']);
    }
}
