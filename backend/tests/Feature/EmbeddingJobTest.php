<?php

namespace Tests\Feature;

use App\Jobs\UpdateDrinkEmbeddingJob;
use App\Jobs\UpdateUserProfileEmbeddingJob;
use App\Models\Drink;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmbeddingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_drink_job_embeds_complete_source_and_stores_vector(): void
    {
        $drink = Drink::factory()->create([
            'name' => 'Cà phê sữa đá',
            'description' => 'Đậm vị',
            'ingredients' => 'Cà phê, sữa',
            'tags' => ['coffee', 'cold'],
        ]);
        $service = Mockery::mock(EmbeddingService::class);
        $service->shouldReceive('embed')
            ->once()
            ->with(Mockery::on(fn (string $source): bool => str_contains($source, 'Cà phê sữa đá')
                && str_contains($source, 'Đậm vị')
                && str_contains($source, 'Cà phê, sữa')
                && str_contains($source, 'coffee, cold')))
            ->andReturn([0.1, 0.2, 0.3]);

        (new UpdateDrinkEmbeddingJob($drink))->handle($service);

        $this->assertSame([0.1, 0.2, 0.3], $drink->fresh()->description_embedding);
    }

    public function test_profile_job_stores_vector_and_clears_it_for_empty_profile(): void
    {
        $preference = UserPreference::query()->create([
            'user_id' => User::factory()->create()->id,
            'profile_text' => 'Sở thích: coffee',
        ]);
        $service = Mockery::mock(EmbeddingService::class);
        $service->shouldReceive('embed')
            ->once()
            ->with('Sở thích: coffee')
            ->andReturn([0.4, 0.5]);

        (new UpdateUserProfileEmbeddingJob($preference))->handle($service);
        $this->assertSame([0.4, 0.5], $preference->fresh()->profile_embedding);

        $preference->update(['profile_text' => null]);
        (new UpdateUserProfileEmbeddingJob($preference))->handle($service);
        $this->assertNull($preference->fresh()->profile_embedding);
    }

    public function test_deleted_drink_is_ignored(): void
    {
        $drink = Drink::factory()->create();
        $drink->delete();
        $service = Mockery::mock(EmbeddingService::class);
        $service->shouldNotReceive('embed');

        (new UpdateDrinkEmbeddingJob($drink))->handle($service);
    }
}
