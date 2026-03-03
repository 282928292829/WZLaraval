<?php

use App\Models\Order;
use App\Models\OrderStatusAutomationLog;
use App\Models\OrderStatusAutomationRule;

test('automation command posts system comment when rule matches', function (): void {
    $rule = OrderStatusAutomationRule::create([
        'status' => 'needs_payment',
        'days' => 1,
        'comment_template' => 'Order in {status} for {days} days',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    $order->refresh();
    expect($order->comments()->where('is_system', true)->count())->toBe(1);
    expect(OrderStatusAutomationLog::where('order_id', $order->id)->count())->toBe(1);

    $comment = $order->comments()->where('is_system', true)->first();
    expect($comment->body)->toContain('Order in');
    expect($comment->body)->toContain('for 2 days');
});

test('automation command does not post twice for same order and rule', function (): void {
    $rule = OrderStatusAutomationRule::create([
        'status' => 'on_hold',
        'days' => 1,
        'comment_template' => 'Reminder: {status} for {days} days',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'on_hold',
        'status_changed_at' => now()->subDays(3),
    ]);

    $this->artisan('orders:status-automation')->assertSuccessful();
    $this->artisan('orders:status-automation')->assertSuccessful();

    expect($order->comments()->where('is_system', true)->count())->toBe(1);
});

test('automation command skips when order not in status long enough', function (): void {
    OrderStatusAutomationRule::create([
        'status' => 'needs_payment',
        'days' => 10,
        'comment_template' => 'Reminder',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')->assertSuccessful();

    expect($order->comments()->where('is_system', true)->count())->toBe(0);
});
