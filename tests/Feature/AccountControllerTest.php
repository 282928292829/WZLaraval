<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
});

test('guest cannot access account page', function (): void {
    $response = $this->get(route('account.index'));

    $response->assertRedirect(route('login'));
});

test('customer can access account page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $response = $this->actingAs($user)->get(route('account.index'));

    $response->assertOk();
});

test('customer can update profile', function (): void {
    $user = User::factory()->create(['name' => 'Old Name']);
    $user->assignRole('customer');

    $response = $this->actingAs($user)->patch(route('account.profile.update'), [
        'name' => 'New Name',
        'email' => $user->email,
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->name)->toBe('New Name');
});
