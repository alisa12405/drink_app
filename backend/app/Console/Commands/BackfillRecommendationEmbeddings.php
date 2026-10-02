<?php

namespace App\Console\Commands;

use App\Jobs\UpdateDrinkEmbeddingJob;
use App\Jobs\UpdateUserProfileEmbeddingJob;
use App\Models\Drink;
use App\Models\UserPreference;
use Illuminate\Console\Command;

class BackfillRecommendationEmbeddings extends Command
{
    protected $signature = 'recommendations:backfill-embeddings
                            {--drinks : Queue embeddings for drinks}
                            {--users : Queue embeddings for user profiles}';

    protected $description = 'Queue missing recommendation embeddings for drinks and user profiles';

    public function handle(): int
    {
        $selected = $this->option('drinks') || $this->option('users');
        $includeDrinks = $this->option('drinks') || ! $selected;
        $includeUsers = $this->option('users') || ! $selected;

        $drinkCount = 0;
        $userCount = 0;

        if ($includeDrinks) {
            Drink::query()
                ->whereNull('description_embedding')
                ->chunkById(100, function ($drinks) use (&$drinkCount): void {
                    foreach ($drinks as $drink) {
                        UpdateDrinkEmbeddingJob::dispatch($drink);
                        $drinkCount++;
                    }
                });
        }

        if ($includeUsers) {
            UserPreference::query()
                ->whereNotNull('profile_text')
                ->whereNull('profile_embedding')
                ->chunkById(100, function ($preferences) use (&$userCount): void {
                    foreach ($preferences as $preference) {
                        UpdateUserProfileEmbeddingJob::dispatch($preference);
                        $userCount++;
                    }
                });
        }

        $this->info("Queued {$drinkCount} drink embedding job(s) and {$userCount} profile embedding job(s).");

        return self::SUCCESS;
    }
}
