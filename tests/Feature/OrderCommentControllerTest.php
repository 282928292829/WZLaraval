<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
});

test('guest cannot store comment on order', function (): void {
    $order = Order::factory()->create();

    $response = $this->post(route('orders.comments.store', $order), [
        'body' => 'Test comment',
    ]);

    $response->assertRedirect(route('login'));
});

test('customer can store comment on own order', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('orders.comments.store', $order), [
        'body' => 'My comment text',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('order_comments', [
        'order_id' => $order->id,
        'user_id' => $user->id,
        'body' => 'My comment text',
        'is_internal' => false,
    ]);
});

test('customer cannot store comment on another customer order', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('customer');

    $other = User::factory()->create();
    $other->assignRole('customer');

    $order = Order::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->post(route('orders.comments.store', $order), [
        'body' => 'Unauthorized comment',
    ]);

    $response->assertForbidden();
});

test('staff can store comment on any order', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($staff)->post(route('orders.comments.store', $order), [
        'body' => 'Staff reply',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $this->assertDatabaseHas('order_comments', [
        'order_id' => $order->id,
        'user_id' => $staff->id,
        'body' => 'Staff reply',
    ]);
});
