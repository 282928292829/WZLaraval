<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can submit contact form', function (): void {
    $response = $this->post(route('contact.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+966501234567',
        'subject' => 'Test Subject',
        'message' => 'Test message content',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'contact-sent');

    $this->assertDatabaseHas('contact_submissions', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'subject' => 'Test Subject',
        'message' => 'Test message content',
    ]);
});

test('contact form requires name email and message', function (): void {
    $response = $this->post(route('contact.store'), [
        'name' => '',
        'email' => 'invalid',
        'message' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'message']);
});
