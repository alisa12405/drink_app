<?php

namespace App\Services;

/**
 * Goi OpenAI Embeddings API (text-embedding-3-small) va tinh cosine similarity.
 * Xem SPEC_smart-drink-recommendation-app.md muc 2.4, 2.5, 3.
 */
class EmbeddingService
{
    /**
     * Sinh vector embedding cho mot doan text (menu description hoac user profile_text).
     *
     * @return float[]
     */
    public function embed(string $text): array
    {
        // TODO: goi OpenAI Embeddings API (text-embedding-3-small), co try/catch + fallback.
        return [];
    }

    /**
     * Tinh do tuong dong ngu nghia giua 2 vector.
     */
    public function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        // TODO: implement cosine similarity thuan PHP (khong dung vector DB rieng).
        return 0.0;
    }
}
