<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('admin can access orders in Filament', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();

    $response = $this->actingAs($admin)->get(route('filament.admin.resources.orders.index'));

    $response->assertOk();
});

test('admin can access users in Filament', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();

    $response = $this->actingAs($admin)->get(route('filament.admin.resources.users.index'));

    $response->assertOk();
});

test('admin can access settings page in Filament', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();

    $response = $this->actingAs($admin)->get(route('filament.admin.pages.settings-page'));

    $response->assertOk();
});

test('admin cannot access roles in Filament', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();

    $response = $this->actingAs($admin)->get(route('filament.admin.resources.roles.index'));

    $response->assertForbidden();
});

test('superadmin can access roles in Filament', function (): void {
    $superadmin = User::where('email', 'superadmin@wasetzon.test')->first();

    $response = $this->actingAs($superadmin)->get(route('filament.admin.resources.roles.index'));

    $response->assertOk();
});

test('staff cannot access Filament panel', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();

    $response = $this->actingAs($staff)->get(route('filament.admin.pages.settings-page'));

    $response->assertForbidden();
});
