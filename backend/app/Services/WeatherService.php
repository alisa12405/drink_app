<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    /**
     * @return array{weather: string, temperature: float, provider: string}|null
     */
    public function getCurrentWeather(float $lat, float $lon): ?array
    {
        $provider = strtolower((string) config('services.weather.provider', 'openweather'));
        $cacheKey = $this->cacheKey($provider, $lat, $lon);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            if (($cached['unavailable'] ?? false) === true) {
                return null;
            }

            return $cached;
        }

        $startedAt = microtime(true);

        try {
            $weather = $this->fetch($provider, $lat, $lon);
            if ($weather !== null) {
                Cache::put($cacheKey, $weather, (int) config('services.weather.cache_ttl', 1800));
            }

            return $weather;
        } catch (Throwable $exception) {
            Cache::put($cacheKey, ['unavailable' => true], 300);
            Log::warning('Weather provider request failed.', [
                'provider' => $provider,
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'coordinate_bucket' => sprintf('%.2f,%.2f', $lat, $lon),
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    private function cacheKey(string $provider, float $lat, float $lon): string
    {
        $bucket = intdiv(now()->timestamp, 1800);

        return sprintf('weather:%s:%.2f:%.2f:%d', $provider, $lat, $lon, $bucket);
    }

    /**
     * @return array{weather: string, temperature: float, provider: string}|null
     */
    private function fetch(string $provider, float $lat, float $lon): ?array
    {
        $key = (string) config('services.weather.key');
        if ($key === '') {
            return null;
        }

        return match ($provider) {
            'openweather', 'openweathermap' => $this->fetchOpenWeather($key, $lat, $lon),
            'weatherapi', 'weatherapi.com' => $this->fetchWeatherApi($key, $lat, $lon),
            default => null,
        };
    }

    /**
     * @return array{weather: string, temperature: float, provider: string}
     */
    private function fetchOpenWeather(string $key, float $lat, float $lon): array
    {
        $response = $this->getWithRetry('https://api.openweathermap.org/data/2.5/weather', [
            'lat' => $lat,
            'lon' => $lon,
            'units' => 'metric',
            'appid' => $key,
        ]);

        return [
            'weather' => $this->normalize((string) $response->json('weather.0.main')),
            'temperature' => (float) $response->json('main.temp'),
            'provider' => 'openweather',
        ];
    }

    /**
     * @return array{weather: string, temperature: float, provider: string}
     */
    private function fetchWeatherApi(string $key, float $lat, float $lon): array
    {
        $response = $this->getWithRetry('https://api.weatherapi.com/v1/current.json', [
            'key' => $key,
            'q' => "{$lat},{$lon}",
        ]);

        return [
            'weather' => $this->normalize((string) $response->json('current.condition.text')),
            'temperature' => (float) $response->json('current.temp_c'),
            'provider' => 'weatherapi',
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     *
     * @throws ConnectionException
     */
    private function getWithRetry(string $url, array $query): Response
    {
        $lastResponse = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::acceptJson()
                    ->timeout((int) config('services.weather.timeout', 3))
                    ->connectTimeout(2)
                    ->get($url, $query);
            } catch (ConnectionException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }

                continue;
            }

            $lastResponse = $response;
            if ($response->status() !== 429 && $response->status() < 500) {
                return $response->throw();
            }
        }

        return $lastResponse->throw();
    }

    private function normalize(string $condition): string
    {
        $condition = strtolower($condition);

        return match (true) {
            str_contains($condition, 'thunder'), str_contains($condition, 'storm') => 'storm',
            str_contains($condition, 'rain'), str_contains($condition, 'drizzle') => 'rain',
            str_contains($condition, 'mist'), str_contains($condition, 'fog'), str_contains($condition, 'haze') => 'mist',
            str_contains($condition, 'cloud'), str_contains($condition, 'overcast') => 'cloudy',
            str_contains($condition, 'clear'), str_contains($condition, 'sunny') => 'clear',
            default => 'unknown',
        };
    }
}
