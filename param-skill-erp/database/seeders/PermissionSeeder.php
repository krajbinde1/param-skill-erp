<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    protected array $centrePermissions = [
        'centres.view_any',
        'centres.view',
        'centres.create',
        'centres.update',
        'centres.activate',
        'centres.deactivate',
        'centres.delete',
        'centres.restore',
        'centres.reset_login',
    ];

    /**
     * @var list<string>
     */
    protected array $employeePermissions = [
        'employees.view_any',
        'employees.view',
        'employees.create',
        'employees.update',
        'employees.activate',
        'employees.deactivate',
        'employees.delete',
        'employees.restore',
        'employees.reset_login',
        'employees.download_documents',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([...$this->centrePermissions, ...$this->employeePermissions] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $centreManager = Role::findOrCreate(RoleName::CentreManager->value, 'web');

        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $admin->syncPermissions([...$this->centrePermissions, ...$this->employeePermissions]);

        $centreManager->syncPermissions([
            'employees.view_any',
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.activate',
            'employees.deactivate',
            'employees.reset_login',
            'employees.download_documents',
            'centres.view',
        ]);
    }
}
