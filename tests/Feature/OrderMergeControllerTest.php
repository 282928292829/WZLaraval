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

test('guest cannot merge orders', function (): void {
    $target = Order::factory()->create();
    $source = Order::factory()->create(['user_id' => $target->user_id]);

    $response = $this->post(route('orders.merge', $target), [
        'merge_with' => $source->id,
    ]);

    $response->assertRedirect(route('login'));
});

test('customer cannot merge orders', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $target = Order::factory()->create(['user_id' => $user->id]);
    $source = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('orders.merge', $target), [
        'merge_with' => $source->id,
    ]);

    $response->assertForbidden();
});

test('staff with merge permission can merge orders', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $customer = User::factory()->create();
    $target = Order::factory()->create(['user_id' => $customer->id]);
    $source = Order::factory()->create(['user_id' => $customer->id]);

    OrderItem::create([
        'order_id' => $source->id,
        'url' => 'https://example.com/item',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($staff)->post(route('orders.merge', $target), [
        'merge_with' => $source->id,
    ]);

    $response->assertRedirect(route('orders.show', $target));
    $response->assertSessionHas('success');

    $source->refresh();
    expect($source->merged_into)->toBe($target->id);

    $movedItem = OrderItem::where('order_id', $target->id)->first();
    expect($movedItem)->not->toBeNull();
});
