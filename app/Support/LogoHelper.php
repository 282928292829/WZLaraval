<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class LogoHelper
{
    /**
     * Get the logo image URL for the current locale.

     *
     * @return string|null Returns the URL or null if no logo image is set
     */
    public static function getLogoUrl(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $usePerLanguage = (bool) Setting::get('logo_use_per_language', false);

        if ($usePerLanguage) {
            $path = $locale === 'ar' ? Setting::get('logo_image_ar', '') : Setting::get('logo_image_en', '');
        } else {
            $path = Setting::get('logo_image', '');
        }

        if (empty($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Get the logo text for the current locale.
     */
    public static function getLogoText(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $usePerLanguage = (bool) Setting::get('logo_use_per_language', false);

        if ($usePerLanguage) {
            $text = $locale === 'ar' ? Setting::get('logo_text_ar', '') : Setting::get('logo_text_en', '');
        } else {
            $text = Setting::get('logo_text', '');
        }

        return $text !== '' ? $text : __('app.name');
    }

    /**
     * Get the logo alt text for the current locale (SEO and accessibility).
     * Falls back to logo text when alt is empty.
     */
    public static function getLogoAlt(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $usePerLanguage = (bool) Setting::get('logo_use_per_language', false);

        if ($usePerLanguage) {
            $alt = $locale === 'ar' ? Setting::get('logo_alt_ar', '') : Setting::get('logo_alt_en', '');
        } else {
            $alt = Setting::get('logo_alt', '');
        }

        return $alt !== '' ? $alt : self::getLogoText($locale);
    }
}
