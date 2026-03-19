<?php

use App\DTOs\OrderSubmissionData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Setting::set('order_success_screen_enabled', false, 'boolean', 'orders');
    Setting::set('exchange_rates', ['rates' => ['USD' => ['final' => 3.75], 'SAR' => ['final' => 1]]], 'json', 'orders');
    Setting::set('order_new_layout', 'cart', 'string', 'orders');
});

test('submit creates new order successfully', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $data = new OrderSubmissionData(
        userId: $user->id,
        isStaff: false,
        items: [['data' => ['url' => 'https://test.com/p1', 'qty' => '2', 'color' => 'red', 'size' => 'M', 'price' => '25', 'currency' => 'USD', 'notes' => 'test'], 'orig' => 0]],
        orderNotes: 'Order notes',
        normalizedFiles: [0 => []],
        exchangeRates: ['USD' => 3.75, 'SAR' => 1.0],
        maxImagesPerItem: 3,
        maxImagesPerOrder: 10,
        request: request(),
    );

    $service = app(OrderSubmissionService::class);
    $result = $service->submit($data);

    expect($result->success)->toBeTrue();
    expect($result->orderId)->not->toBeNull();
    expect($result->redirectUrl)->not->toBeNull();

    $order = Order::find($result->orderId);
    expect($order)->not->toBeNull();
    expect($order->user_id)->toBe($user->id);
    expect($order->notes)->toBe('Order notes');

    $item = OrderItem::where('order_id', $order->id)->first();
    expect($item)->not->toBeNull();
    expect($item->url)->toBe('https://test.com/p1');
    expect($item->qty)->toBe(2);
    expect($item->color)->toBe('red');
    expect($item->size)->toBe('M');
    expect((float) $item->unit_price)->toBe(25.0);
});

test('submit edit flow updates existing order', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => '100',
        'status' => 'pending',
        'is_paid' => false,
        'can_edit_until' => now()->addMinutes(15),
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://old.com',
        'is_url' => true,
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $data = new OrderSubmissionData(
        userId: $user->id,
        isStaff: false,
        items: [['data' => ['url' => 'https://new.com/p1', 'qty' => '3', 'color' => 'blue', 'size' => 'L', 'price' => '30', 'currency' => 'USD', 'notes' => 'updated'], 'orig' => 0]],
        orderNotes: 'Updated notes',
        normalizedFiles: [0 => []],
        exchangeRates: ['USD' => 3.75, 'SAR' => 1.0],
        maxImagesPerItem: 3,
        maxImagesPerOrder: 10,
        editingOrderId: $order->id,
        request: request(),
    );

    $service = app(OrderSubmissionService::class);
    $result = $service->submit($data);

    expect($result->success)->toBeTrue();
    expect($result->orderId)->toBe($order->id);

    $order->refresh();
    expect($order->notes)->toBe('Updated notes');
    expect($order->can_edit_until)->toBeNull();

    $items = OrderItem::where('order_id', $order->id)->get();
    expect($items)->toHaveCount(1);
    expect($items[0]->url)->toBe('https://new.com/p1');
    expect($items[0]->qty)->toBe(3);
    expect($items[0]->color)->toBe('blue');
});

test('submit returns error when hourly rate limit exceeded', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Setting::set('orders_per_hour_customer', 2, 'integer', 'orders');

    Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => '100',
        'created_at' => now()->subMinutes(30),
    ]);
    Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => '101',
        'created_at' => now()->subMinutes(30),
    ]);

    $data = new OrderSubmissionData(
        userId: $user->id,
        isStaff: false,
        items: [['data' => ['url' => 'https://test.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '10', 'currency' => 'USD', 'notes' => ''], 'orig' => 0]],
        orderNotes: '',
        normalizedFiles: [0 => []],
        exchangeRates: ['USD' => 3.75, 'SAR' => 1.0],
        maxImagesPerItem: 3,
        maxImagesPerOrder: 10,
        request: request(),
    );

    $service = app(OrderSubmissionService::class);
    $result = $service->submit($data);

    expect($result->success)->toBeFalse();
    expect($result->errorMessage)->toContain('2');
    expect($result->errorType)->toBe('notify');
});
