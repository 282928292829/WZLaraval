<?php

use App\Livewire\OrderItemEdit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
    Setting::set('order_edit_enabled', true, 'boolean', 'orders');
    Setting::set('order_edit_click_window_minutes', 10, 'integer', 'orders');
    Setting::set('order_edit_resubmit_window_minutes', 10, 'integer', 'orders');
    Setting::set('default_currency', 'USD', 'string', 'orders');
});

test('component renders read-only when not editing', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(5),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/item',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(OrderItemEdit::class, [
            'order' => $order,
            'canEditItems' => true,
            'orderEditEnabled' => true,
            'clickEditRemaining' => '5 minutes',
            'isOwner' => true,
            'isStaff' => false,
        ]);

    $component->assertSet('editing', false);
    $component->assertSet('items', []);
    $component->assertSee(__('orders.items'));
});

test('startEdit loads items and enters edit mode when within click window', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(5),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/item',
        'qty' => 2,
        'color' => 'red',
        'currency' => 'USD',
        'unit_price' => 10.5,
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(OrderItemEdit::class, [
            'order' => $order->load('items'),
            'canEditItems' => true,
            'orderEditEnabled' => true,
            'clickEditRemaining' => '5 minutes',
            'isOwner' => true,
            'isStaff' => false,
        ])
        ->call('startEdit');

    $component->assertSet('editing', true);
    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://example.com/item');
    expect($component->get('items.0.qty'))->toBe('2');
    expect($component->get('items.0.color'))->toBe('red');
    expect((float) $component->get('items.0.price'))->toBe(10.5);
});

test('startEdit is forbidden when not owner', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->assignRole('customer');
    $other->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(5),
    ]);

    Livewire::actingAs($other)
        ->test(OrderItemEdit::class, [
            'order' => $order,
            'canEditItems' => false,
            'orderEditEnabled' => true,
            'clickEditRemaining' => null,
            'isOwner' => false,
            'isStaff' => false,
        ])
        ->call('startEdit')
        ->assertDispatched('order-toast');
});

test('addProduct and removeProduct work in edit mode', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(5),
        'can_edit_until' => now()->addMinutes(10),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/item',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(OrderItemEdit::class, [
            'order' => $order->load('items'),
            'canEditItems' => true,
            'orderEditEnabled' => true,
            'clickEditRemaining' => '10 minutes',
            'isOwner' => true,
            'isStaff' => false,
        ])
        ->call('startEdit');

    $component->call('addProduct');
    expect($component->get('items'))->toHaveCount(2);

    $component->call('removeProduct', 1);
    expect($component->get('items'))->toHaveCount(1);
});

test('save persists items and redirects to order page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(5),
        'can_edit_until' => now()->addMinutes(10),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/old',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(OrderItemEdit::class, [
            'order' => $order->load('items'),
            'canEditItems' => true,
            'orderEditEnabled' => true,
            'clickEditRemaining' => '10 minutes',
            'isOwner' => true,
            'isStaff' => false,
        ])
        ->call('startEdit')
        ->set('items', [[
            'id' => '',
            'url' => 'https://example.com/new-item',
            'qty' => '3',
            'color' => 'blue',
            'size' => 'M',
            'price' => '15.00',
            'currency' => 'USD',
            'notes' => 'Updated',
        ]])
        ->set('orderNotes', 'Order notes')
        ->call('save');

    $component->assertRedirect(route('orders.show', $order));

    $order->refresh();
    $order->load('items');
    expect($order->notes)->toBe('Order notes');
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->url)->toBe('https://example.com/new-item');
    expect($order->items->first()->qty)->toBe(3);
    expect((float) $order->items->first()->unit_price)->toBe(15.0);
    expect($order->can_edit_until)->toBeNull();
});
