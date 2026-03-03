<?php

test('converts Arabic-Indic digits ٠١٢٣٤٥٦٧٨٩ to English', function (): void {
    expect(to_english_digits('٠١٢٣٤٥٦٧٨٩'))->toBe('0123456789');
});

test('converts Persian digits ۰۱۲۳۴۵۶۷۸۹ to English', function (): void {
    expect(to_english_digits('۰۱۲۳۴۵۶۷۸۹'))->toBe('0123456789');
});

test('converts mixed Arabic amount ٥٥٠٫٥٠', function (): void {
    expect(to_english_digits('٥٥٠٫٥٠'))->toBe('550.50');
});

test('leaves English digits unchanged', function (): void {
    expect(to_english_digits('123.45'))->toBe('123.45');
});

test('returns null for null input', function (): void {
    expect(to_english_digits(null))->toBeNull();
});

test('returns empty string for empty input', function (): void {
    expect(to_english_digits(''))->toBe('');
});
