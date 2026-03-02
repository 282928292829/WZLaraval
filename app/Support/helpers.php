<?php

if (! function_exists('order_form_currencies')) {
    /**
     * Currency list for the order form. Same source used by NewOrder and design prototypes.
     * Keys: currency code; values: ['label' => translated label, 'symbol' => symbol].
     */
    function order_form_currencies(): array
    {
        return [
            'USD' => ['label' => __('order_form.cur_usd'), 'symbol' => '$'],
            'EUR' => ['label' => __('order_form.cur_eur'), 'symbol' => '€'],
            'GBP' => ['label' => __('order_form.cur_gbp'), 'symbol' => '£'],
            'CNY' => ['label' => __('order_form.cur_cny'), 'symbol' => '¥'],
            'JPY' => ['label' => __('order_form.cur_jpy'), 'symbol' => '¥'],
            'KRW' => ['label' => __('order_form.cur_krw'), 'symbol' => '₩'],
            'TRY' => ['label' => __('order_form.cur_try'), 'symbol' => '₺'],
            'SAR' => ['label' => __('order_form.cur_sar'), 'symbol' => 'ر.س'],
            'OTHER' => ['label' => __('order_form.cur_other'), 'symbol' => '—'],
        ];
    }
}

if (! function_exists('allowed_upload_mimes')) {
    /**
     * Allowed MIME types for order-related file uploads (images, docs, spreadsheets).
     */
    function allowed_upload_mimes(): string
    {
        return 'jpg,jpeg,png,gif,webp,bmp,tiff,tif,pdf,doc,docx,xls,xlsx,csv,heic';
    }
}

if (! function_exists('allowed_upload_mime_types')) {
    /**
     * MIME types for client-side file validation. Derived from allowed_upload_mimes()
     * so frontend and backend stay in sync.
     *
     * @return array<string>
     */
    function allowed_upload_mime_types(): array
    {
        $extensions = array_map('trim', explode(',', allowed_upload_mimes()));
        $map = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'bmp' => ['image/bmp'],
            'tiff' => ['image/tiff'],
            'tif' => ['image/tiff'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'csv' => ['text/csv', 'application/csv'],
            'heic' => ['image/heic'],
        ];
        $mimes = [];
        foreach ($extensions as $ext) {
            if (isset($map[$ext])) {
                $mimes = array_merge($mimes, $map[$ext]);
            }
        }

        return array_values(array_unique($mimes));
    }
}

if (! function_exists('safe_item_url')) {
    /**
     * Return a safe URL for use in href, or null if unsafe.
     * Accepts http/https URLs and domain-like strings (e.g. example.com).
     * Rejects javascript:, data:, vbscript: and other dangerous schemes.
     */
    function safe_item_url(?string $url): ?string
    {
        $url = trim($url ?? '');
        if ($url === '') {
            return null;
        }

        $lower = strtolower($url);

        // Reject dangerous schemes
        $dangerous = ['javascript:', 'data:', 'vbscript:', 'file:'];
        foreach ($dangerous as $scheme) {
            if (str_starts_with($lower, $scheme)) {
                return null;
            }
        }

        // Already http or https
        if (str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://')) {
            return $url;
        }

        // Domain-like: example.com, www.example.com (must contain a dot to avoid "red", "product")
        if (str_contains($url, '.') && preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?(\/.*)?$/i', $url)) {
            return 'https://'.$url;
        }

        return null;
    }
}

if (! function_exists('format_datetime_for_display')) {
    /**
     * Format a date/time for display, using site timezone or authenticated user's timezone.
     * When "use user timezone" is enabled and user has a timezone set, uses that.
     * Otherwise uses site timezone from admin settings (default Asia/Riyadh).
     */
    function format_datetime_for_display(?\Carbon\CarbonInterface $date, string $format = 'Y/m/d H:i'): string
    {
        if (! $date) {
            return '—';
        }
        if (! $date instanceof \Carbon\Carbon) {
            $date = \Carbon\Carbon::parse($date);
        }
        $tz = \App\Models\Setting::get('times_use_user_timezone', false)
            && auth()->check()
            && filled(auth()->user()->timezone ?? null)
            ? auth()->user()->timezone
            : \App\Models\Setting::get('site_timezone', 'Asia/Riyadh');

        return $date->copy()->timezone($tz)->format($format);
    }
}

if (! function_exists('comment_body_safe')) {
    /**
     * Escape comment body and convert newlines/URLs for safe HTML output.
     * Prevents XSS: user content is escaped before any HTML is added.
     */
    function comment_body_safe(?string $body): string
    {
        return nl2br(linkify_whatsapp(e($body ?? '')));
    }
}

if (! function_exists('linkify_whatsapp')) {
    /**
     * Convert WhatsApp phone numbers in text to clickable wa.me links.
     * Matches formats: 00966556063500, 966556063500, +966556063500
     */
    function linkify_whatsapp(string $text): string
    {
        return preg_replace_callback(
            '/\b((?:00|\+)?\d{10,15})\b/',
            function (array $m): string {
                $num = preg_replace('/\D/', '', $m[1]);
                $num = ltrim($num, '0');
                if (strlen($num) < 10) {
                    return $m[0];
                }

                return '<a href="https://wa.me/'.$num.'" target="_blank" rel="noopener" class="text-primary-600 hover:underline">'.e($m[1]).'</a>';
            },
            $text
        );
    }
}
