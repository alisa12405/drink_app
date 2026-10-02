<?php

namespace Tests\Unit\Services;

use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    public function test_cosine_similarity_handles_valid_and_invalid_vectors(): void
    {
        $service = new EmbeddingService;

        $this->assertEqualsWithDelta(1.0, $service->cosineSimilarity([1, 2], [1, 2]), 0.00001);
        $this->assertEqualsWithDelta(0.0, $service->cosineSimilarity([1, 0], [0, 1]), 0.00001);
        $this->assertEqualsWithDelta(-1.0, $service->cosineSimilarity([1, 0], [-1, 0]), 0.00001);
        $this->assertSame(0.0, $service->cosineSimilarity([], []));
        $this->assertSame(0.0, $service->cosineSimilarity([1], [1, 2]));
        $this->assertSame(0.0, $service->cosineSimilarity([0, 0], [1, 2]));
        $this->assertSame(0.0, $service->cosineSimilarity(['invalid'], [1]));
    }

    public function test_embed_returns_validated_float_vector(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.test/v1',
            'services.openai.embedding_dimensions' => 3,
        ]);
        Http::fake([
            'api.openai.test/v1/embeddings' => Http::response([
                'data' => [['embedding' => [0.1, 2, -0.3]]],
            ]),
        ]);

        $this->assertSame([0.1, 2.0, -0.3], (new EmbeddingService)->embed('sample'));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request['model'] === 'text-embedding-3-small'
            && $request['input'] === 'sample'
            && $request['dimensions'] === 3);
    }

    public function test_embed_rejects_malformed_response(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.test/v1',
            'services.openai.embedding_dimensions' => 3,
        ]);
        Http::fake(['*' => Http::response(['data' => [['embedding' => [0.1]]]])]);

        $this->expectException(RuntimeException::class);

        (new EmbeddingService)->embed('sample');
    }
}
