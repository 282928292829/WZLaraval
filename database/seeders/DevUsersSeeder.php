<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Optional: Seeds development users after migration.
 *
 * Run via: php artisan migrate:all --seed-dev
 * Or: php artisan db:seed --class=DevUsersSeeder
 */
class DevUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'customer' => 'customer@wasetzon.test',
            'staff' => 'staff@wasetzon.test',
            'superadmin' => 'superadmin@wasetzon.test',
        ];

        foreach ($roles as $roleName => $email) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => ucfirst($roleName).' User',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$role]);
        }

        $this->command?->info('Dev users seeded.');
    }
}
