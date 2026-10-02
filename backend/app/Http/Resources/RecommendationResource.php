<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->resource['rank'],
            'drink' => new DrinkResource($this->resource['drink']),
            'score' => $this->resource['score'],
            'explanation' => $this->resource['explanation'],
        ];
    }
}
