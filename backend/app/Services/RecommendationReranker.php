<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class RecommendationReranker
{
    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $tasteTags
     * @return array{summary: string, recommendations: array<int, array{drink_id: int, explanation: string}>}|null
     */
    public function rerank(array $candidates, array $context, array $tasteTags = []): ?array
    {
        $key = (string) config('services.openai.key');
        if ($key === '' || $candidates === [] || Cache::get('openai:reranker:unavailable') === true) {
            return null;
        }

        $input = [
            'context' => array_diff_key($context, ['suggestion' => true]),
            'taste_tags' => array_slice($tasteTags, 0, 5),
            'candidates' => array_map(static fn (array $candidate): array => [
                'drink_id' => $candidate['drink_id'],
                'name' => $candidate['name'],
                'category' => $candidate['category'],
                'tags' => $candidate['tags'],
                'temperature_type' => $candidate['temperature_type'],
                'score' => $candidate['score'],
            ], $candidates),
        ];

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->withToken($key)
                ->acceptJson()
                ->timeout((int) config('services.openai.rerank_timeout', 3))
                ->connectTimeout(2)
                ->post('/responses', [
                    'model' => config('services.openai.rerank_model'),
                    'store' => false,
                    'instructions' => 'Chọn tối đa 5 đồ uống phù hợp nhất. Chỉ dùng drink_id trong candidates. Viết summary là một câu tiếng Việt ngắn (tối đa 30 từ) giải thích vì sao nhóm món được chọn, dựa trên taste_tags nếu có và ngữ cảnh thực tế. Nếu temperature >= 28°C thì gọi là trời nóng, <= 20°C là trời lạnh; không gọi nhiệt độ cao là mát mẻ. Viết explanation ngắn cho từng món. Không đưa tuyên bố an toàn y tế.',
                    'input' => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'max_output_tokens' => 700,
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'drink_recommendations',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'summary' => ['type' => 'string'],
                                    'recommendations' => [
                                        'type' => 'array',
                                        'maxItems' => 5,
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'drink_id' => ['type' => 'integer'],
                                                'explanation' => ['type' => 'string'],
                                            ],
                                            'required' => ['drink_id', 'explanation'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                ],
                                'required' => ['summary', 'recommendations'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                ])
                ->throw();
        } catch (Throwable $exception) {
            Cache::put('openai:reranker:unavailable', true, 300);

            throw $exception;
        }

        $text = $this->outputText($response->json());
        $decoded = json_decode($text, true);
        if (! is_array($decoded) || ! is_array($decoded['recommendations'] ?? null) || ! is_string($decoded['summary'] ?? null)) {
            throw new RuntimeException('OpenAI returned an invalid recommendation payload.');
        }

        return [
            'summary' => mb_substr(trim($decoded['summary']), 0, 180),
            'recommendations' => $decoded['recommendations'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function outputText(array $payload): string
    {
        foreach ($payload['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        throw new RuntimeException('OpenAI response did not contain output text.');
    }
}
