<?php

namespace App\Jobs;

use App\Models\UserPreference;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateUserProfileEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public UserPreference $preference)
    {
        $this->onQueue('embeddings');
    }

    /**
     * @return int[]
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(EmbeddingService $embeddingService): void
    {
        $preference = $this->preference->fresh();
        if ($preference === null) {
            return;
        }

        $profileText = trim((string) $preference->profile_text);
        if ($profileText === '') {
            $preference->updateQuietly(['profile_embedding' => null]);

            return;
        }

        $vector = $embeddingService->embed($profileText);
        if ($vector === []) {
            return;
        }

        $current = $preference->fresh();
        if ($current === null) {
            return;
        }

        if (trim((string) $current->profile_text) !== $profileText) {
            self::dispatch($current);

            return;
        }

        $current->updateQuietly(['profile_embedding' => $vector]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('User profile embedding job failed.', [
            'preference_id' => $this->preference->getKey(),
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }
}
