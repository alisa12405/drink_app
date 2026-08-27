<?php

namespace App\Http\Controllers\Api;

use App\Enums\IceLevel;
use App\Enums\OrderStatus;
use App\Enums\SugarLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Drink;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * UC-05: Tạo đơn hàng — snapshot giá món + ngữ cảnh tại thời điểm đặt.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $preference = $user->preference;

        $order = DB::transaction(function () use ($request, $user, $preference) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'total_price' => 0,
                'context_snapshot' => [
                    'hour' => (int) now()->format('G'),
                    'weather' => null,
                    'temperature' => null,
                    'order_type' => $request->validated('order_type'),
                    'occasion' => $request->validated('occasion'),
                    'lat' => $request->validated('lat'),
                    'lon' => $request->validated('lon'),
                ],
            ]);

            $total = 0;

            foreach ($request->validated('items') as $item) {
                $drink = Drink::query()->findOrFail($item['drink_id']);
                $quantity = (int) $item['quantity'];
                $subtotal = $drink->price * $quantity;

                $order->items()->create([
                    'drink_id' => $drink->id,
                    'quantity' => $quantity,
                    'sugar_level' => $item['sugar_level'] ?? $preference?->sugar_level_default ?? SugarLevel::Hundred,
                    'ice_level' => $item['ice_level'] ?? $preference?->ice_level_default ?? IceLevel::NormalIce,
                    'note' => $item['note'] ?? null,
                    'unit_price' => $drink->price,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_price' => $total]);

            return $order;
        });

        return (new OrderResource($order->load('items.drink')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * UC-06: Lịch sử đơn hàng của user hiện tại.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()
            ->orders()
            ->with('items.drink')
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->authorizeOwner($order, $request->user());

        return new OrderResource($order->load('items.drink'));
    }

    /**
     * Huỷ đơn — chỉ hợp lệ khi đơn còn ở trạng thái `pending` (mục 1.5 SPEC).
     */
    public function cancel(Request $request, Order $order): OrderResource
    {
        $this->authorizeOwner($order, $request->user());

        abort_unless(
            $order->status === OrderStatus::Pending,
            422,
            'Chỉ có thể huỷ đơn khi đang ở trạng thái chờ xác nhận.',
        );

        $order->update(['status' => OrderStatus::Cancelled]);

        return new OrderResource($order->load('items.drink'));
    }

    private function authorizeOwner(Order $order, User $user): void
    {
        abort_unless($order->user_id === $user->id || $user->isAdmin(), 403);
    }

    /**
     * UC-09: Admin xem toàn bộ đơn hàng — lọc theo trạng thái/khách hàng.
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->with(['user:id,name,email', 'items.drink'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('user_id'),
                fn ($query) => $query->where('user_id', $request->integer('user_id')),
            )
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function adminShow(Order $order): OrderResource
    {
        return new OrderResource($order->load(['user:id,name,email', 'items.drink']));
    }

    /**
     * UC-09: Admin đổi trạng thái đơn hàng theo đúng chiều nghiệp vụ
     * (pending -> confirmed -> done, hoặc pending/confirmed -> cancelled).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $targetStatus = OrderStatus::from($request->validated('status'));

        abort_unless(
            $order->status->canTransitionTo($targetStatus),
            422,
            "Không thể chuyển đơn từ trạng thái '{$order->status->value}' sang '{$targetStatus->value}'.",
        );

        $order->update(['status' => $targetStatus]);

        return new OrderResource($order->load(['user:id,name,email', 'items.drink']));
    }
}
