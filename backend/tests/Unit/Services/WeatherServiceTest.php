<?php

namespace Tests\Unit\Services;

use App\Services\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.weather.provider' => 'openweather',
            'services.weather.key' => 'weather-test-key',
            'services.weather.cache_ttl' => 1800,
        ]);
    }

    public function test_it_normalizes_and_caches_openweather_response(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'weather' => [['main' => 'Clouds']],
                'main' => ['temp' => 29.4],
            ]),
        ]);

        $service = new WeatherService;
        $first = $service->getCurrentWeather(10.77691, 106.70091);
        $second = $service->getCurrentWeather(10.77692, 106.70092);

        $this->assertSame('cloudy', $first['weather']);
        $this->assertSame(29.4, $first['temperature']);
        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_it_returns_null_when_provider_fails(): void
    {
        Http::fake(['*' => Http::response(['message' => 'unauthorized'], 401)]);

        $this->assertNull((new WeatherService)->getCurrentWeather(10.77, 106.70));
        Http::assertSentCount(1);
    }
}
