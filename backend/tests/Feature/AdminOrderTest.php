<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $user, OrderStatus $status): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'total_price' => 50000,
        ]);
    }

    public function test_guest_cannot_access_admin_order_endpoints(): void
    {
        $order = $this->createOrder(User::factory()->create(), OrderStatus::Pending);

        $this->getJson('/api/admin/orders')->assertUnauthorized();
        $this->getJson("/api/admin/orders/{$order->id}")->assertUnauthorized();
        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'confirmed'])->assertUnauthorized();
    }

    public function test_customer_cannot_access_admin_order_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->createOrder(User::factory()->create(), OrderStatus::Pending);

        $this->getJson('/api/admin/orders')->assertForbidden();
        $this->getJson("/api/admin/orders/{$order->id}")->assertForbidden();
        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'confirmed'])->assertForbidden();
    }

    public function test_admin_can_list_all_orders_and_filter_by_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $customerA = User::factory()->create();
        $customerB = User::factory()->create();

        $this->createOrder($customerA, OrderStatus::Pending);
        $this->createOrder($customerB, OrderStatus::Done);
        $this->createOrder($customerB, OrderStatus::Cancelled);

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson('/api/admin/orders?status=done')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', OrderStatus::Done->value);
    }

    public function test_admin_can_view_order_detail_with_customer_info(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $customer = User::factory()->create(['name' => 'Nguyễn Văn A']);
        $order = $this->createOrder($customer, OrderStatus::Pending);

        $this->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Nguyễn Văn A')
            ->assertJsonPath('data.user.id', $customer->id);
    }

    public function test_admin_can_view_guest_order_with_customer_name(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $order = Order::query()->create([
            'user_id' => null,
            'customer_name' => 'Khách vãng lai',
            'status' => OrderStatus::Pending,
            'total_price' => 50000,
        ]);

        $this->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'Khách vãng lai')
            ->assertJsonPath('data.is_guest', true)
            ->assertJsonMissingPath('data.user');
    }

    public function test_admin_can_transition_order_status_through_valid_chain(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $order = $this->createOrder(User::factory()->create(), OrderStatus::Pending);

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Done->value]);
    }

    public function test_admin_can_cancel_a_confirmed_order_as_an_exception(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $order = $this->createOrder(User::factory()->create(), OrderStatus::Confirmed);

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_admin_cannot_skip_confirmed_step_or_change_terminal_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $pending = $this->createOrder(User::factory()->create(), OrderStatus::Pending);
        $this->patchJson("/api/admin/orders/{$pending->id}/status", ['status' => 'done'])
            ->assertStatus(422);

        $done = $this->createOrder(User::factory()->create(), OrderStatus::Done);
        $this->patchJson("/api/admin/orders/{$done->id}/status", ['status' => 'cancelled'])
            ->assertStatus(422);

        $cancelled = $this->createOrder(User::factory()->create(), OrderStatus::Cancelled);
        $this->patchJson("/api/admin/orders/{$cancelled->id}/status", ['status' => 'pending'])
            ->assertStatus(422);
    }

    public function test_update_status_validates_enum_value(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $order = $this->createOrder(User::factory()->create(), OrderStatus::Pending);

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
}
