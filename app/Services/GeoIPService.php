<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Queries ip-api.com for country/city data from an IP address.
 * Mirrors WP's wz_geo_from_ip() function.
 *
 * ip-api.com free plan allows ~45 req/min from the same IP.
 * Responses are cached per IP for 24 hours to avoid hitting rate limits.
 */
class GeoIPService
{
    private const API_URL = 'http://ip-api.com/json/';
    private const CACHE_TTL = 86400; // 24 hours in seconds

    /**
     * Returns ['country' => '...', 'city' => '...'] or empty strings on failure.
     *
     * @param  string  $ip  IPv4 or IPv6 address
     * @return array{country: string, city: string}
     */
    public function lookup(string $ip): array
    {
        // Skip lookups for private/reserved IPs
        if ($this->isPrivateIp($ip)) {
            return ['country' => '', 'city' => ''];
        }

        $cacheKey = 'geoip:' . md5($ip);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($ip) {
            return $this->fetchFromApi($ip);
        });
    }

    private function fetchFromApi(string $ip): array
    {
        try {
            $response = Http::timeout(3)
                ->get(self::API_URL . urlencode($ip), [
                    'fields' => 'status,country,city',
                    'lang'   => 'en',
                ]);

            if (! $response->successful()) {
                return ['country' => '', 'city' => ''];
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'success') {
                return ['country' => '', 'city' => ''];
            }

            return [
                'country' => $data['country'] ?? '',
                'city'    => $data['city']    ?? '',
            ];
        } catch (\Throwable $e) {
            Log::warning('GeoIPService: lookup failed', [
                'ip'    => $ip,
                'error' => $e->getMessage(),
            ]);

            return ['country' => '', 'city' => ''];
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        // Covers localhost, RFC1918 private ranges, link-local, loopback
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
