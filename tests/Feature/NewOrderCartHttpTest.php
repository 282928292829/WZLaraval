<?php

use App\Livewire\NewOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Setting::set('order_new_layout', '2', 'string', 'orders');
    app()->setLocale('en');
});

test('guest submit order opens login modal without redirect', function (): void {
    $this->get(route('new-order'));

    Livewire::test(NewOrder::class)
        ->set('items', [
            ['url' => 'https://example.com/p1', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '10', 'currency' => 'USD', 'notes' => ''],
        ])
        ->set('itemFiles', [[]])
        ->call('submitOrder')
        ->assertSet('showLoginModal', true)
        ->assertNoRedirect();
});

test('guest cart layout loadGuestDraftFromStorage loads items when empty', function (): void {
    $guestComponent = Livewire::test(NewOrder::class);
    $guestComponent->call('loadGuestDraftFromStorage', [
        ['url' => 'https://test.com', 'qty' => '2', 'color' => 'red', 'size' => 'L', 'price' => '25', 'currency' => 'USD', 'notes' => 'test'],
    ], 'Please ship fast');

    $items = $guestComponent->get('items');
    expect($items)->toBeArray()->toHaveCount(1);
    expect($items[0]['url'] ?? null)->toBe('https://test.com');
    expect($items[0]['qty'] ?? null)->toBe('2');
    expect($guestComponent->get('orderNotes'))->toBe('Please ship fast');
});

test('guest cart layout loadGuestDraftFromStorage is no-op when logged in', function (): void {
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(NewOrder::class)
        ->call('loadGuestDraftFromStorage', [
            ['url' => 'https://test.com', 'qty' => '1', 'color' => '', 'size' => '', 'price' => '', 'currency' => 'USD', 'notes' => ''],
        ], 'notes')
        ->assertSet('items', []);
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

test('new-order page renders option 3 cards layout with one-card-at-a-time desktop when layout 3 is set', function (): void {
    Setting::set('order_new_layout', '3', 'string', 'orders');

    $response = $this->get(route('new-order'));

    $response->assertOk();
    $response->assertSee('Create new order', false);
    $response->assertSee('Add product', false);
    $response->assertSee('prevCard', false);
    $response->assertSee('nextCard', false);
    $response->assertSee('activeCardIndex', false);
});
