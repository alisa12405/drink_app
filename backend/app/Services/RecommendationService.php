<?php

namespace App\Services;

use App\Enums\TemperatureType;
use App\Models\Drink;
use App\Models\RecommendationLog;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecommendationService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly WeatherService $weatherService,
        private readonly RecommendationReranker $reranker,
    ) {}

    /**
     * Public, non-personalized context shown before a user requests recommendations.
     *
     * @return array<string, mixed>
     */
    public function context(?float $lat = null, ?float $lon = null): array
    {
        $weather = $lat !== null && $lon !== null
            ? $this->weatherService->getCurrentWeather($lat, $lon)
            : null;
        $now = now((string) config('services.recommendation.timezone', 'Asia/Ho_Chi_Minh'));

        return [
            'current_time' => $now->format('H:i'),
            'current_date' => $now->format('d/m/Y'),
            'hour' => (int) $now->format('G'),
            'weather' => $weather['weather'] ?? null,
            'temperature' => $weather['temperature'] ?? null,
            'location_used' => $lat !== null && $lon !== null,
            'suggestion' => $this->basicSuggestion(
                (int) $now->format('G'),
                $weather['temperature'] ?? null,
            ),
        ];
    }

    /**
     * @return array{recommendations: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function recommend(
        User $user,
        ?float $lat = null,
        ?float $lon = null,
        ?string $occasion = null,
    ): array {
        $context = $this->context($lat, $lon);
        $context['occasion'] = $occasion;

        $allDrinks = Drink::query()->available()->orderBy('id')->get();
        if ($allDrinks->isEmpty()) {
            return $this->emptyResult($context);
        }

        $drinks = $this->temperatureCandidates($allDrinks, $context['temperature']);
        $preference = $user->preference()->first();
        $profileVector = $preference?->profile_embedding ?? [];
        $hasSemanticRanking = $this->hasUsableSemanticData($profileVector, $drinks);
        $highRatedIds = $user->ratings()->where('rating', '>=', 4)->pluck('drink_id')->all();

        $candidates = $drinks
            ->map(function (Drink $drink) use ($profileVector, $preference, $highRatedIds, $context, $hasSemanticRanking): array {
                $score = $hasSemanticRanking
                    ? $this->embeddingService->cosineSimilarity($profileVector, $drink->description_embedding ?? [])
                    : $this->ruleScore($drink, $preference, $highRatedIds, $context['temperature']);

                return [
                    'drink' => $drink,
                    'drink_id' => $drink->id,
                    'name' => $drink->name,
                    'category' => $drink->category,
                    'tags' => $drink->tags ?? [],
                    'temperature_type' => $drink->temperature_type->value,
                    'score' => round($score, 4),
                ];
            })
            ->sort(fn (array $left, array $right): int => $right['score'] <=> $left['score'] ?: $left['drink_id'] <=> $right['drink_id'])
            ->take(10)
            ->values();

        $strategy = $hasSemanticRanking
            ? 'semantic_fallback'
            : ($preference === null ? 'popular_fallback' : 'profile_rule_fallback');
        $llmRanked = null;

        try {
            $llmRanked = $this->reranker->rerank($candidates->all(), $context);
        } catch (Throwable $exception) {
            Log::warning('Recommendation rerank failed; fallback used.', [
                'exception' => $exception::class,
                'candidate_count' => $candidates->count(),
            ]);
        }

        [$rankedCandidates, $explanations] = $this->finalRanking($candidates, $llmRanked, $context);
        if ($llmRanked !== null) {
            $strategy = 'hybrid_llm';
        }

        $recommendations = $rankedCandidates->values()->map(
            fn (array $candidate, int $index): array => [
                'rank' => $index + 1,
                'drink' => $candidate['drink'],
                'score' => $candidate['score'],
                'explanation' => $explanations[$candidate['drink_id']] ?? $this->fallbackExplanation($candidate, $context),
            ],
        )->all();

        $logId = $this->writeLog(
            $user,
            $context,
            $candidates,
            $rankedCandidates,
            $llmRanked === null ? null : $explanations,
        );

        return [
            'recommendations' => $recommendations,
            'meta' => [
                'strategy' => $strategy,
                'fallback' => $strategy !== 'hybrid_llm',
                'context' => $context,
                'recommendation_log_id' => $logId,
                'reason_summary' => $this->reasonSummary($strategy, $context, $preference),
            ],
        ];
    }

    private function basicSuggestion(int $hour, mixed $temperature): string
    {
        if (is_numeric($temperature) && (float) $temperature >= 28) {
            return 'Trời đang khá nóng, bạn có thể ưu tiên một món lạnh hoặc trà trái cây thanh mát.';
        }

        if (is_numeric($temperature) && (float) $temperature <= 20) {
            return 'Thời tiết se lạnh, một món nóng hoặc vị đậm sẽ dễ thưởng thức hơn.';
        }

        return match (true) {
            $hour < 10 => 'Buổi sáng thích hợp với cà phê, latte hoặc một món có vị thanh nhẹ.',
            $hour < 14 => 'Giữa ngày, bạn có thể chọn một món mát và vừa ngọt để cân bằng năng lượng.',
            $hour < 18 => 'Buổi chiều là lúc phù hợp để thử trà, cà phê hoặc một món ít ngọt.',
            default => 'Buổi tối, bạn có thể ưu tiên món nhẹ, ít caffeine và dễ thư giãn.',
        };
    }

    private function reasonSummary(
        string $strategy,
        array $context,
        ?UserPreference $preference,
    ): string {
        $parts = [];
        $tasteTags = array_values(array_filter($preference?->taste_tags ?? []));

        if ($tasteTags !== []) {
            $parts[] = 'sở thích '.implode(', ', array_slice($tasteTags, 0, 3));
        }
        if (is_numeric($context['temperature'] ?? null)) {
            $parts[] = 'thời tiết '.number_format((float) $context['temperature'], 1).'°C';
        }
        if (! empty($context['occasion'])) {
            $parts[] = 'ngữ cảnh '.$context['occasion'];
        }

        $basis = $parts === []
            ? 'thời điểm hiện tại và những món nổi bật trong menu'
            : implode(', ', $parts);

        return $strategy === 'hybrid_llm'
            ? "Top 5 được chọn và sắp xếp lại dựa trên {$basis}. Mỗi món có một lý do riêng ở bên dưới."
            : "Top 5 được chọn theo {$basis}. Hệ thống đang dùng phương án dự phòng an toàn.";
    }

    /**
     * @param  Collection<int, Drink>  $drinks
     * @return Collection<int, Drink>
     */
    private function temperatureCandidates(Collection $drinks, mixed $temperature): Collection
    {
        if (! is_numeric($temperature)) {
            return $drinks;
        }

        $preferred = match (true) {
            (float) $temperature >= 28 => TemperatureType::Cold,
            (float) $temperature <= 20 => TemperatureType::Hot,
            default => null,
        };

        if ($preferred === null) {
            return $drinks;
        }

        $filtered = $drinks->filter(
            fn (Drink $drink): bool => in_array($drink->temperature_type, [$preferred, TemperatureType::Both], true),
        );

        return $filtered->count() >= 5 ? $filtered->values() : $drinks;
    }

    /**
     * @param  float[]  $profileVector
     * @param  Collection<int, Drink>  $drinks
     */
    private function hasUsableSemanticData(array $profileVector, Collection $drinks): bool
    {
        if ($profileVector === []) {
            return false;
        }

        return $drinks->contains(fn (Drink $drink): bool => is_array($drink->description_embedding)
            && count($drink->description_embedding) === count($profileVector));
    }

    /**
     * @param  int[]  $highRatedIds
     */
    private function ruleScore(
        Drink $drink,
        ?UserPreference $preference,
        array $highRatedIds,
        mixed $temperature,
    ): float {
        $matchingTags = count(array_intersect($preference?->taste_tags ?? [], $drink->tags ?? []));
        $score = min(0.5, $matchingTags * 0.25);

        if (in_array('best_seller', $drink->tags ?? [], true)) {
            $score += 0.1;
        }

        if (in_array($drink->id, $highRatedIds, true)) {
            $score += 0.3;
        }

        if (is_numeric($temperature)) {
            $preferred = (float) $temperature >= 28
                ? TemperatureType::Cold
                : ((float) $temperature <= 20 ? TemperatureType::Hot : null);
            if ($preferred !== null && in_array($drink->temperature_type, [$preferred, TemperatureType::Both], true)) {
                $score += 0.1;
            }
        }

        return min(1.0, $score);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $candidates
     * @param  array<int, array{drink_id: int, explanation: string}>|null  $llmRanked
     * @param  array<string, mixed>  $context
     * @return array{Collection<int, array<string, mixed>>, array<int, string>}
     */
    private function finalRanking(Collection $candidates, ?array $llmRanked, array $context): array
    {
        $byId = $candidates->keyBy('drink_id');
        $selected = collect();
        $explanations = [];

        foreach ($llmRanked ?? [] as $item) {
            $id = filter_var($item['drink_id'] ?? null, FILTER_VALIDATE_INT);
            $explanation = trim((string) ($item['explanation'] ?? ''));
            if ($id === false || ! $byId->has($id) || $selected->contains('drink_id', $id) || $explanation === '') {
                continue;
            }

            $selected->push($byId->get($id));
            $explanations[$id] = mb_substr($explanation, 0, 240);
            if ($selected->count() === 5) {
                break;
            }
        }

        foreach ($candidates as $candidate) {
            if ($selected->count() === 5) {
                break;
            }
            if (! $selected->contains('drink_id', $candidate['drink_id'])) {
                $selected->push($candidate);
                $explanations[$candidate['drink_id']] ??= $this->fallbackExplanation($candidate, $context);
            }
        }

        return [$selected, $explanations];
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @param  array<string, mixed>  $context
     */
    private function fallbackExplanation(array $candidate, array $context): string
    {
        if (is_numeric($context['temperature'] ?? null)) {
            return (float) $context['temperature'] >= 28
                ? 'Một lựa chọn mát lạnh, phù hợp với thời tiết hiện tại.'
                : 'Một lựa chọn phù hợp với thời tiết và sở thích của bạn.';
        }

        return $candidate['score'] > 0
            ? 'Phù hợp với sở thích và những lựa chọn trước đây của bạn.'
            : 'Một món nổi bật trong menu mà bạn có thể muốn thử.';
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  Collection<int, array<string, mixed>>  $candidates
     * @param  Collection<int, array<string, mixed>>  $rankedCandidates
     * @param  array<int, string>|null  $explanations
     */
    private function writeLog(
        User $user,
        array $context,
        Collection $candidates,
        Collection $rankedCandidates,
        ?array $explanations,
    ): ?int {
        if ($rankedCandidates->isEmpty()) {
            return null;
        }

        try {
            return RecommendationLog::query()->create([
                'user_id' => $user->id,
                'context_snapshot' => $context,
                'candidate_drink_ids' => $candidates->pluck('drink_id')->all(),
                'final_ranked_ids' => $rankedCandidates->pluck('drink_id')->all(),
                'llm_explanation' => $explanations === null
                    ? null
                    : json_encode($explanations, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ])->id;
        } catch (Throwable $exception) {
            Log::warning('Recommendation log could not be stored.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{recommendations: array<int, never>, meta: array<string, mixed>}
     */
    private function emptyResult(array $context): array
    {
        return [
            'recommendations' => [],
            'meta' => [
                'strategy' => 'empty_menu',
                'fallback' => true,
                'context' => $context,
                'recommendation_log_id' => null,
                'reason_summary' => 'Menu hiện chưa có món khả dụng để tạo gợi ý.',
            ],
        ];
    }
}
