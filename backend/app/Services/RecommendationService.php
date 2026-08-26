<?php

namespace App\Services;

/**
 * Orchestrate luong goi y: pre-filter -> cosine similarity -> goi LLM re-rank.
 * Xem SPEC_smart-drink-recommendation-app.md muc 2.4 (buoc 1-8).
 */
class RecommendationService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected WeatherService $weatherService,
    ) {
    }

    /**
     * Sinh danh sach goi y ca nhan hoa cho user theo ngu canh hien tai.
     */
    public function recommend(int $userId, ?float $lat = null, ?float $lon = null): array
    {
        // TODO:
        // 1. Lay context (gio, thoi tiet, nhiet do) qua WeatherService.
        // 2. Pre-filter bang drinks theo context (temperature_type...).
        // 3. Tinh cosine similarity giua user_vector va tung drink_vector.
        // 4. Goi gpt-4o-mini de re-rank top candidate + sinh giai thich.
        // 5. Ghi log vao recommendation_logs.
        return [];
    }
}
