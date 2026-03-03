<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('order show page renders lightbox markup and scripts', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->get(route('orders.show', $order));

    $response->assertOk();
    $response->assertSee('orderLightboxImages', false);
    $response->assertSee('open-lightbox', false);
});

test('order show page lightbox receives gallery from comment images', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $comment = $order->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Test comment',
        'is_internal' => false,
    ]);

    $order->files()->create([
        'user_id' => $customer->id,
        'comment_id' => $comment->id,
        'path' => 'order-files/1/test.jpg',
        'original_name' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1000,
        'type' => 'comment',
    ]);

    $response = $this->actingAs($customer)->get(route('orders.show', $order));

    $response->assertOk();
    // Gallery should include the comment image URL
    $response->assertSee('orderLightboxImages', false);
    // Comment image thumbnail should dispatch open-lightbox on click
    $response->assertSee('open-lightbox', false);
});
