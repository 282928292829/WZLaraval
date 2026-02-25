<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Artisan::call('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    Storage::fake('public');
});

test('customer cannot generate invoice', function (): void {
    $customer = User::where('email', 'customer@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $customer->id]);

    $response = $this->actingAs($customer)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::FirstPayment->value,
        'first_items_total' => 100,
        'first_agent_fee' => 10,
        'action' => 'preview',
    ]);

    $response->assertForbidden();
});

test('editor can generate first_payment invoice preview', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::FirstPayment->value,
        'first_items_total' => 100,
        'first_agent_fee' => 10,
        'action' => 'preview',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('Invoice-');
    expect($response->headers->get('Content-Disposition'))->toContain('.pdf');
});

test('editor can generate first_payment invoice publish', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::FirstPayment->value,
        'first_items_total' => 100,
        'first_agent_fee' => 10,
        'action' => 'publish',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');

    $order->refresh();
    expect($order->comments()->where('is_internal', false)->count())->toBe(1);
    expect($order->files()->where('type', 'invoice')->count())->toBe(1);
});

test('editor can generate second_final invoice preview', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::SecondFinal->value,
        'second_product_value' => 200,
        'second_agent_fee' => 20,
        'second_shipping_cost' => 50,
        'second_first_payment' => 100,
        'second_remaining' => 170,
        'second_weight' => '1.5 kg',
        'second_shipping_company' => 'Aramex',
        'action' => 'preview',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('editor can generate second_final invoice publish', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::SecondFinal->value,
        'second_product_value' => 200,
        'second_agent_fee' => 20,
        'second_shipping_cost' => 50,
        'second_first_payment' => 100,
        'second_remaining' => 170,
        'action' => 'publish',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');
    $order->refresh();
    expect($order->files()->where('type', 'invoice')->count())->toBe(1);
});

test('editor can generate items_cost invoice preview', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);
    OrderItem::create([
        'order_id' => $order->id,
        'unit_price' => 50,
        'final_price' => 55,
        'qty' => 2,
        'currency' => 'SAR',
    ]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::ItemsCost->value,
        'items' => [
            ['description' => 'Product A', 'qty' => 1, 'unit_price' => 100, 'currency' => 'SAR'],
            ['description' => 'Product B', 'qty' => 2, 'unit_price' => 50, 'currency' => 'SAR'],
        ],
        'action' => 'preview',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('editor can generate items_cost invoice publish', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::ItemsCost->value,
        'items' => [
            ['description' => 'Product A', 'qty' => 1, 'unit_price' => 100, 'currency' => 'SAR'],
        ],
        'action' => 'publish',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');
    $order->refresh();
    expect($order->files()->where('type', 'invoice')->count())->toBe(1);
});

test('editor can generate general invoice preview', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::General->value,
        'custom_amount' => 500,
        'action' => 'preview',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('editor can generate general invoice publish', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id]);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::General->value,
        'custom_amount' => 500,
        'action' => 'publish',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $response->assertSessionHas('success');
    $order->refresh();
    expect($order->files()->where('type', 'invoice')->count())->toBe(1);
});

test('custom filename override is applied', function (): void {
    $editor = User::where('email', 'editor@wasetzon.test')->first();
    $order = Order::factory()->create(['user_id' => $editor->id, 'order_number' => 'ORD-12345']);

    $response = $this->actingAs($editor)->post(route('orders.invoice.generate', $order->id), [
        'invoice_type' => InvoiceType::General->value,
        'custom_amount' => 100,
        'custom_filename' => 'Custom-Invoice-{order_number}.pdf',
        'action' => 'preview',
    ]);

    $response->assertOk();
    $contentDisposition = $response->headers->get('Content-Disposition') ?? '';
    expect($contentDisposition)->toContain('Custom-Invoice-ORD-12345');
});
