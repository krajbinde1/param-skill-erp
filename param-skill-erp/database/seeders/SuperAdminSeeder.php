<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = env('SUPER_ADMIN_NAME');
        $loginId = env('SUPER_ADMIN_LOGIN_ID');
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (blank($name) || blank($loginId) || blank($email) || blank($password)) {
            throw new RuntimeException(
                'Missing Super Admin credentials. Add these to your .env file:'.PHP_EOL.
                'SUPER_ADMIN_NAME='.PHP_EOL.
                'SUPER_ADMIN_LOGIN_ID='.PHP_EOL.
                'SUPER_ADMIN_EMAIL='.PHP_EOL.
                'SUPER_ADMIN_PASSWORD='
            );
        }

        $role = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');

        $user = User::withTrashed()->updateOrCreate(
            ['login_id' => $loginId],
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'status' => UserStatus::Active,
                'must_change_password' => false,
                'centre_id' => null,
                'employee_id' => null,
                'deleted_at' => null,
            ]
        );

        if (! $user->hasRole($role)) {
            $user->syncRoles([$role]);
        }
    }
}
