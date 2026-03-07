<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

test('login page is accessible to guests', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('user can log in with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('customer');

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function (): void {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('register page is accessible to guests', function (): void {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('user can register', function (): void {
    $response = $this->post(route('register'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('forgot password page is accessible', function (): void {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});
