<?php

namespace App\Jobs;

use App\Models\Drink;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateDrinkEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Drink $drink) {}

    /**
     * Gọi OpenAI Embeddings trên name + ingredients + tags (SPEC mục 2.5).
     * Logic EmbeddingService sẽ được điền ở bước Recommendation.
     */
    public function handle(): void
    {
        $drink = $this->drink->fresh();

        if ($drink === null) {
            return;
        }

        // TODO: EmbeddingService::embed() khi OpenAI được tích hợp.
    }
}
