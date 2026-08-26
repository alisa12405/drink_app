<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
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

class RatingTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithItem(User $user, Drink $drink, OrderStatus $status): Order
    {
        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'total_price' => 45000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'quantity' => 1,
            'unit_price' => 45000,
            'subtotal' => 45000,
        ]);

        return $order;
    }

    public function test_guest_cannot_rate_or_list_ratings(): void
    {
        $this->postJson('/api/ratings', [])->assertUnauthorized();
        $this->getJson('/api/ratings')->assertUnauthorized();
    }

    public function test_user_can_rate_drink_from_completed_order_and_triggers_profile_sync(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create(['name' => 'Trà đào cam sả']);
        $order = $this->createOrderWithItem($user, $drink, OrderStatus::Done);

        $this->postJson('/api/ratings', [
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 5,
            'comment' => 'Rất ngon!',
        ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Rất ngon!')
            ->assertJsonPath('data.drink_name', 'Trà đào cam sả');

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 5,
        ]);

        $this->assertDatabaseHas('user_preferences', ['user_id' => $user->id]);
        Bus::assertDispatched(UpdateUserProfileEmbeddingJob::class);
    }

    public function test_rating_same_order_and_drink_twice_updates_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create();
        $order = $this->createOrderWithItem($user, $drink, OrderStatus::Done);

        $this->postJson('/api/ratings', [
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 3,
        ])->assertCreated();

        $this->postJson('/api/ratings', [
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 5,
            'comment' => 'Đổi ý, ngon hơn tôi nghĩ',
        ])->assertOk()->assertJsonPath('data.rating', 5);

        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', ['rating' => 5, 'comment' => 'Đổi ý, ngon hơn tôi nghĩ']);
    }

    public function test_cannot_rate_pending_or_confirmed_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create();
        $pendingOrder = $this->createOrderWithItem($user, $drink, OrderStatus::Pending);

        $this->postJson('/api/ratings', [
            'order_id' => $pendingOrder->id,
            'drink_id' => $drink->id,
            'rating' => 4,
        ])->assertStatus(422);

        $confirmedOrder = $this->createOrderWithItem($user, $drink, OrderStatus::Confirmed);

        $this->postJson('/api/ratings', [
            'order_id' => $confirmedOrder->id,
            'drink_id' => $drink->id,
            'rating' => 4,
        ])->assertStatus(422);
    }

    public function test_cannot_rate_drink_not_in_the_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $orderedDrink = Drink::factory()->create();
        $otherDrink = Drink::factory()->create();
        $order = $this->createOrderWithItem($user, $orderedDrink, OrderStatus::Done);

        $this->postJson('/api/ratings', [
            'order_id' => $order->id,
            'drink_id' => $otherDrink->id,
            'rating' => 4,
        ])->assertStatus(422);
    }

    public function test_user_cannot_rate_another_users_order(): void
    {
        $owner = User::factory()->create();
        $drink = Drink::factory()->create();
        $order = $this->createOrderWithItem($owner, $drink, OrderStatus::Done);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ratings', [
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 4,
        ])->assertForbidden();
    }

    public function test_rating_value_must_be_between_one_and_five(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create();
        $order = $this->createOrderWithItem($user, $drink, OrderStatus::Done);

        $this->postJson('/api/ratings', [
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 6,
        ])->assertUnprocessable()->assertJsonValidationErrors(['rating']);
    }

    public function test_user_can_list_only_own_ratings(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $drink = Drink::factory()->create();

        $order = $this->createOrderWithItem($user, $drink, OrderStatus::Done);
        Rating::query()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'rating' => 4,
        ]);

        $otherOrder = $this->createOrderWithItem($other, $drink, OrderStatus::Done);
        Rating::query()->create([
            'user_id' => $other->id,
            'order_id' => $otherOrder->id,
            'drink_id' => $drink->id,
            'rating' => 2,
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/ratings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 4);
    }
}
