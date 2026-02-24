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

    $response = $this->actingAs($customer)->post(route('orders.status.update', $order->id), [
        'status' => 'processing',
    ]);

    $response->assertForbidden();
});

test('editor can update order status', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.status.update', $order->id), [
        'status' => 'processing',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');
    $order->refresh();
    expect($order->status)->toBe('processing');
});

test('customer cannot merge orders', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $orderA = Order::factory()->create(['user_id' => $customer->id]);
    $orderB = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->post(route('orders.merge', $orderA->id), [
        'merge_with' => $orderB->id,
    ]);

    $response->assertForbidden();
});

test('editor can merge orders', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $orderA = Order::factory()->create(['user_id' => $editor->id]);
    $orderB = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.merge', $orderA->id), [
        'merge_with' => $orderB->id,
    ]);

    $response->assertRedirect(route('orders.show', $orderA->id));
    $response->assertSessionHas('success');
});

test('customer cannot export orders csv', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();

    $response = $this->actingAs($customer)->get(route('orders.index', ['export' => 'csv']));

    $response->assertOk();
    expect(str_contains($response->headers->get('Content-Type') ?? '', 'text/csv'))->toBeFalse();
});

test('editor can export orders csv', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();

    $response = $this->actingAs($editor)->get(route('orders.index', ['export' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
