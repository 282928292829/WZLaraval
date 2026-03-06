<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeds the "Deleted User" placeholder for orphan comments.
 *
 * Orphan comments (e.g. from deleted users like ulgasan581@gmail.com) are assigned
 * to this user. The user has unsubscribed_all=true so no emails are sent.
 */
class DeletedUserSeeder extends Seeder
{
    public const EMAIL = 'deleted-user@deleted-user.local';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Deleted User',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'unsubscribed_all' => true,
                'email_verified_at' => null,
            ]
        );

        $user->update([
            'name' => 'Deleted User',
            'unsubscribed_all' => true,
        ]);

        // Assign customer role if none.
        if (! $user->roles()->exists()) {
            $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
            $user->assignRole($customer);
        }
    }
}
