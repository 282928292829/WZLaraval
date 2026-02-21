<?php

namespace App\Services;

/**
 * Parses a User-Agent string into structured device/browser/OS metadata.
 * Mirrors WP's wz_parse_browser() and wz_parse_device() functions.
 */
class UserAgentParser
{
    private string $ua;

    public function __construct(string $userAgent)
    {
        $this->ua = $userAgent;
    }

    /** Return all parsed fields as an array. */
    public function parse(): array
    {
        return [
            'browser'         => $this->browser(),
            'browser_version' => $this->browserVersion(),
            'device'          => $this->deviceType(),
            'device_model'    => $this->deviceModel(),
            'os'              => $this->os(),
            'os_version'      => $this->osVersion(),
        ];
    }

    public function browser(): string
    {
        $ua = $this->ua;

        if (str_contains($ua, 'Edg/') || str_contains($ua, 'Edge/')) {
            return 'Edge';
        }
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) {
            return 'Opera';
        }
        if (str_contains($ua, 'SamsungBrowser/')) {
            return 'Samsung Browser';
        }
        if (str_contains($ua, 'UCBrowser/')) {
            return 'UC Browser';
        }
        if (str_contains($ua, 'YaBrowser/')) {
            return 'Yandex';
        }
        if (str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Chromium')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'Chromium/')) {
            return 'Chromium';
        }
        if (str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome')) {
            return 'Safari';
        }
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident/')) {
            return 'Internet Explorer';
        }

        return 'Unknown';
    }

    public function browserVersion(): string
    {
        $ua      = $this->ua;
        $browser = $this->browser();

        $patterns = [
            'Edge'            => '/(?:Edg|Edge)\/(\S+)/',
            'Opera'           => '/(?:OPR|Opera)\/(\S+)/',
            'Samsung Browser' => '/SamsungBrowser\/(\S+)/',
            'UC Browser'      => '/UCBrowser\/(\S+)/',
            'Yandex'          => '/YaBrowser\/(\S+)/',
            'Chrome'          => '/Chrome\/(\S+)/',
            'Chromium'        => '/Chromium\/(\S+)/',
            'Firefox'         => '/(?:Firefox|FxiOS)\/(\S+)/',
            'Safari'          => '/Version\/(\S+)/',
            'Internet Explorer' => '/(?:MSIE |rv:)(\S+)/',
        ];

        if (isset($patterns[$browser]) && preg_match($patterns[$browser], $ua, $m)) {
            return rtrim($m[1], ';,)');
        }

        return '';
    }

    public function deviceType(): string
    {
        $ua = strtolower($this->ua);

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (
            str_contains($ua, 'mobile')   ||
            str_contains($ua, 'android')  ||
            str_contains($ua, 'iphone')   ||
            str_contains($ua, 'ipod')     ||
            str_contains($ua, 'blackberry') ||
            str_contains($ua, 'windows phone')
        ) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function deviceModel(): string
    {
        $ua = $this->ua;

        // iPhone model
        if (preg_match('/iPhone OS ([\d_]+)/', $ua)) {
            if (preg_match('/iPhone(\d+),(\d+)/', $ua, $m)) {
                return $this->iphoneModelFromIdentifier((int) $m[1], (int) $m[2]);
            }
            return 'iPhone';
        }

        // iPad model
        if (str_contains($ua, 'iPad')) {
            return 'iPad';
        }

        // Samsung Galaxy
        if (preg_match('/Samsung[- ]?(\w[\w ]+)/i', $ua, $m)) {
            return 'Samsung ' . trim($m[1]);
        }
        if (preg_match('/SM-([A-Z0-9]+)/i', $ua, $m)) {
            return 'Samsung SM-' . strtoupper($m[1]);
        }

        // Huawei
        if (preg_match('/(?:Huawei|HW[- ]?)([A-Z0-9\-]+)/i', $ua, $m)) {
            return 'Huawei ' . strtoupper($m[1]);
        }

        // Pixel
        if (preg_match('/Pixel (\d+[A-Za-z]*)/i', $ua, $m)) {
            return 'Google Pixel ' . $m[1];
        }

        // Generic Android device name
        if (preg_match('/;\s*([^;)]+)\s+Build\//i', $ua, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    public function os(): string
    {
        $ua = $this->ua;

        if (str_contains($ua, 'Windows Phone')) {
            return 'Windows Phone';
        }
        if (str_contains($ua, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'iPhone OS') || str_contains($ua, 'iPad')) {
            return 'iOS';
        }
        if (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) {
            return 'macOS';
        }
        if (str_contains($ua, 'Android')) {
            return 'Android';
        }
        if (str_contains($ua, 'Linux')) {
            return 'Linux';
        }
        if (str_contains($ua, 'CrOS')) {
            return 'ChromeOS';
        }

        return 'Unknown';
    }

    public function osVersion(): string
    {
        $ua = $this->ua;
        $os = $this->os();

        $patterns = [
            'Windows Phone' => '/Windows Phone ([\d.]+)/',
            'Windows'       => '/Windows NT ([\d.]+)/',
            'iOS'           => '/(?:iPhone|iPad) OS ([\d_]+)/',
            'macOS'         => '/Mac OS X ([\d_.]+)/',
            'Android'       => '/Android ([\d.]+)/',
            'ChromeOS'      => '/CrOS \S+ ([\d.]+)/',
        ];

        if (isset($patterns[$os]) && preg_match($patterns[$os], $ua, $m)) {
            $ver = str_replace('_', '.', $m[1]);

            // Map Windows NT version → marketing version
            if ($os === 'Windows') {
                $ver = match ($ver) {
                    '10.0' => '10/11',
                    '6.3'  => '8.1',
                    '6.2'  => '8',
                    '6.1'  => '7',
                    '6.0'  => 'Vista',
                    '5.2'  => 'XP x64',
                    '5.1'  => 'XP',
                    default => $ver,
                };
            }

            return $ver;
        }

        return '';
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function iphoneModelFromIdentifier(int $major, int $minor): string
    {
        // Approximate mapping — covers most common models
        return match (true) {
            $major === 14 && $minor <= 2  => 'iPhone 6',
            $major === 14 && $minor <= 5  => 'iPhone 6 Plus',
            $major === 15 && $minor <= 2  => 'iPhone 6s',
            $major === 15 && $minor <= 4  => 'iPhone 6s Plus',
            $major === 16 && $minor === 1 => 'iPhone SE (1st gen)',
            $major === 16 && $minor <= 4  => 'iPhone 7',
            $major === 16 && $minor <= 6  => 'iPhone 7 Plus',
            $major === 17 && $minor <= 4  => 'iPhone 8',
            $major === 17 && $minor <= 6  => 'iPhone 8 Plus',
            $major === 10 && $minor <= 3  => 'iPhone X',
            $major === 11 && $minor <= 2  => 'iPhone XS',
            $major === 11 && $minor === 4 => 'iPhone XS Max',
            $major === 11 && $minor === 6 => 'iPhone XR',
            $major === 12 && $minor <= 3  => 'iPhone 11 Pro',
            $major === 12 && $minor <= 5  => 'iPhone 11 Pro Max',
            $major === 12 && $minor === 8 => 'iPhone SE (2nd gen)',
            $major === 13 && $minor <= 2  => 'iPhone 12 mini',
            $major === 13 && $minor <= 4  => 'iPhone 12',
            $major === 13 && $minor <= 6  => 'iPhone 12 Pro',
            $major === 14 && $minor <= 8  => 'iPhone 12 Pro Max',
            $major === 13 && $minor === 8 => 'iPhone 13 mini',
            $major === 14 && $minor <= 6  => 'iPhone 13',
            $major === 14 && $minor <= 8  => 'iPhone 13 Pro',
            $major === 15 && $minor <= 8  => 'iPhone 14',
            $major === 16 && $minor <= 8  => 'iPhone 15',
            $major === 17 && $minor <= 8  => 'iPhone 15 Pro',
            default                       => "iPhone ({$major},{$minor})",
        };
    }
}
