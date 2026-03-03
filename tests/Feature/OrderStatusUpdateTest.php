<?php

use App\Models\Order;
use App\Models\User;

beforeEach(function (): void {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('staff can update order status with optional comment', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->actingAs($staff)->post(route('orders.status.update', $order), [
        'status' => 'processing',
        'comment' => 'Payment received, starting processing.',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $order->refresh();
    expect($order->status)->toBe('processing');
    expect($order->comments()->count())->toBe(1);
    expect($order->comments()->first()->body)->toBe('Payment received, starting processing.');
});

test('staff can update order status without comment', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->actingAs($staff)->post(route('orders.status.update', $order), [
        'status' => 'processing',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $order->refresh();
    expect($order->status)->toBe('processing');
    expect($order->comments()->count())->toBe(0);
});
