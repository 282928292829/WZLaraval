<?php

namespace App\Support;

use App\Models\Setting;

class FontHelper
{
    /**
     * Get the font-family CSS value for the current locale.
     * Uses font_family_ar / font_family_en if set, else single font_family.
     */
    public static function cssFontFamily(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $defaultAr = 'IBM Plex Sans Arabic';
        $defaultEn = 'Inter';

        $fontAr = Setting::get('font_family_ar', '') ?: Setting::get('font_family', $defaultAr);
        $fontEn = Setting::get('font_family_en', '') ?: Setting::get('font_family', $defaultEn);

        $font = $locale === 'ar' ? $fontAr : $fontEn;

        return "'{$font}', ui-sans-serif, system-ui, sans-serif";
    }
}
