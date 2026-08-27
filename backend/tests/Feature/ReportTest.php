<?php

namespace Tests\Feature;

use App\Enums\IceLevel;
use App\Enums\OrderStatus;
use App\Enums\SugarLevel;
use App\Models\Drink;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RecommendationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithItem(
        Drink $drink,
        int $quantity,
        float $unitPrice,
        OrderStatus $status,
        ?User $user = null,
        ?\DateTimeInterface $createdAt = null,
    ): Order {
        $order = Order::query()->create([
            'user_id' => ($user ?? User::factory()->create())->id,
            'status' => $status,
            'total_price' => $unitPrice * $quantity,
        ]);

        // created_at không nằm trong $fillable của Order (mass-assignment bị bỏ qua) —
        // set trực tiếp thuộc tính rồi save() để giả lập đơn được tạo ở thời điểm mong muốn.
        if ($createdAt !== null) {
            $order->created_at = $createdAt;
            $order->save();
        }

        OrderItem::query()->create([
            'order_id' => $order->id,
            'drink_id' => $drink->id,
            'quantity' => $quantity,
            'sugar_level' => SugarLevel::Hundred,
            'ice_level' => IceLevel::NormalIce,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ]);

        return $order;
    }

    public function test_guest_and_customer_cannot_access_reports(): void
    {
        $this->getJson('/api/admin/reports/best-selling-drinks')->assertUnauthorized();
        $this->getJson('/api/admin/reports/recommendation-effectiveness')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/admin/reports/best-selling-drinks')->assertForbidden();
        $this->getJson('/api/admin/reports/recommendation-effectiveness')->assertForbidden();
    }

    public function test_best_selling_drinks_aggregates_only_done_orders(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $tra = Drink::factory()->create(['name' => 'Trà đào']);
        $cafe = Drink::factory()->create(['name' => 'Cà phê sữa']);

        $this->createOrderWithItem($tra, 3, 30000, OrderStatus::Done);
        $this->createOrderWithItem($cafe, 1, 25000, OrderStatus::Done);
        // Đơn huỷ có số lượng lớn hơn nhưng phải bị loại khỏi thống kê.
        $this->createOrderWithItem($tra, 10, 30000, OrderStatus::Cancelled);
        $this->createOrderWithItem($tra, 10, 30000, OrderStatus::Pending);

        $this->getJson('/api/admin/reports/best-selling-drinks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.drink_id', $tra->id)
            ->assertJsonPath('data.0.total_quantity', 3)
            ->assertJsonPath('data.0.total_revenue', 90000)
            ->assertJsonPath('data.1.drink_id', $cafe->id)
            ->assertJsonPath('data.1.total_quantity', 1);
    }

    public function test_best_selling_drinks_respects_date_range_filter(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $drink = Drink::factory()->create();

        $this->createOrderWithItem($drink, 2, 30000, OrderStatus::Done, createdAt: now()->subDays(10));
        $this->createOrderWithItem($drink, 5, 30000, OrderStatus::Done, createdAt: now());

        $this->getJson('/api/admin/reports/best-selling-drinks?from='.now()->subDay()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.0.total_quantity', 5);
    }

    public function test_recommendation_effectiveness_returns_zero_when_no_logs_exist(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/reports/recommendation-effectiveness')
            ->assertOk()
            ->assertJsonPath('data.total_recommendations', 0)
            ->assertJsonPath('data.converted_recommendations', 0)
            ->assertJsonPath('data.conversion_rate', 0);
    }

    public function test_recommendation_effectiveness_tracks_conversion_by_position(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $user = User::factory()->create();
        $drinkA = Drink::factory()->create();
        $drinkB = Drink::factory()->create();
        $drinkC = Drink::factory()->create();

        $logTime = now()->subHours(2);
        $log = RecommendationLog::query()->create([
            'user_id' => $user->id,
            'final_ranked_ids' => [$drinkA->id, $drinkB->id, $drinkC->id],
        ]);
        $this->setLogCreatedAt($log, $logTime);

        // User mua drinkB (vị trí index 1 trong danh sách gợi ý) trong khung 24h.
        $this->createOrderWithItem(
            $drinkB,
            1,
            40000,
            OrderStatus::Pending,
            user: $user,
            createdAt: $logTime->copy()->addHour(),
        );

        // Log thứ 2 không có ai mua món nào trong danh sách -> không converted.
        $secondLog = RecommendationLog::query()->create([
            'user_id' => $user->id,
            'final_ranked_ids' => [$drinkC->id],
        ]);
        $this->setLogCreatedAt($secondLog, now()->subHours(5));

        $this->getJson('/api/admin/reports/recommendation-effectiveness')
            ->assertOk()
            ->assertJsonPath('data.total_recommendations', 2)
            ->assertJsonPath('data.converted_recommendations', 1)
            ->assertJsonPath('data.conversion_rate', 50)
            ->assertJsonPath('data.conversion_by_position.1', 1);
    }

    private function setLogCreatedAt(RecommendationLog $log, \DateTimeInterface $createdAt): void
    {
        // created_at không nằm trong $fillable của RecommendationLog — set trực tiếp rồi save().
        $log->created_at = $createdAt;
        $log->save();
    }

    public function test_recommendation_effectiveness_ignores_purchase_outside_window(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $user = User::factory()->create();
        $drink = Drink::factory()->create();

        $logTime = now()->subDays(3);
        $log = RecommendationLog::query()->create([
            'user_id' => $user->id,
            'final_ranked_ids' => [$drink->id],
        ]);
        $this->setLogCreatedAt($log, $logTime);

        // Mua 2 ngày sau đó -> ngoài khung mặc định 24h, không tính là converted.
        $this->createOrderWithItem(
            $drink,
            1,
            20000,
            OrderStatus::Pending,
            user: $user,
            createdAt: $logTime->copy()->addDays(2),
        );

        $this->getJson('/api/admin/reports/recommendation-effectiveness')
            ->assertOk()
            ->assertJsonPath('data.converted_recommendations', 0);
    }
}
