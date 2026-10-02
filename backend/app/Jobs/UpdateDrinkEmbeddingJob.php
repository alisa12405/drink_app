<?php

namespace App\Jobs;

use App\Models\Drink;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateDrinkEmbeddingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public Drink $drink)
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
        $drink = Drink::query()->find($this->drink->getKey());
        if ($drink === null) {
            return;
        }

        $source = $this->sourceText($drink);
        $vector = $embeddingService->embed($source);
        if ($vector === []) {
            return;
        }

        $current = Drink::query()->find($drink->getKey());
        if ($current === null) {
            return;
        }

        if ($this->sourceText($current) !== $source) {
            self::dispatch($current);

            return;
        }

        $current->updateQuietly(['description_embedding' => $vector]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Drink embedding job failed.', [
            'drink_id' => $this->drink->getKey(),
            'exception' => $exception === null ? null : $exception::class,
        ]);
    }

    private function sourceText(Drink $drink): string
    {
        return sprintf(
            'Tên: %s. Mô tả: %s. Thành phần: %s. Thẻ: %s.',
            $drink->name,
            $drink->description ?? '',
            $drink->ingredients ?? '',
            implode(', ', $drink->tags ?? []),
        );
    }
}
