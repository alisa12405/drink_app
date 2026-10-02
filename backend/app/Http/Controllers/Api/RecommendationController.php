<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recommendation\GetRecommendationRequest;
use App\Http\Resources\RecommendationResource;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendationService) {}

    public function context(GetRecommendationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => $this->recommendationService->context(
                isset($validated['lat']) ? (float) $validated['lat'] : null,
                isset($validated['lon']) ? (float) $validated['lon'] : null,
            ),
        ]);
    }

    public function index(GetRecommendationRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $result = $this->recommendationService->recommend(
            $request->user(),
            isset($validated['lat']) ? (float) $validated['lat'] : null,
            isset($validated['lon']) ? (float) $validated['lon'] : null,
            $validated['occasion'] ?? null,
        );

        return RecommendationResource::collection($result['recommendations'])
            ->additional(['meta' => $result['meta']]);
    }
}
