<?php

use App\Mail\CommentNotification;
use App\Mail\StatusChangeNotification;
use App\Models\Order;
use App\Models\OrderCommentNotificationLog;
use App\Models\OrderStatusAutomationLog;
use App\Models\OrderStatusAutomationRule;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

test('automation command posts system comment when rule matches', function (): void {
    OrderStatusAutomationRule::create([
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
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
    OrderStatusAutomationRule::create([
        'status' => 'on_hold',
        'days' => 1,
        'hours' => 0,
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
        'hours' => 0,
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

test('automation command triggers after specified hours', function (): void {
    OrderStatusAutomationRule::create([
        'status' => 'needs_payment',
        'days' => 0,
        'hours' => 2,
        'comment_template' => 'Order in {status} for {hours} hours',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subHours(3),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    $order->refresh();
    expect($order->comments()->where('is_system', true)->count())->toBe(1);

    $comment = $order->comments()->where('is_system', true)->first();
    expect($comment->body)->toContain('for 3 hours');
});

test('automation command respects comment_is_internal and posts team-only comment', function (): void {
    OrderStatusAutomationRule::create([
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'comment_template' => 'Team reminder',
        'comment_is_internal' => true,
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    $comment = $order->comments()->where('is_system', true)->first();
    expect($comment)->not->toBeNull();
    expect($comment->is_internal)->toBeTrue();
});

test('automation command skips when pause_if_no_reply threshold met', function (): void {
    OrderStatusAutomationRule::create([
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'pause_if_no_reply_days' => 6,
        'pause_if_no_reply_hours' => 0,
        'comment_template' => 'Reminder',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(10),
    ]);

    $comment = $order->comments()->create([
        'user_id' => $order->user_id,
        'body' => 'Old comment with no reply',
        'is_internal' => false,
        'is_system' => false,
    ]);
    $comment->created_at = now()->subDays(7);
    $comment->saveQuietly();

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    expect($order->comments()->where('is_system', true)->count())->toBe(0);
});

test('action change_status only updates status without posting comment', function (): void {
    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'action_type' => 'change_status',
        'action_status' => 'on_hold',
        'comment_template' => 'Unused',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe('on_hold');
    expect($order->comments()->where('is_system', true)->count())->toBe(0);
});

test('action both posts comment and changes status', function (): void {
    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'action_type' => 'both',
        'action_status' => 'on_hold',
        'comment_template' => 'Moved to on_hold after {days} days',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe('on_hold');
    expect($order->comments()->where('is_system', true)->count())->toBe(1);
    $comment = $order->comments()->where('is_system', true)->first();
    expect($comment->body)->toContain('2 days');
});

test('automation command posts when latest comment is newer than pause threshold', function (): void {
    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'pause_if_no_reply_days' => 6,
        'pause_if_no_reply_hours' => 0,
        'comment_template' => 'Reminder',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(10),
    ]);

    $comment = $order->comments()->create([
        'user_id' => $order->user_id,
        'body' => 'Recent comment',
        'is_internal' => false,
        'is_system' => false,
    ]);
    $comment->created_at = now()->subDays(2);
    $comment->saveQuietly();

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    expect($order->comments()->where('is_system', true)->count())->toBe(1);
});

test('comment trigger posts when customer comment has no reply for threshold', function (): void {
    $user = \App\Models\User::factory()->create();
    $user->givePermissionTo('view-all-orders');

    OrderStatusAutomationRule::create([
        'trigger_type' => 'comment',
        'status' => '',
        'last_comment_from' => 'staff',
        'days' => 2,
        'hours' => 0,
        'comment_template' => 'Reminder: no reply for {days} days',
        'is_active' => true,
    ]);

    $order = Order::factory()->create();
    $staffComment = $order->comments()->create([
        'user_id' => $user->id,
        'body' => 'Staff message',
        'is_internal' => false,
        'is_system' => false,
    ]);
    $staffComment->created_at = now()->subDays(3);
    $staffComment->saveQuietly();

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    expect($order->comments()->where('is_system', true)->count())->toBe(1);
    $autoComment = $order->comments()->where('is_system', true)->first();
    expect($autoComment->body)->toContain('3 days');
});

test('comment trigger does not post twice for same unreplied comment', function (): void {
    $user = \App\Models\User::factory()->create();
    $user->givePermissionTo('view-all-orders');

    OrderStatusAutomationRule::create([
        'trigger_type' => 'comment',
        'status' => '',
        'last_comment_from' => 'staff',
        'days' => 1,
        'hours' => 0,
        'comment_template' => 'Reminder',
        'is_active' => true,
    ]);

    $order = Order::factory()->create();
    $staffComment = $order->comments()->create([
        'user_id' => $user->id,
        'body' => 'Staff message',
        'is_internal' => false,
        'is_system' => false,
    ]);
    $staffComment->created_at = now()->subDays(2);
    $staffComment->saveQuietly();

    $this->artisan('orders:status-automation')->assertSuccessful();
    $this->artisan('orders:status-automation')->assertSuccessful();

    expect($order->comments()->where('is_system', true)->count())->toBe(1);
});

test('notify_customer_email sends CommentNotification when public comment is posted', function (): void {
    Mail::fake();
    Setting::set('email_enabled', true, 'boolean', 'email');

    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'action_type' => 'comment',
        'comment_template' => 'Reminder: {days} days',
        'comment_is_internal' => false,
        'notify_customer_email' => true,
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    Mail::assertQueued(CommentNotification::class);
    expect(OrderCommentNotificationLog::where('order_id', $order->id)->count())->toBe(1);
});

test('notify_customer_email sends StatusChangeNotification when status changes without public comment', function (): void {
    Mail::fake();
    Setting::set('email_enabled', true, 'boolean', 'email');

    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'action_type' => 'change_status',
        'action_status' => 'on_hold',
        'comment_template' => 'Unused',
        'notify_customer_email' => true,
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    Mail::assertQueued(StatusChangeNotification::class);
});

test('notify_customer_email does not send when disabled', function (): void {
    Mail::fake();
    Setting::set('email_enabled', true, 'boolean', 'email');

    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'action_type' => 'comment',
        'comment_template' => 'Reminder',
        'comment_is_internal' => false,
        'notify_customer_email' => false,
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    Mail::assertNotQueued(CommentNotification::class);
    Mail::assertNotQueued(StatusChangeNotification::class);
});

test('notify_customer_email does not send when email_enabled is false', function (): void {
    Mail::fake();
    Setting::set('email_enabled', false, 'boolean', 'email');

    OrderStatusAutomationRule::create([
        'trigger_type' => 'status',
        'status' => 'needs_payment',
        'days' => 1,
        'hours' => 0,
        'action_type' => 'comment',
        'comment_template' => 'Reminder',
        'comment_is_internal' => false,
        'notify_customer_email' => true,
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'status' => 'needs_payment',
        'status_changed_at' => now()->subDays(2),
    ]);

    $this->artisan('orders:status-automation')
        ->assertSuccessful();

    Mail::assertNotQueued(CommentNotification::class);
});
