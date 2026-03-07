<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeAll(function (): void {
    TestCase::$skipRoleAndPermissionSeeder = true;
});

afterAll(function (): void {
    TestCase::$skipRoleAndPermissionSeeder = false;
});

test('RoleAndPermissionSeeder skips test users when static flag is set', function (): void {
    expect(User::count())->toBe(0, 'DB should be empty before seeder');

    RoleAndPermissionSeeder::$skipTestUsers = true;

    try {
        (new RoleAndPermissionSeeder)->run();

        $testEmails = [
            'guest@wasetzon.test',
            'customer@wasetzon.test',
            'staff@wasetzon.test',
            'admin@wasetzon.test',
            'superadmin@wasetzon.test',
        ];

        foreach ($testEmails as $email) {
            expect(User::where('email', $email)->exists())->toBeFalse();
        }
    } finally {
        RoleAndPermissionSeeder::$skipTestUsers = false;
    }
});

test('RoleAndPermissionSeeder creates test users in testing environment', function (): void {
    expect(app()->environment())->toBe('testing');

    (new RoleAndPermissionSeeder)->run();

    expect(User::where('email', 'admin@wasetzon.test')->exists())->toBeTrue();
});
