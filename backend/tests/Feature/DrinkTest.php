<?php

namespace Tests\Feature;

use App\Enums\TemperatureType;
use App\Jobs\UpdateDrinkEmbeddingJob;
use App\Models\Drink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DrinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_available_drinks_and_filter_by_category(): void
    {
        Drink::factory()->create(['name' => 'Trà đào', 'category' => 'trà trái cây']);
        Drink::factory()->create(['name' => 'Cà phê sữa', 'category' => 'cà phê']);
        Drink::factory()->unavailable()->create(['name' => 'Hết món', 'category' => 'cà phê']);

        $this->getJson('/api/drinks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['name' => 'Hết món']);

        $this->getJson('/api/drinks?category='.urlencode('cà phê'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cà phê sữa');
    }

    public function test_guest_cannot_view_unavailable_drink(): void
    {
        $drink = Drink::factory()->unavailable()->create();

        $this->getJson("/api/drinks/{$drink->id}")->assertNotFound();
    }

    public function test_guest_cannot_create_drink(): void
    {
        $this->postJson('/api/admin/drinks', $this->drinkPayload())->assertUnauthorized();
    }

    public function test_customer_cannot_manage_drinks(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/admin/drinks', $this->drinkPayload())->assertForbidden();
    }

    public function test_admin_can_create_drink_and_dispatches_embedding_job(): void
    {
        Bus::fake();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/admin/drinks', $this->drinkPayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Trà đào cam sả')
            ->assertJsonMissingPath('data.description_embedding');

        $this->assertDatabaseHas('drinks', ['name' => 'Trà đào cam sả']);
        Bus::assertDispatched(UpdateDrinkEmbeddingJob::class);
    }

    public function test_admin_can_update_and_delete_drink(): void
    {
        Bus::fake();
        Sanctum::actingAs(User::factory()->admin()->create());
        $drink = Drink::factory()->create(['name' => 'Trà đào']);

        $this->putJson("/api/admin/drinks/{$drink->id}", [
            'name' => 'Trà đào cam sả',
            'price' => 49000,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Trà đào cam sả');

        Bus::assertDispatched(UpdateDrinkEmbeddingJob::class);

        $this->deleteJson("/api/admin/drinks/{$drink->id}")->assertNoContent();
        $this->assertSoftDeleted($drink);
    }

    /**
     * @return array<string, mixed>
     */
    private function drinkPayload(): array
    {
        return [
            'name' => 'Trà đào cam sả',
            'description' => 'Trà đen ủ lạnh.',
            'ingredients' => 'Trà đen, đào, cam, sả',
            'category' => 'trà trái cây',
            'price' => 45000,
            'calories' => 180,
            'temperature_type' => TemperatureType::Cold->value,
            'tags' => ['best_seller'],
            'is_available' => true,
        ];
    }
}
