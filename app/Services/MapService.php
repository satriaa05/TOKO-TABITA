<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Peta gratis tanpa API key:
 * - Geocoding: OpenStreetMap Nominatim
 * - Routing: OSRM demo server
 * - Tile & JS: Leaflet + OpenStreetMap
 *
 * Semua hasil di-cache untuk mematuhi kebijakan penggunaan Nominatim.
 */
class MapService
{
    public function isConfigured(): bool
    {
        return true; // Tidak membutuhkan kredensial.
    }

    public function attribution(): string
    {
        return (string) config(
            'shipping.maps.attribution',
            '© OpenStreetMap contributors'
        );
    }

    /** @return array{lat: float, lng: float}|null */
    public function geocode(string $address): ?array
    {
        $address = trim($address);

        if ($address === '') {
            return null;
        }

        $cacheKey = 'osm.geocode.'.md5($address);

        return Cache::remember($cacheKey, 86400, function () use ($address) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                    'Accept-Language' => 'id',
                ])->timeout(12)->get((string) config('shipping.maps.geocode_url'), [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'id',
                    'q' => $address,
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $row = $response->json('0');

                if (! is_array($row) || ! isset($row['lat'], $row['lon'])) {
                    return null;
                }

                return [
                    'lat' => (float) $row['lat'],
                    'lng' => (float) $row['lon'],
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @param  array{lat: float, lng: float}  $origin
     * @param  array{lat: float, lng: float}  $destination
     * @return array<int, array{lat: float, lng: float}>|null
     */
    public function route(array $origin, array $destination): ?array
    {
        $cacheKey = 'osm.route.'.md5(
            $origin['lat'].','.$origin['lng'].'|'.$destination['lat'].','.$destination['lng']
        );

        return Cache::remember($cacheKey, 21600, function () use ($origin, $destination) {
            $straight = [
                ['lat' => $origin['lat'], 'lng' => $origin['lng']],
                ['lat' => $destination['lat'], 'lng' => $destination['lng']],
            ];

            try {
                $coordinates = $origin['lng'].','.$origin['lat'].';'.$destination['lng'].','.$destination['lat'];
                $base = rtrim((string) config('shipping.maps.route_url'), '/');

                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                ])->timeout(15)->get($base.'/'.$coordinates, [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                ]);

                if (! $response->successful() || $response->json('code') !== 'Ok') {
                    return $straight;
                }

                $points = (array) $response->json('routes.0.geometry.coordinates');

                if ($points === []) {
                    return $straight;
                }

                $path = [];

                foreach ($points as $point) {
                    if (! is_array($point) || ! isset($point[0], $point[1])) {
                        continue;
                    }

                    $path[] = ['lat' => (float) $point[1], 'lng' => (float) $point[0]];
                }

                return $path !== [] ? $path : $straight;
            } catch (\Throwable) {
                return $straight;
            }
        });
    }

    private function userAgent(): string
    {
        return (string) config(
            'shipping.maps.user_agent',
            'TabitaModiste/1.0 (local development)'
        );
    }
}
