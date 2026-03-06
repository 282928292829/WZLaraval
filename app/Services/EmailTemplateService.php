<?php

namespace App\Services;

use App\Models\Setting;

class EmailTemplateService
{
    public const TYPES = [
        'registration',
        'welcome',
        'password_reset',
        'comment_notification',
        'order_confirmation',
        'status_change',
    ];

    /**
     * Get custom subject from settings. Empty means use Mailable default.
     *
     * @param  array<string, string>  $replacements
     */
    public function getSubject(string $type, string $locale, array $replacements = []): ?string
    {
        $key = "email_{$type}_subject_".($locale === 'ar' ? 'ar' : 'en');
        $value = trim((string) Setting::get($key, ''));

        return $value !== '' ? $this->replace($value, $replacements) : null;
    }

    /**
     * Get custom body from settings. Empty means use Mailable Blade view.
     *
     * @param  array<string, string>  $replacements
     */
    public function getBody(string $type, string $locale, array $replacements = []): ?string
    {
        $key = "email_{$type}_body_".($locale === 'ar' ? 'ar' : 'en');
        $value = trim((string) Setting::get($key, ''));

        return $value !== '' ? $this->replace($value, $replacements) : null;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function replace(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace(':'.$key, (string) $value, $text);
            $text = str_replace('{'.$key.'}', (string) $value, $text);
        }

        return $text;
    }
}
