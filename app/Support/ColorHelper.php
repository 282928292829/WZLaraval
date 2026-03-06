<?php

namespace App\Support;

class ColorHelper
{
    /**
     * Darken a hex color by a percentage (0–100).
     */
    public static function darken(string $hex, int $percent = 10): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6 && strlen($hex) !== 3) {
            return '#'.$hex;
        }
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = max(0, min(255, (int) round(hexdec(substr($hex, 0, 2)) * (1 - $percent / 100))));
        $g = max(0, min(255, (int) round(hexdec(substr($hex, 2, 2)) * (1 - $percent / 100))));
        $b = max(0, min(255, (int) round(hexdec(substr($hex, 4, 2)) * (1 - $percent / 100))));

        return '#'.sprintf('%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Lighten a hex color by mixing with white (0–100 = % of white to mix).
     */
    public static function lighten(string $hex, int $percent = 10): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6 && strlen($hex) !== 3) {
            return '#'.$hex;
        }
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $p = $percent / 100;
        $r = (int) round($r + (255 - $r) * $p);
        $g = (int) round($g + (255 - $g) * $p);
        $b = (int) round($b + (255 - $b) * $p);

        return '#'.sprintf('%02x%02x%02x', min(255, $r), min(255, $g), min(255, $b));
    }
}
