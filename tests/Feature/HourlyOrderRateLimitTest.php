<?php

use App\Livewire\NewOrder;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

    Setting::set('orders_per_hour_customer', '2', 'integer', 'orders');
    Setting::set('orders_per_hour_admin', '5', 'integer', 'orders');
    Setting::set('max_orders_per_day', '0', 'integer', 'orders');
    Setting::set('max_products_per_order', '30', 'integer', 'orders');
});

test('customer is blocked after hourly limit is reached', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Order::factory()->count(2)->create([
        'user_id' => $user->id,
        'created_at' => now()->subMinutes(30),
    ]);

    Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->call('submitOrder')
        ->assertDispatched('notify', type: 'error');
});

test('orders older than 1 hour do not count toward hourly limit', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    // All older than 1 hour — should not count
    Order::factory()->count(5)->create([
        'user_id' => $user->id,
        'created_at' => now()->subHours(2),
    ]);

    // With 0 recent orders (under limit), the rate-limit error should NOT be dispatched.
    // submitOrder will proceed to validation, which will fail on empty items.
    Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->call('submitOrder')
        ->assertNotDispatched('notify', type: 'error', message: fn ($msg) => str_contains((string) $msg, '2'));
});

test('staff use admin hourly limit and are not blocked at customer threshold', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    // 3 orders in past hour — over customer limit (2) but under admin limit (5)
    Order::factory()->count(3)->create([
        'user_id' => $editor->id,
        'created_at' => now()->subMinutes(20),
    ]);

    // Should NOT be rate-limited yet (admin limit is 5)
    Livewire::actingAs($editor)
        ->test(NewOrder::class)
        ->call('submitOrder')
        ->assertNotDispatched('notify', type: 'error', message: fn ($msg) => str_contains((string) $msg, '5'));
});

test('staff are blocked after admin hourly limit is reached', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    Order::factory()->count(5)->create([
        'user_id' => $editor->id,
        'created_at' => now()->subMinutes(20),
    ]);

    Livewire::actingAs($editor)
        ->test(NewOrder::class)
        ->call('submitOrder')
        ->assertDispatched('notify', type: 'error');
});
