<?php

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('staff role user can add comment on any order', function (): void {
    Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($staff)->post(route('orders.comments.store', $order), [
        'body' => 'Staff reply via factory-created user.',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    expect($order->comments()->count())->toBe(1);
    expect($order->comments()->first()->body)->toBe('Staff reply via factory-created user.');
});

test('staff can attach files to comment', function (): void {
    Storage::fake('public');
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $comment = $order->comments()->create([
        'user_id' => $staff->id,
        'body' => 'Comment with attachment',
        'is_internal' => false,
    ]);
    $file = UploadedFile::fake()->image('screenshot.png', 100, 100);

    $response = $this->actingAs($staff)->post(
        route('orders.comments.attach-files', [$order, $comment->id]),
        ['files' => [$file]]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($comment->fresh()->files()->count())->toBe(1);
});

test('customer can add comment on own order', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->post(route('orders.comments.store', $order), [
        'body' => 'When will this ship?',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    expect($order->comments()->count())->toBe(1);
    expect($order->comments()->first()->body)->toBe('When will this ship?');
    expect($order->comments()->first()->is_internal)->toBeFalse();
});

test('customer cannot add comment on another customer order', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $otherUser = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($customer)->post(route('orders.comments.store', $order), [
        'body' => 'Trying to comment',
    ]);

    $response->assertForbidden();
    expect($order->comments()->count())->toBe(0);
});

test('staff can add comment on any order', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($staff)->post(route('orders.comments.store', $order), [
        'body' => 'We will ship tomorrow.',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    expect($order->comments()->count())->toBe(1);
    expect($order->comments()->first()->body)->toBe('We will ship tomorrow.');
});

test('staff can add internal note on any order', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($staff)->post(route('orders.comments.store', $order), [
        'body' => 'VIP customer - priority shipping',
        'is_internal' => true,
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    $comment = $order->comments()->first();
    expect($comment)->not->toBeNull();
    expect($comment->is_internal)->toBeTrue();
});

test('staff can edit comment on any order', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $comment = $order->comments()->create([
        'user_id' => $staff->id,
        'body' => 'Original message',
        'is_internal' => false,
    ]);

    $response = $this->actingAs($staff)->patch(
        route('orders.comments.update', [$order, $comment->id]),
        ['body' => 'Updated message']
    );

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    $comment->refresh();
    expect($comment->body)->toBe('Updated message');
    expect($comment->is_edited)->toBeTrue();
});

test('customer can edit own comment on own order', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $comment = $order->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Original',
        'is_internal' => false,
    ]);

    $response = $this->actingAs($customer)->patch(
        route('orders.comments.update', [$order, $comment->id]),
        ['body' => 'Edited by me']
    );

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    $comment->refresh();
    expect($comment->body)->toBe('Edited by me');
});

test('staff can delete any non-system comment', function (): void {
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $comment = $order->comments()->create([
        'user_id' => $customer->id,
        'body' => 'To be deleted',
        'is_internal' => false,
    ]);

    $response = $this->actingAs($staff)->delete(
        route('orders.comments.destroy', [$order, $comment->id])
    );

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');
    expect(OrderComment::withTrashed()->find($comment->id)->trashed())->toBeTrue();
});

test('customer cannot delete another user comment', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $staff = User::where('email', 'staff@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);
    $comment = $order->comments()->create([
        'user_id' => $staff->id,
        'body' => 'Staff comment',
        'is_internal' => false,
    ]);

    $response = $this->actingAs($customer)->delete(
        route('orders.comments.destroy', [$order, $comment->id])
    );

    $response->assertForbidden();
    expect($comment->fresh()->deleted_at)->toBeNull();
});
