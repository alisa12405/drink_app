<?php

namespace Tests\Feature;

use App\Enums\IceLevel;
use App\Enums\OrderStatus;
use App\Enums\SugarLevel;
use App\Jobs\UpdateUserProfileEmbeddingJob;
use App\Models\Drink;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_or_update_preferences(): void
    {
        $this->getJson('/api/preferences')->assertUnauthorized();
        $this->putJson('/api/preferences', [])->assertUnauthorized();
    }

    public function test_user_without_preference_sees_defaults_without_creating_row(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/preferences')
            ->assertOk()
            ->assertJsonPath('data.taste_tags', [])
            ->assertJsonPath('data.sugar_level_default', SugarLevel::Hundred->value)
            ->assertJsonPath('data.ice_level_default', IceLevel::NormalIce->value)
            ->assertJsonPath('data.allergy_notes', null);

        $this->assertDatabaseCount('user_preferences', 0);
    }

    public function test_user_can_update_preferences_and_dispatches_embedding_job(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/preferences', [
            'taste_tags' => ['ngọt', 'có_caffeine'],
            'sugar_level_default' => SugarLevel::Seventy->value,
            'ice_level_default' => IceLevel::LessIce->value,
            'allergy_notes' => 'Dị ứng đậu phộng',
        ])
            ->assertOk()
            ->assertJsonPath('data.taste_tags', ['ngọt', 'có_caffeine'])
            ->assertJsonPath('data.sugar_level_default', SugarLevel::Seventy->value)
            ->assertJsonPath('data.ice_level_default', IceLevel::LessIce->value)
            ->assertJsonPath('data.allergy_notes', 'Dị ứng đậu phộng')
            ->assertJsonMissingPath('data.profile_embedding');

        $this->assertDatabaseCount('user_preferences', 1);
        $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id]);

        Bus::assertDispatchedTimes(UpdateUserProfileEmbeddingJob::class, 1);
    }

    public function test_updating_preferences_never_creates_a_second_row(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/preferences', ['sugar_level_default' => SugarLevel::Fifty->value])->assertOk();
        $this->putJson('/api/preferences', ['sugar_level_default' => SugarLevel::Thirty->value])->assertOk();

        $this->assertDatabaseCount('user_preferences', 1);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'sugar_level_default' => SugarLevel::Thirty->value,
        ]);
    }

    public function test_embedding_job_not_redispatched_when_profile_text_unchanged(): void
    {
        Bus::fake();
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'taste_tags' => ['ngọt'],
            'sugar_level_default' => SugarLevel::Fifty->value,
            'ice_level_default' => IceLevel::NormalIce->value,
        ];

        $this->putJson('/api/preferences', $payload)->assertOk();
        $this->putJson('/api/preferences', $payload)->assertOk();

        Bus::assertDispatchedTimes(UpdateUserProfileEmbeddingJob::class, 1);
    }

    public function test_profile_text_includes_recent_orders_and_high_ratings(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create(['name' => 'Trà đào cam sả']);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Done,
            'total_price' => 45000,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'quantity' => 1,
            'unit_price' => 45000,
            'subtotal' => 45000,
        ]);

        Rating::query()->create([
            'user_id' => $user->id,
            'drink_id' => $drink->id,
            'order_id' => $order->id,
            'rating' => 5,
        ]);

        $this->putJson('/api/preferences', ['taste_tags' => ['trái_cây']])
            ->assertOk()
            ->assertJsonPath('data.profile_text', fn ($text) => str_contains($text, 'Trà đào cam sả') && str_contains($text, '5 sao'));
    }
}
