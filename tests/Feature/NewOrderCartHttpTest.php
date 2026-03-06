<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Setting::set('order_new_layout', '2', 'string', 'orders');
    app()->setLocale('en');
});

test('new-order page renders cart layout when option 2 is set', function (): void {
    $response = $this->get(route('new-order'));

    $response->assertOk();
    $response->assertSee('Add products one by one', false);
    $response->assertSee('Add to Cart', false);
    $response->assertSee('Cart', false);
});

test('new-order cart layout shows add-product form fields', function (): void {
    $response = $this->get(route('new-order'));

    $response->assertOk();
    $response->assertSee('Product URL', false);
    $response->assertSee('Qty', false);
    $response->assertSee('Color', false);
    $response->assertSee('Size', false);
    $response->assertSee('Price', false);
    $response->assertSee('Currency', false);
});
