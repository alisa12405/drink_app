<?php

namespace Tests\Feature;

use App\Enums\IceLevel;
use App\Enums\OrderStatus;
use App\Enums\SugarLevel;
use App\Models\Drink;
use App\Models\Order;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_or_view_orders(): void
    {
        $this->postJson('/api/orders', [])->assertUnauthorized();
        $this->getJson('/api/orders/history')->assertUnauthorized();
    }

    public function test_user_can_place_order_with_snapshot_pricing_and_default_preferences(): void
    {
        $user = User::factory()->create();
        UserPreference::query()->create([
            'user_id' => $user->id,
            'sugar_level_default' => SugarLevel::Fifty,
            'ice_level_default' => IceLevel::LessIce,
        ]);
        Sanctum::actingAs($user);

        $tra = Drink::factory()->create(['name' => 'Trà đào', 'price' => 39000]);
        $cafe = Drink::factory()->create(['name' => 'Cà phê sữa', 'price' => 25000]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['drink_id' => $tra->id, 'quantity' => 2],
                ['drink_id' => $cafe->id, 'quantity' => 1, 'sugar_level' => SugarLevel::Zero->value, 'note' => 'Ít đường giúp em'],
            ],
            'occasion' => 'sau khi tập gym',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::Pending->value)
            ->assertJsonPath('data.total_price', '103000.00')
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.unit_price', '39000.00')
            ->assertJsonPath('data.items.0.sugar_level', SugarLevel::Fifty->value)
            ->assertJsonPath('data.items.0.ice_level', IceLevel::LessIce->value)
            ->assertJsonPath('data.items.1.sugar_level', SugarLevel::Zero->value)
            ->assertJsonPath('data.items.1.note', 'Ít đường giúp em')
            ->assertJsonPath('data.context_snapshot.occasion', 'sau khi tập gym');

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total_price' => 103000]);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_cannot_order_unavailable_or_missing_drink(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $unavailable = Drink::factory()->unavailable()->create();

        $this->postJson('/api/orders', [
            'items' => [['drink_id' => $unavailable->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.drink_id']);

        $this->postJson('/api/orders', [
            'items' => [['drink_id' => 999999, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.drink_id']);
    }

    public function test_order_requires_at_least_one_item(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/orders', ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_user_can_view_own_order_history_and_detail(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create();

        $this->postJson('/api/orders', ['items' => [['drink_id' => $drink->id, 'quantity' => 1]]])->assertCreated();
        $this->postJson('/api/orders', ['items' => [['drink_id' => $drink->id, 'quantity' => 3]]])->assertCreated();

        $this->getJson('/api/orders/history')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $order = $user->orders()->first();
        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_user_cannot_view_or_cancel_other_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'status' => OrderStatus::Pending,
            'total_price' => 10000,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/orders/{$order->id}")->assertForbidden();
        $this->patchJson("/api/orders/{$order->id}/cancel")->assertForbidden();
    }

    public function test_user_can_cancel_pending_order_but_not_confirmed_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $drink = Drink::factory()->create();

        $created = $this->postJson('/api/orders', ['items' => [['drink_id' => $drink->id, 'quantity' => 1]]]);
        $orderId = $created->json('data.id');

        $this->patchJson("/api/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => OrderStatus::Cancelled->value]);

        $confirmedOrder = Order::query()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed,
            'total_price' => 20000,
        ]);

        $this->patchJson("/api/orders/{$confirmedOrder->id}/cancel")
            ->assertStatus(422);
    }
}
