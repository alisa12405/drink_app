<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $key = (string) config('services.openai.key');
        if ($key === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $expectedDimensions = (int) config('services.openai.embedding_dimensions');
        $response = $this->postWithRetry('/embeddings', [
            'model' => config('services.openai.embedding_model'),
            'input' => $text,
            'encoding_format' => 'float',
            'dimensions' => $expectedDimensions,
        ], $key);

        $vector = $response->json('data.0.embedding');

        if (! is_array($vector) || count($vector) !== $expectedDimensions) {
            throw new RuntimeException('OpenAI returned an invalid embedding vector.');
        }

        foreach ($vector as $value) {
            if (! is_numeric($value) || ! is_finite((float) $value)) {
                throw new RuntimeException('OpenAI returned a non-numeric embedding value.');
            }
        }

        return array_map('floatval', $vector);
    }

    /**
     * Tinh do tuong dong ngu nghia giua 2 vector.
     */
    public function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        if ($vectorA === [] || count($vectorA) !== count($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($vectorA as $index => $valueA) {
            $valueB = $vectorB[$index] ?? null;
            if (! is_numeric($valueA) || ! is_numeric($valueB)) {
                return 0.0;
            }

            $a = (float) $valueA;
            $b = (float) $valueB;
            if (! is_finite($a) || ! is_finite($b)) {
                return 0.0;
            }

            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ConnectionException
     */
    private function postWithRetry(string $endpoint, array $payload, string $key): Response
    {
        $lastResponse = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                    ->withToken($key)
                    ->acceptJson()
                    ->timeout((int) config('services.openai.timeout'))
                    ->connectTimeout(3)
                    ->post($endpoint, $payload);
            } catch (ConnectionException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }

                $this->retryDelay($attempt);

                continue;
            }

            $lastResponse = $response;
            if ($response->status() !== 429 && $response->status() < 500) {
                return $response->throw();
            }

            if ($attempt < 2) {
                $this->retryDelay($attempt);
            }
        }

        return $lastResponse->throw();
    }

    private function retryDelay(int $attempt): void
    {
        if (! app()->environment('testing')) {
            usleep($attempt * 200_000);
        }
    }
}
