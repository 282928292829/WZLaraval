<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('unauthenticated user is redirected to login', function (): void {
    $response = $this->get('/admin/orders/ad-campaigns');

    $response->assertRedirect('/admin/login');
});

test('admin can access ad campaigns list page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/orders/ad-campaigns');

    $response->assertOk();
    $response->assertSee('Ad Campaigns', false);
});

test('admin can access ad campaigns create page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/orders/ad-campaigns/create');

    $response->assertOk();
});

test('go route records click and redirects to register', function (): void {
    $campaign = \App\Models\AdCampaign::create([
        'title' => 'Test Banner',
        'slug' => 'test-banner',
        'is_active' => true,
    ]);

    $response = $this->get(route('go', ['slug' => 'test-banner']));

    $response->assertRedirect();
    expect($campaign->fresh()->click_count)->toBe(1);
});

test('order cancelled increments campaign orders_cancelled', function (): void {
    $campaign = \App\Models\AdCampaign::create([
        'title' => 'Test Campaign',
        'slug' => 'test-cancel',
        'is_active' => true,
    ]);
    $user = \App\Models\User::factory()->create(['ad_campaign_id' => $campaign->id]);
    $user->assignRole('customer');
    $order = \App\Models\Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)->post(route('orders.cancel', $order->id));

    expect($campaign->fresh()->orders_cancelled)->toBe(1);
});
