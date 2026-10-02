<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Drink;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_notifies_admin_and_authenticated_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $drink = Drink::factory()->create();
        Sanctum::actingAs($customer);

        $orderId = $this->postJson('/api/orders', [
            'items' => [['drink_id' => $drink->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $this->assertSame('new_order', $admin->fresh()->notifications()->sole()->data['kind']);
        $this->assertSame($orderId, $admin->fresh()->notifications()->sole()->data['order_id']);
        $this->assertSame('order_placed', $customer->fresh()->notifications()->sole()->data['kind']);
    }

    public function test_guest_order_notifies_admin_without_creating_customer_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $drink = Drink::factory()->create();

        $this->postJson('/api/orders', [
            'customer_name' => 'Khách tại quán',
            'items' => [['drink_id' => $drink->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame('new_order', $admin->fresh()->notifications()->sole()->data['kind']);
    }

    public function test_user_can_list_and_mark_only_their_notifications_as_read(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $drink = Drink::factory()->create();
        Sanctum::actingAs($customer);

        $this->postJson('/api/orders', [
            'items' => [['drink_id' => $drink->id, 'quantity' => 1]],
        ])->assertCreated();

        $notificationId = $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('data.0.kind', 'order_placed')
            ->json('data.0.id');

        $this->patchJson("/api/notifications/{$notificationId}/read")->assertOk();
        $this->getJson('/api/notifications')->assertJsonPath('meta.unread_count', 0);

        $adminNotification = $admin->fresh()->notifications()->sole();
        $this->patchJson("/api/notifications/{$adminNotification->id}/read")->assertNotFound();
    }

    public function test_admin_status_change_notifies_customer(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'status' => OrderStatus::Pending,
            'total_price' => 45000,
        ]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::Confirmed->value,
        ])->assertOk();

        $notification = $customer->fresh()->notifications()->sole();
        $this->assertSame('order_status', $notification->data['kind']);
        $this->assertSame(OrderStatus::Confirmed->value, $notification->data['status']);
    }
}
