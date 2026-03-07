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

test('admin can see export csv and import csv buttons on comment templates list page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/admin/orders/comment-templates');

    $response->assertOk();
    $response->assertSee(__('Export CSV'), false);
    $response->assertSee(__('Import CSV'), false);
});

test('admin can export comment templates as csv', function (): void {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('admin.comment-templates.export-csv'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('customer cannot export comment templates', function (): void {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $response = $this->actingAs($user)->get(route('admin.comment-templates.export-csv'));

    $response->assertForbidden();
});
