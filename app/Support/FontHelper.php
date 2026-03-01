<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class FontHelper
{
    public const DEFAULT_AR = 'IBM Plex Sans Arabic';

    public const DEFAULT_EN = 'Inter';

    /**
     * Get the font-family CSS value for the current locale.
     * Uses font_family_ar / font_family_en if set, else single font_family.
     * When custom font is active (font_source set), uses font_family for both locales.
     */
    public static function cssFontFamily(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $defaultAr = self::DEFAULT_AR;
        $defaultEn = self::DEFAULT_EN;

        // Custom font (Google or upload) overrides per-locale fonts
        if (self::hasCustomFont()) {
            $active = self::getActiveFontFamily();

            return "'{$active}', ui-sans-serif, system-ui, sans-serif";
        }

        $fontAr = Setting::get('font_family_ar', '') ?: Setting::get('font_family', $defaultAr);
        $fontEn = Setting::get('font_family_en', '') ?: Setting::get('font_family', $defaultEn);

        $font = $locale === 'ar' ? $fontAr : $fontEn;

        return "'{$font}', ui-sans-serif, system-ui, sans-serif";
    }

    /**
     * Whether a custom font (Google or upload) is configured.
     */
    public static function hasCustomFont(): bool
    {
        $source = Setting::get('font_source', '');

        return $source === 'google' || $source === 'upload';
    }

    /**
     * Get the active font family name when custom font is used.
     */
    public static function getActiveFontFamily(): string
    {
        $family = trim((string) Setting::get('font_family', ''));

        if ($family !== '') {
            return $family;
        }

        $source = Setting::get('font_source', '');
        if ($source === 'google') {
            $extracted = self::extractFontFamilyFromGoogleUrl(Setting::get('font_google_url', ''));

            return $extracted ?? self::DEFAULT_AR;
        }

        if ($source === 'upload') {
            return trim((string) Setting::get('font_upload_family_name', '')) ?: self::DEFAULT_AR;
        }

        return self::DEFAULT_AR;
    }

    /**
     * Extract font family name from a Google Fonts URL.
     * e.g. https://fonts.googleapis.com/css2?family=Cairo -> Cairo
     * e.g. https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400 -> IBM Plex Sans Arabic
     */
    public static function extractFontFamilyFromGoogleUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $parsed = parse_url($url, PHP_URL_QUERY);
        if (! is_string($parsed)) {
            return null;
        }

        parse_str($parsed, $params);
        $family = $params['family'] ?? null;

        if (! is_string($family)) {
            return null;
        }

        // Remove weight/italic suffix e.g. ":wght@400;500" or ":ital,wght@0,400"
        $family = preg_replace('/[:@].*$/', '', $family);

        return trim(str_replace('+', ' ', $family)) ?: null;
    }

    /**
     * HTML to inject into <head> for the custom font (link or style block).
     */
    public static function getFontHeadHtml(): string
    {
        $source = Setting::get('font_source', '');

        if ($source === 'google') {
            $url = trim((string) Setting::get('font_google_url', ''));

            if ($url === '') {
                return '';
            }

            $preconnect = '<link rel="preconnect" href="https://fonts.googleapis.com">'."\n"
                .'        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'."\n";

            return $preconnect.'        <link href="'.e($url).'" rel="stylesheet">';
        }

        if ($source === 'upload') {
            $path = Setting::get('font_uploaded_path', '');
            $family = self::getActiveFontFamily();

            if ($path === '' || $family === '') {
                return '';
            }

            $fullPath = Storage::disk('public')->path($path);
            if (! is_file($fullPath)) {
                return '';
            }

            $url = Storage::disk('public')->url($path);

            return '<style>'."\n"
                .'@font-face {'."\n"
                .'  font-family: \''.addslashes($family).'\';'."\n"
                .'  src: url(\''.e($url).'\') format(\''.self::fontFormat($path).'\');'."\n"
                .'  font-display: swap;'."\n"
                .'}'."\n"
                .'</style>';
        }

        return '';
    }

    private static function fontFormat(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'woff2' => 'woff2',
            'woff' => 'woff',
            'ttf' => 'truetype',
            default => 'woff2',
        };
    }
}
