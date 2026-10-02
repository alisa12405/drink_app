<?php

namespace Tests\Feature;

use App\Enums\TemperatureType;
use App\Jobs\UpdateDrinkEmbeddingJob;
use App\Models\Drink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_upload_and_replace_a_drink_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());
        $drink = Drink::factory()->create();

        $firstResponse = $this->post("/api/admin/drinks/{$drink->id}/image", [
            'image' => UploadedFile::fake()->image('first.png', 600, 600),
        ])->assertOk();

        $firstPath = $drink->refresh()->image_path;
        Storage::disk('public')->assertExists($firstPath);
        $firstResponse->assertJsonPath('data.image_url', "/api/drinks/{$drink->id}/image?v={$drink->updated_at->timestamp}");
        $this->get("/api/drinks/{$drink->id}/image")->assertOk();

        $this->post("/api/admin/drinks/{$drink->id}/image", [
            'image' => UploadedFile::fake()->image('second.png', 800, 800),
        ])->assertOk();

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($drink->refresh()->image_path);
    }

    public function test_drink_image_upload_rejects_invalid_files(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());
        $drink = Drink::factory()->create();

        $this->post("/api/admin/drinks/{$drink->id}/image", [
            'image' => UploadedFile::fake()->create('unsafe.svg', 20, 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['image']);
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
