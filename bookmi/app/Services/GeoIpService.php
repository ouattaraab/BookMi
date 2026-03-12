<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoIpService
{
    /**
     * Returns ['country' => '...', 'city' => '...'] or empty array on failure.
     *
     * Uses ip-api.com free tier (45 req/min, no API key needed).
     * Results are cached for 24 hours to avoid rate-limiting.
     *
     * @return array{country: string, city: string}
     */
    public function lookup(string $ip): array
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return ['country' => 'Local', 'city' => 'localhost'];
        }

        return Cache::remember("geoip:{$ip}", now()->addDay(), function () use ($ip) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=country,city,status");
                if ($response->ok() && $response->json('status') === 'success') {
                    return [
                        'country' => $response->json('country', ''),
                        'city'    => $response->json('city', ''),
                    ];
                }
            } catch (\Throwable) {
                // Fail silently — GeoIP is best-effort
            }

            return ['country' => '', 'city' => ''];
        });
    }
}
