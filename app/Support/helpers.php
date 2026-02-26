<?php

if (! function_exists('allowed_upload_mimes')) {
    /**
     * Allowed MIME types for order-related file uploads (images, docs, spreadsheets).
     */
    function allowed_upload_mimes(): string
    {
        return 'jpg,jpeg,png,gif,webp,bmp,tiff,tif,pdf,doc,docx,xls,xlsx,csv,heic';
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
