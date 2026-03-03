<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('banned user cannot log in', function (): void {
    $user = User::where('email', 'customer@wasetzon.test')->first();
    $user->update(['is_banned' => true, 'banned_at' => now()]);

    $response = $this->post(route('login'), [
        'email' => 'customer@wasetzon.test',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('unbanned user can log in', function (): void {
    $response = $this->post(route('login'), [
        'email' => 'customer@wasetzon.test',
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticated();
});
