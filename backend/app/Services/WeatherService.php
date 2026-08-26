<?php

namespace App\Services;

/**
 * Goi Weather API theo toa do, cache ket qua theo toa do + khung gio (TTL ~30 phut).
 * Xem SPEC_smart-drink-recommendation-app.md muc 2.4 (buoc 2), muc 3.
 */
class WeatherService
{
    /**
     * Lay thoi tiet/nhiet do hien tai theo toa do. Fallback null neu khong co quyen vi tri
     * hoac goi API loi (theo Business Rule muc 1.5).
     *
     * @return array{weather: string, temperature: float}|null
     */
    public function getCurrentWeather(float $lat, float $lon): ?array
    {
        // TODO: goi Weather API, cache Redis theo toa do (TTL ~30 phut), try/catch fallback null.
        return null;
    }
}
