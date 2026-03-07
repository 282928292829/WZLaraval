<?php

test('dev login-as route returns 404 when APP_ENV is not local', function (): void {
    expect(app()->environment('local'))->toBeFalse(
        'This test requires APP_ENV to not be "local" (e.g. "testing" or "production"). '
        .'The dev login route must not be exposed in non-local environments.'
    );

    $response = $this->post('/_dev/login-as', ['role' => 'admin']);

    $response->assertNotFound();
});
