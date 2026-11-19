<?php

namespace Tests\Feature\Shipping;

use App\Services\MapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_geocoding_mengembalikan_koordinat_dari_nominatim(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-6.9656495',
                    'lon' => '110.4271369',
                    'display_name' => 'Semarang, Jawa Tengah, Indonesia',
                ],
            ]),
        ]);

        $point = app(MapService::class)->geocode('Semarang, Jawa Tengah, Indonesia 50131');

        $this->assertNotNull($point);
        $this->assertEquals(-6.9656495, $point['lat']);
        $this->assertEquals(110.4271369, $point['lng']);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'nominatim.openstreetmap.org/search')
                && str_contains(implode(' ', $request->header('User-Agent')), 'TabitaModiste');
        });
    }

    public function test_hasil_geocoding_di_cache(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '-7.0', 'lon' => '110.4'],
            ]),
        ]);

        $service = app(MapService::class);
        $service->geocode('Semarang');
        $service->geocode('Semarang');

        Http::assertSentCount(1);
    }

    public function test_route_mengembalikan_array_titik_dari_osrm(): void
    {
        Http::fake([
            'router.project-osrm.org/route/v1/driving/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'geometry' => [
                        'coordinates' => [
                            [110.4, -7.0],
                            [110.5, -6.9],
                        ],
                    ],
                ]],
            ]),
        ]);

        $path = app(MapService::class)->route(
            ['lat' => -7.0, 'lng' => 110.4],
            ['lat' => -6.9, 'lng' => 110.5]
        );

        $this->assertCount(2, $path);
        $this->assertEquals(-7.0, $path[0]['lat']);
        $this->assertEquals(110.5, $path[1]['lng']);
    }

    public function test_route_fallback_garis_lurus_saat_osrm_gagal(): void
    {
        Http::fake([
            'router.project-osrm.org/route/v1/driving/*' => Http::response(['code' => 'NoRoute'], 500),
        ]);

        $path = app(MapService::class)->route(
            ['lat' => -7.0, 'lng' => 110.4],
            ['lat' => -6.9, 'lng' => 110.5]
        );

        $this->assertCount(2, $path);
        $this->assertEquals(-7.0, $path[0]['lat']);
        $this->assertEquals(-6.9, $path[1]['lat']);
    }
}
