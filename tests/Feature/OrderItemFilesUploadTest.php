<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses()->group('order-item-files');

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

    Setting::set('max_files_per_item_after_submit', 5, 'integer', 'orders');
    Setting::set('max_file_size_mb', 2, 'integer', 'orders');
    Setting::set('customer_can_add_files_after_submit', true, 'boolean', 'orders');

    Storage::fake('public');
});

test('staff can upload files to order item', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $order = Order::factory()->create();
    $item = OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $file = UploadedFile::fake()->image('product.jpg', 100, 100);

    $response = $this->actingAs($staff)
        ->post(route('orders.items.files.store', [$order, $item->id]), [
            'files' => [$file],
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    $response->assertJsonFragment(['message' => __('orders.item_files_uploaded')]);

    expect($order->fresh()->files()->where('order_item_id', $item->id)->count())->toBe(1);
});

test('customer owner can upload when setting enabled', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $file = UploadedFile::fake()->image('product.png', 100, 100);

    $response = $this->actingAs($customer)
        ->post(route('orders.items.files.store', [$order, $item->id]), [
            'files' => [$file],
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect($order->fresh()->files()->where('order_item_id', $item->id)->count())->toBe(1);
});

test('customer cannot upload when setting disabled', function (): void {
    Setting::set('customer_can_add_files_after_submit', false, 'boolean', 'orders');

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $item = OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $file = UploadedFile::fake()->image('product.png', 100, 100);

    $response = $this->actingAs($customer)
        ->post(route('orders.items.files.store', [$order, $item->id]), [
            'files' => [$file],
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertStatus(403);
});

test('validation rejects empty files', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $order = Order::factory()->create();
    $item = OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($staff)
        ->post(route('orders.items.files.store', [$order, $item->id]), [
            'files' => [],
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['files']);
});

test('validation rejects too many files', function (): void {
    Setting::set('max_files_per_item_after_submit', 2, 'integer', 'orders');

    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $order = Order::factory()->create();
    $item = OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $files = [
        UploadedFile::fake()->image('a.jpg', 50, 50),
        UploadedFile::fake()->image('b.jpg', 50, 50),
        UploadedFile::fake()->image('c.jpg', 50, 50),
    ];

    $response = $this->actingAs($staff)
        ->post(route('orders.items.files.store', [$order, $item->id]), [
            'files' => $files,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['files']);
});

test('accepts PDF and doc files', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $order = Order::factory()->create();
    $item = OrderItem::create([
        'order_id' => $order->id,
        'url' => 'https://example.com/product',
        'qty' => 1,
        'currency' => 'USD',
        'sort_order' => 0,
    ]);

    $pdf = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');
    $doc = UploadedFile::fake()->create('notes.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $response = $this->actingAs($staff)
        ->post(route('orders.items.files.store', [$order, $item->id]), [
            'files' => [$pdf, $doc],
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect($order->fresh()->files()->where('order_item_id', $item->id)->count())->toBe(2);
});
