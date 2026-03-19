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
    Setting::set('max_products_per_order', '30', 'integer', 'orders');
    Setting::set('default_currency', 'USD', 'string', 'orders');
    Setting::set('order_new_layout', 'cart', 'string', 'orders');
});

test('cart layout renders new-order-cart view', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->assertSeeLivewire(NewOrder::class);
    $component->assertViewIs('livewire.new-order-cart');
});

test('addToCart accepts empty URL and all empty fields', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('currentItem', [
        'url' => '',
        'qty' => '1',
        'color' => '',
        'size' => '',
        'price' => '',
        'currency' => 'USD',
        'notes' => '',
    ]);
    $component->call('addToCart');

    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('');
});

test('addToCart adds item when URL provided', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('currentItem', [
        'url' => 'https://amazon.com/dp/B0TEST',
        'qty' => '2',
        'color' => 'blue',
        'size' => 'M',
        'price' => '',
        'currency' => 'USD',
        'notes' => 'Test note',
    ]);
    $component->call('addToCart');

    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://amazon.com/dp/B0TEST');
    expect($component->get('items.0.qty'))->toBe('2');
    expect($component->get('items.0.color'))->toBe('blue');
    expect($component->get('items.0.size'))->toBe('M');
    expect($component->get('items.0.notes'))->toBe('Test note');
    expect($component->get('currentItem.url'))->toBe('');
});

test('addToCart adds item when price provided', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('currentItem', [
        'url' => '',
        'qty' => '1',
        'color' => '',
        'size' => '',
        'price' => '29.99',
        'currency' => 'USD',
        'notes' => '',
    ]);
    $component->call('addToCart');

    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.price'))->toBe('29.99');
});

test('addToCart clears form after adding', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('currentItem', [
        'url' => 'https://example.com/item',
        'qty' => '3',
        'color' => 'red',
        'size' => 'L',
        'price' => '15',
        'currency' => 'EUR',
        'notes' => 'Notes here',
    ]);
    $component->call('addToCart');

    expect($component->get('currentItem'))->toMatchArray([
        'url' => '',
        'qty' => '1',
        'color' => '',
        'size' => '',
        'price' => '',
        'currency' => 'EUR',
        'notes' => '',
    ]);
});

test('removeItem removes from cart', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('items', [
        ['url' => 'https://a.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => ''],
        ['url' => 'https://b.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => ''],
    ]);
    $component->call('removeItem', 0);

    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://b.com');
});

test('editCartItem moves item back to form', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('items', [
        ['url' => 'https://edit.com', 'qty' => '2', 'color' => 'green', 'size' => 'S', 'price' => '10', 'currency' => 'GBP', 'notes' => 'Edit me'],
    ]);
    $component->call('editCartItem', 0);

    expect($component->get('items'))->toBeEmpty();
    expect($component->get('currentItem.url'))->toBe('https://edit.com');
    expect($component->get('currentItem.qty'))->toBe('2');
    expect($component->get('currentItem.color'))->toBe('green');
    expect($component->get('currentItem.size'))->toBe('S');
    expect($component->get('currentItem.price'))->toBe('10');
    expect($component->get('currentItem.currency'))->toBe('GBP');
});

test('addToCart rejects negative price', function (): void {
    $component = Livewire::test(NewOrder::class);

    $component->set('currentItem', [
        'url' => '',
        'qty' => '1',
        'color' => '',
        'size' => '',
        'price' => '-5',
        'currency' => 'USD',
        'notes' => '',
    ]);
    $component->call('addToCart');

    expect($component->get('items'))->toBeEmpty();
});

test('addToCart respects max products limit', function (): void {
    Setting::set('max_products_per_order', '2', 'integer', 'orders');

    $component = Livewire::test(NewOrder::class);
    $component->set('items', [
        ['url' => 'https://a.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => ''],
        ['url' => 'https://b.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => ''],
    ]);

    $component->set('currentItem', [
        'url' => 'https://c.com',
        'qty' => '1',
        'color' => '',
        'size' => '',
        'price' => '',
        'currency' => 'USD',
        'notes' => '',
    ]);
    $component->call('addToCart');

    expect($component->get('items'))->toHaveCount(2);
});

test('product_url pre-fills currentItem when layout is cart', function (): void {
    $component = Livewire::test(NewOrder::class, ['product_url' => 'https://amazon.com/dp/XYZ']);

    expect($component->get('items'))->toBeEmpty();
    expect($component->get('currentItem.url'))->toBe('https://amazon.com/dp/XYZ');
});

test('duplicate_from pre-fills items when layout is cart', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create(['user_id' => $user->id, 'notes' => 'Dup notes']);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://dup.com/item',
        'qty' => 3,
        'color' => 'navy',
        'size' => 'XL',
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class, ['duplicate_from' => $order->id]);

    expect($component->get('orderNotes'))->toBe('Dup notes');
    expect($component->get('items'))->toHaveCount(1);
    expect($component->get('items.0.url'))->toBe('https://dup.com/item');
    expect($component->get('items.0.qty'))->toBe('3');
    expect($component->get('items.0.color'))->toBe('navy');
});

test('submitOrder creates order from cart when user is logged in', function (): void {
    Setting::set('order_success_screen_enabled', false, 'boolean', 'orders');
    Setting::set('exchange_rates', ['rates' => ['USD' => ['final' => 3.75], 'SAR' => ['final' => 1]]], 'json', 'orders');

    $user = User::factory()->create();
    $user->assignRole('customer');

    $component = Livewire::actingAs($user)
        ->test(NewOrder::class)
        ->set('items', [
            ['url' => 'https://cart-test.com/p1', 'qty' => '2', 'color' => 'black', 'size' => 'M', 'price' => '25', 'currency' => 'USD', 'notes' => 'Cart item 1'],
        ])
        ->set('orderNotes', 'Cart order notes')
        ->call('submitOrder');

    $order = Order::where('user_id', $user->id)->orderByDesc('id')->first();
    expect($order)->not->toBeNull();
    expect($order->notes)->toBe('Cart order notes');
    expect((string) $order->layout_option)->toBe('cart');

    $item = OrderItem::where('order_id', $order->id)->first();
    expect($item)->not->toBeNull();
    expect($item->url)->toBe('https://cart-test.com/p1');
    expect($item->qty)->toBe(2);
    expect($item->color)->toBe('black');
    expect($item->size)->toBe('M');
    expect((float) $item->unit_price)->toBe(25.0);
    expect($item->currency)->toBe('USD');
});

test('submitOrder prompts login modal when guest', function (): void {
    $component = Livewire::test(NewOrder::class)
        ->set('items', [
            ['url' => 'https://guest.com/item', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '10', 'currency' => 'USD', 'notes' => ''],
        ])
        ->call('submitOrder');

    $component->assertDispatched('open-login-modal');
    expect(Order::count())->toBe(0);
});

test('getCartSummary returns correct subtotal and commission', function (): void {
    Setting::set('exchange_rates', ['rates' => ['USD' => ['final' => 3.75], 'SAR' => ['final' => 1]]], 'json', 'orders');

    $component = Livewire::test(NewOrder::class)
        ->set('items', [
            ['url' => 'https://a.com', 'qty' => '2', 'color' => '', 'size' => '', 'price' => '50', 'currency' => 'USD', 'notes' => ''],
        ]);

    $summary = $component->instance()->getCartSummary();
    expect($summary['subtotal'])->toBe(375.0);
    expect($summary['filledCount'])->toBe(1);
    expect($summary['total'])->toBeGreaterThan($summary['subtotal']);
});
