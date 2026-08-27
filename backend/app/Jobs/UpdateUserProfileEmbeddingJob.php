<?php

namespace App\Jobs;

use App\Models\UserPreference;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateUserProfileEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserPreference $preference) {}

    /**
     * Gọi OpenAI Embeddings trên profile_text tổng hợp (SPEC mục 2.5).
     * Logic EmbeddingService sẽ được điền ở bước Recommendation.
     */
    public function handle(): void
    {
        $preference = $this->preference->fresh();

        if ($preference === null || $preference->profile_text === null) {
            return;
        }

        // TODO: EmbeddingService::embed() khi OpenAI được tích hợp.
    }
}
