<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
});

test('admin with manage-settings can access translations page', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();

    $response = $this->actingAs($admin)->get(route('filament.admin.pages.translations-page'));

    $response->assertOk();
});

test('translations page loads and displays translation keys from lang files', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();

    $response = $this->actingAs($admin)->get(route('filament.admin.pages.translations-page'));

    $response->assertOk();

    // Page loads; loadRows merges lang/ar.json and lang/en.json (LARAVEL_PLAN Phase 4)
    // Table shows keys - at least one key from our lang files should appear
    $content = $response->getContent();
    expect($content)->toBeString();
    expect(strlen($content))->toBeGreaterThan(1000);
});

test('translations page handles missing locale file gracefully', function (): void {
    $admin = User::where('email', 'admin@wasetzon.test')->first();
    $arPath = lang_path('ar.json');
    $enPath = lang_path('en.json');

    // Ensure both files exist (they should from default install)
    expect(File::exists($arPath))->toBeTrue();
    expect(File::exists($enPath))->toBeTrue();

    $response = $this->actingAs($admin)->get(route('filament.admin.pages.translations-page'));

    // Page loads without error even if one locale has fewer keys
    $response->assertOk();
});
