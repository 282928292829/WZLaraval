<?php

use App\Livewire\NewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

    Setting::set('order_edit_enabled', true, 'boolean', 'orders');
    Setting::set('order_edit_click_window_minutes', 10, 'integer', 'orders');
    Setting::set('order_edit_resubmit_window_minutes', 10, 'integer', 'orders');
    Setting::set('max_products_per_order', 30, 'integer', 'orders');
    Setting::set('default_currency', 'USD', 'string', 'orders');
    Setting::set('orders_per_hour_customer', 100, 'integer', 'orders');
    Setting::set('max_orders_per_day', 0, 'integer', 'orders');
});

test('edit param pre-fills items and sets can_edit_until when within click window', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => '1001',
        'notes' => 'Original notes',
        'is_paid' => false,
        'can_edit_until' => null,
        'created_at' => now(),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://amazon.com/edit-item',
        'qty' => 3,
        'color' => 'blue',
        'size' => 'M',
        'currency' => 'USD',
        'unit_price' => 25.99,
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class, ['edit' => $order->id]);

    expect($component->get('editingOrderId'))->toBe($order->id);
    expect($component->get('editingOrderNumber'))->toBe('1001');
    expect($component->get('orderNotes'))->toBe('Original notes');
    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://amazon.com/edit-item');
    expect($component->get('items.0.qty'))->toBe('3');
    expect($component->get('items.0.color'))->toBe('blue');
    expect($component->get('items.0.size'))->toBe('M');
    expect($component->get('items.0.price'))->toBe('25.99');

    $order->refresh();
    expect($order->can_edit_until)->not->toBeNull();
    expect($order->can_edit_until->isFuture())->toBeTrue();
});

test('edit param redirects when order edit disabled', function (): void {
    Setting::set('order_edit_enabled', false, 'boolean', 'orders');

    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'is_paid' => false, 'created_at' => now()]);

    Livewire::actingAs($user)
        ->test(NewOrder::class, ['edit' => $order->id])
        ->assertRedirect(route('orders.show', $order->id));
});

test('edit param redirects when order is paid', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => true,
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(NewOrder::class, ['edit' => $order->id])
        ->assertRedirect(route('orders.show', $order->id));
});

test('edit param redirects when not owner', function (): void {
    $owner = User::factory()->create();
    $owner->assignRole('customer');
    $other = User::factory()->create();
    $other->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $owner->id, 'is_paid' => false, 'created_at' => now()]);

    Livewire::actingAs($other)
        ->test(NewOrder::class, ['edit' => $order->id])
        ->assertRedirect(route('orders.show', $order->id));
});

test('edit param redirects when click window expired', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(15),
    ]);

    Livewire::actingAs($user)
        ->test(NewOrder::class, ['edit' => $order->id])
        ->assertRedirect(route('orders.show', $order->id));
});

test('submitOrder updates existing order when editing', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => '2002',
        'notes' => 'Before edit',
        'is_paid' => false,
        'can_edit_until' => now()->addMinutes(10),
        'subtotal' => 100,
        'total_amount' => 150,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://old.com/item',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('editingOrderId', $order->id)
        ->set('editingOrderNumber', $order->order_number)
        ->set('orderNotes', 'Updated notes')
        ->set('items', [[
            'url' => 'https://new.com/product',
            'qty' => '2',
            'color' => 'green',
            'size' => 'XL',
            'price' => '50',
            'currency' => 'USD',
            'notes' => 'New item',
        ]])
        ->call('submitOrder');

    $component->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect($order->notes)->toBe('Updated notes');
    expect($order->can_edit_until)->toBeNull();
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->url)->toBe('https://new.com/product');
    expect($order->items->first()->qty)->toBe(2);
    expect($order->items->first()->color)->toBe('green');
    expect($order->timeline()->where('body', __('orders.timeline_items_edited'))->count())->toBe(1);
});

test('submitOrder edit rejects when resubmit window expired', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'can_edit_until' => now()->subMinutes(1),
    ]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('editingOrderId', $order->id)
        ->set('items', [['url' => 'https://example.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => '']])
        ->call('submitOrder');

    expect($component->get('editingOrderId'))->toBe($order->id);
    $order->refresh();
    expect($order->can_edit_until)->not->toBeNull();
});

test('order show page displays edit link when within click window', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('orders.show', $order->id));

    $response->assertOk();
    $response->assertSee(__('orders.edit_items'));
    $response->assertSee('edit='.$order->id);
});

test('order show page hides edit link when order edit disabled', function (): void {
    Setting::set('order_edit_enabled', false, 'boolean', 'orders');

    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $user->id, 'is_paid' => false, 'created_at' => now()]);

    $response = $this->actingAs($user)->get(route('orders.show', $order->id));

    $response->assertOk();
    $response->assertDontSee(__('orders.edit_items'));
});

test('order show page hides edit link when click window expired', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'created_at' => now()->subMinutes(15),
    ]);

    $response = $this->actingAs($user)->get(route('orders.show', $order->id));

    $response->assertOk();
    $response->assertDontSee(__('orders.edit_items'));
});

test('submitOrder edit rejects empty items', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'is_paid' => false,
        'can_edit_until' => now()->addMinutes(10),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://original.com/item',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('editingOrderId', $order->id)
        ->set('items', [['url' => '', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => '']])
        ->call('submitOrder');

    expect($component->get('editingOrderId'))->toBe($order->id);
    $order->refresh();
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->url)->toBe('https://original.com/item');
});
