<?php

use App\Livewire\GuestLoginModal;
use App\Livewire\NewOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Setting::set('order_new_layout', 'cart', 'string', 'orders');
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
        ->assertDispatched('open-login-modal')
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
    $response->assertSee('Product price', false);
    $response->assertSee('Currency', false);
});

test('new-order page renders option 3 cards layout when layout 3 is set', function (): void {
    Setting::set('order_new_layout', 'cards', 'string', 'orders');

    $response = $this->get(route('new-order'));

    $response->assertOk();
    $response->assertSee('Create new order', false);
    $response->assertSee('Add Another Product', false);
    $response->assertSee('newOrderFormCards', false);
    $response->assertSee('items-container', false);
});

test('new-order-cart route renders cart layout with form sidebar and bottom-sheet', function (): void {
    $response = $this->get(route('new-order-cart'));

    $response->assertOk();
    $response->assertSee('Add to Cart', false);
    $response->assertSee('newOrderFormCart', false);
    $response->assertSee('cart-sidebar', false);
    $response->assertSee('Cart', false);
});

test('addToCart adds item to cart', function (): void {
    Livewire::test(NewOrder::class)
        ->set('activeLayout', 'cart')
        ->set('currentItem', [
            'url' => 'https://example.com/product',
            'qty' => '2',
            'color' => 'Blue',
            'size' => 'M',
            'price' => '29.99',
            'currency' => 'USD',
            'notes' => 'Test note',
        ])
        ->call('addToCart')
        ->assertSet('items.0.url', 'https://example.com/product')
        ->assertSet('items.0.qty', '2')
        ->assertSet('items.0.color', 'Blue')
        ->assertSet('items.0.size', 'M')
        ->assertSet('items.0.price', '29.99')
        ->assertSet('items.0.currency', 'USD')
        ->assertSet('items.0.notes', 'Test note')
        ->assertSet('currentItem.url', '')
        ->assertSet('currentItem.color', '')
        ->assertSet('currentItem.size', '')
        ->assertSet('currentItem.price', '')
        ->assertSet('currentItem.notes', '');
});

test('guest on new-order-cart sees draft restore prompt copy when draft exists', function (): void {
    $response = $this->get(route('new-order-cart'));

    $response->assertOk();
    $response->assertSee('Unsaved items from your last visit', false);
});

test('new-order-cart-inline route renders cards-style layout with sidebar and add product button', function (): void {
    $response = $this->get(route('new-order-cart-inline'));

    $response->assertOk();
    $response->assertSee('Create new order', false);
    $response->assertSee('Add Another Product', false);
    $response->assertSee('newOrderFormCartInline', false);
    $response->assertSee('items-container', false);
    $response->assertSee('cart-inline-sidebar', false);
    $response->assertSee('Cart', false);
    $response->assertSee('Review Cart', false);
});

test('cart-inline addProduct adds item via Livewire', function (): void {
    Livewire::test(NewOrder::class)
        ->set('activeLayout', 'cart-inline')
        ->call('addItem', 'USD')
        ->assertSet('items.0.url', '')
        ->assertSet('items.0.qty', '1')
        ->assertSet('items.0.currency', 'USD');
});

test('new-order-cart-next route renders Bersonal-style drawer layout', function (): void {
    $response = $this->get(route('new-order-cart-next'));

    $response->assertOk();
    $response->assertSee('Add to Cart', false);
    $response->assertSee('newOrderFormCartNext', false);
    $response->assertSee('Checkout', false);
});

test('all layouts include user-logged-in event listener for attach-after-login fix', function (): void {
    $routes = [
        'new-order-table' => '@user-logged-in.window="isLoggedIn = true"',
        'new-order-cards' => '@user-logged-in.window="isLoggedIn = true"',
        'new-order-hybrid' => '@user-logged-in.window="isLoggedIn = true"',
        'new-order-wizard' => '@user-logged-in.window="isLoggedIn = true"',
        'new-order-cart-inline' => '@user-logged-in.window="isLoggedIn = true"',
        'new-order-cart' => '@user-logged-in.window="attachBlocked = false"',
        'new-order-cart-next' => '@user-logged-in.window="attachBlocked = false"',
    ];

    foreach ($routes as $routeName => $expectedHtml) {
        $response = $this->get(route($routeName));
        $response->assertOk();
        $response->assertSee($expectedHtml, false);
    }
});

test('loginFromModal for attach reason dispatches user-logged-in event', function (): void {
    $user = User::factory()->create([
        'email' => 'attach-test@example.com',
        'password' => bcrypt('password123'),
    ]);
    $user->assignRole('customer');

    Livewire::test(GuestLoginModal::class)
        ->set('loginModalReason', 'attach')
        ->set('modalEmail', 'attach-test@example.com')
        ->set('modalPassword', 'password123')
        ->call('loginFromModal')
        ->assertSet('showLoginModal', false)
        ->assertDispatched('user-logged-in');
});

test('registerFromModal for attach reason dispatches user-logged-in event', function (): void {
    Livewire::test(GuestLoginModal::class)
        ->set('loginModalReason', 'attach')
        ->set('modalEmail', 'new-user-attach@example.com')
        ->set('modalPassword', 'password123')
        ->call('checkModalEmail')
        ->assertSet('modalStep', 'register');

    Livewire::test(GuestLoginModal::class)
        ->set('loginModalReason', 'attach')
        ->set('modalEmail', 'new-user-attach@example.com')
        ->set('modalPassword', 'password123')
        ->set('modalStep', 'register')
        ->call('registerFromModal')
        ->assertSet('showLoginModal', false)
        ->assertDispatched('user-logged-in');
});
