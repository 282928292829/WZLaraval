<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('customer cannot update order status', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->post(route('orders.status.update', $order), [
        'status' => 'processing',
    ]);

    $response->assertForbidden();
});

test('staff can update order status', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $staff->id]);

    $response = $this->actingAs($staff)->post(route('orders.status.update', $order), [
        'status' => 'processing',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    $order->refresh();
    expect($order->status)->toBe('processing');
});

test('customer cannot merge orders', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $orderA = Order::factory()->create(['user_id' => $customer->id]);
    $orderB = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->post(route('orders.merge', $orderA), [
        'merge_with' => $orderB->id,
    ]);

    $response->assertForbidden();
});

test('staff can merge orders', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $orderA = Order::factory()->create(['user_id' => $staff->id]);
    $orderB = Order::factory()->create(['user_id' => $staff->id]);

    $response = $this->actingAs($staff)->post(route('orders.merge', $orderA), [
        'merge_with' => $orderB->id,
    ]);

    $response->assertRedirect(route('orders.show', $orderA));
    $response->assertSessionHas('success');
});

test('customer cannot export orders csv', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();

    $response = $this->actingAs($customer)->get(route('orders.index', ['export' => 'csv']));

    $response->assertOk();
    expect(str_contains($response->headers->get('Content-Type') ?? '', 'text/csv'))->toBeFalse();
});

test('superadmin can export orders csv', function (): void {
    $superadmin = User::where('email', 'superadmin@wasetzon.test')->first();

    $response = $this->actingAs($superadmin)->get(route('orders.all', ['export' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
