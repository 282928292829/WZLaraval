<?php

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
