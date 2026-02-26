<?php

test('safe_item_url returns null for empty input', function (): void {
    expect(safe_item_url(''))->toBeNull()
        ->and(safe_item_url(null))->toBeNull()
        ->and(safe_item_url('   '))->toBeNull();
});

test('safe_item_url rejects dangerous schemes', function (): void {
    expect(safe_item_url('javascript:alert(1)'))->toBeNull()
        ->and(safe_item_url('JavaScript:void(0)'))->toBeNull()
        ->and(safe_item_url('data:text/html,<script>alert(1)</script>'))->toBeNull()
        ->and(safe_item_url('vbscript:msgbox(1)'))->toBeNull()
        ->and(safe_item_url('file:///etc/passwd'))->toBeNull();
});

test('safe_item_url accepts http and https', function (): void {
    expect(safe_item_url('https://amazon.com/dp/123'))->toBe('https://amazon.com/dp/123')
        ->and(safe_item_url('http://example.com'))->toBe('http://example.com');
});

test('safe_item_url prepends https for domain-like input', function (): void {
    expect(safe_item_url('amazon.com'))->toBe('https://amazon.com')
        ->and(safe_item_url('www.example.com'))->toBe('https://www.example.com')
        ->and(safe_item_url('example.com/path'))->toBe('https://example.com/path');
});

test('safe_item_url returns null for plain text', function (): void {
    expect(safe_item_url('red size M'))->toBeNull()
        ->and(safe_item_url('product name only'))->toBeNull()
        ->and(safe_item_url('see notes'))->toBeNull();
});
