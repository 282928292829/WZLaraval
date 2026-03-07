<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
});

test('guest cannot access orders index', function (): void {
    $response = $this->get(route('orders.index'));

    $response->assertRedirect(route('login'));
});

test('customer can access orders index and see own orders', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create(['user_id' => $user->id]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($user)->get(route('orders.index'));

    $response->assertOk();
    $response->assertSee($order->order_number, false);
});

test('customer cannot see other customer orders in index', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $other = User::factory()->create();
    $otherOrder = Order::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->get(route('orders.index'));

    $response->assertOk();
    $response->assertDontSee($otherOrder->order_number, false);
});

test('customer can view own order detail', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('orders.show', $order));

    $response->assertOk();
    $response->assertSee($order->order_number, false);
});

test('customer cannot view another customer order', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)->get(route('orders.show', $order));

    $response->assertForbidden();
});

test('staff can access all orders and see staff view', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($staff)->get(route('orders.all'));

    $response->assertOk();
    $response->assertSee($order->order_number, false);
});

test('customer cannot access staff all-orders route', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $response = $this->actingAs($user)->get(route('orders.all'));

    $response->assertForbidden();
});
