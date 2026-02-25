<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('unauthenticated user is redirected to login', function (): void {
    $response = $this->get('/admin/orders/comment-templates');

    $response->assertRedirect('/admin/login');
});

test('admin can access comment templates list page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/orders/comment-templates');

    $response->assertOk();
    $response->assertSee(__('Comment Templates'), false);
});

test('admin can see import from wordpress button on comment templates list page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/orders/comment-templates');

    $response->assertOk();
    $response->assertSee(__('Import from WordPress'), false);
});
