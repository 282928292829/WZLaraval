<?php

use App\Livewire\NewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

    Setting::set('orders_per_hour_customer', '100', 'integer', 'orders');
    Setting::set('orders_per_hour_admin', '100', 'integer', 'orders');
    Setting::set('max_orders_per_day', '0', 'integer', 'orders');
    Setting::set('max_products_per_order', '30', 'integer', 'orders');
    Setting::set('default_currency', 'USD', 'string', 'orders');
});

// ─── duplicate_from ───────────────────────────────────────────────────────────

test('duplicate_from pre-fills items and notes from existing order', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create(['user_id' => $user->id, 'notes' => 'Test notes']);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://amazon.com/item1',
        'qty' => 2,
        'color' => 'red',
        'size' => 'L',
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class, ['duplicate_from' => $order->id]);

    expect($component->get('orderNotes'))->toBe('Test notes');
    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://amazon.com/item1');
    expect($component->get('items.0.qty'))->toBe('2');
    expect($component->get('items.0.color'))->toBe('red');
    expect($component->get('items.0.size'))->toBe('L');
    expect($component->get('duplicateFrom'))->toBe($order->id);
});

test('duplicate_from is ignored if user does not own the order', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $other->id]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class, ['duplicate_from' => $order->id]);

    expect($component->get('items'))->toBeEmpty();
    expect($component->get('duplicateFrom'))->toBeNull();
});

test('staff can duplicate any order', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'notes' => 'Staff dupe']);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/prod',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($staff)
        ->test(NewOrder::class, ['duplicate_from' => $order->id]);

    expect($component->get('orderNotes'))->toBe('Staff dupe');
    expect($component->get('items'))->toHaveCount(1);
});

// ─── product_url ─────────────────────────────────────────────────────────────

test('product_url pre-fills first item url', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class, ['product_url' => 'https://amazon.com/s/ref=123']);

    expect($component->get('productUrl'))->toBe('https://amazon.com/s/ref=123');
    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://amazon.com/s/ref=123');
});

// ─── success screen ──────────────────────────────────────────────────────────

test('success page is shown for user first order — redirects to orders.success', function (): void {
    Setting::set('order_success_screen_enabled', true, 'boolean', 'orders');
    Setting::set('order_success_screen_threshold', 3, 'integer', 'orders');

    $user = User::factory()->create();
    $user->assignRole('customer');

    // No prior orders — this will be their 1st
    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('items', [[
            'url' => 'https://example.com/product',
            'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => '',
        ]])
        ->call('submitOrder');

    $order = Order::where('user_id', $user->id)->orderByDesc('id')->first();
    expect($order)->not->toBeNull();
    $component->assertRedirect(route('orders.success', $order));
});

test('success page is NOT shown for 4th order — redirects to orders.show', function (): void {
    Setting::set('order_success_screen_enabled', true, 'boolean', 'orders');
    Setting::set('order_success_screen_threshold', 3, 'integer', 'orders');

    $user = User::factory()->create();
    $user->assignRole('customer');

    // 3 existing orders → this will be their 4th
    Order::factory()->count(3)->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('items', [[
            'url' => 'https://example.com/product',
            'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => '',
        ]])
        ->call('submitOrder');

    $order = Order::where('user_id', $user->id)->orderByDesc('id')->first();
    expect($order)->not->toBeNull();
    $component->assertRedirect(route('orders.show', $order));
});

test('when success screen disabled, all orders redirect to orders.show', function (): void {
    Setting::set('order_success_screen_enabled', false, 'boolean', 'orders');
    Setting::set('order_success_screen_threshold', 3, 'integer', 'orders');

    $user = User::factory()->create();
    $user->assignRole('customer');

    // First order — would normally get success page, but disabled
    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('items', [[
            'url' => 'https://example.com/product',
            'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => '',
        ]])
        ->call('submitOrder');

    $order = Order::where('user_id', $user->id)->orderByDesc('id')->first();
    expect($order)->not->toBeNull();
    $component->assertRedirect(route('orders.show', $order));
});
