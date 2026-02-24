<?php

use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('customer can view own order page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('orders.show', $order->id));

    $response->assertOk();
});

test('non-owner cannot view order page', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('customer');
    $other = User::factory()->create();
    $other->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->get(route('orders.show', $order->id));

    $response->assertForbidden();
});

test('customer can submit payment notify', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('orders.payment-notify', $order->id), [
        'transfer_amount' => '100.50',
        'transfer_bank' => 'Test Bank',
        'transfer_notes' => 'Ref 123',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');
    expect($order->comments()->count())->toBe(1);
});

test('non-owner cannot submit payment notify', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->post(route('orders.payment-notify', $order->id), [
        'transfer_amount' => '100',
        'transfer_bank' => 'Bank',
    ]);

    $response->assertForbidden();
});

test('customer can update shipping address', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    $address = UserAddress::create([
        'user_id' => $user->id,
        'label' => 'Home',
        'recipient_name' => 'Test User',
        'phone' => '+966501234567',
        'country' => 'SA',
        'city' => 'Riyadh',
        'address' => 'Street 1',
    ]);

    $response = $this->actingAs($user)->patch(route('orders.shipping-address.update', $order->id), [
        'shipping_address_id' => $address->id,
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $order->refresh();
    expect($order->shipping_address_id)->toBe($address->id);
    expect($order->timeline()->where('type', 'note')->count())->toBeGreaterThan(0);
});

test('customer can cancel order when pending', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

    $response = $this->actingAs($user)->post(route('orders.cancel', $order->id));

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');
    $order->refresh();
    expect($order->status)->toBe('cancelled');
    expect($order->timeline()->where('status_to', 'cancelled')->count())->toBe(1);
});

test('customer cannot cancel order when not cancellable', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'processing']);

    $response = $this->actingAs($user)->post(route('orders.cancel', $order->id));

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('error');
    $order->refresh();
    expect($order->status)->toBe('processing');
});

test('customer can request customer merge', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $orderA = Order::factory()->create(['user_id' => $user->id]);
    $orderB = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('orders.customer-merge', $orderA->id), [
        'merge_with_order' => $orderB->id,
    ]);

    $response->assertRedirect(route('orders.show', $orderA->id));
    $response->assertSessionHas('success');
    expect($orderA->comments()->count())->toBe(1);
    expect($orderA->timeline()->where('type', 'merge')->count())->toBe(1);
});

test('non-owner cannot request customer merge', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);
    $otherOrder = Order::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($other)->post(route('orders.customer-merge', $order->id), [
        'merge_with_order' => $otherOrder->id,
    ]);

    $response->assertForbidden();
});
