<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\RecommendationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * UC-10: Món bán nhiều nhất — tính trên order_items của các đơn đã hoàn tất (`done`),
     * có thể lọc theo khoảng ngày tạo đơn. Dùng query builder (không qua Eloquent scope
     * soft-delete của Drink) để vẫn thống kê được cả món đã bị xoá khỏi menu.
     */
    public function bestSellingDrinks(Request $request): JsonResponse
    {
        $limit = min($request->integer('limit', 10), 50);

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('drinks', 'drinks.id', '=', 'order_items.drink_id')
            ->where('orders.status', OrderStatus::Done->value)
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate('orders.created_at', '>=', $request->date('from')),
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate('orders.created_at', '<=', $request->date('to')),
            )
            ->selectRaw('order_items.drink_id')
            ->selectRaw('drinks.name as drink_name')
            ->selectRaw('drinks.category as drink_category')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->groupBy('order_items.drink_id', 'drinks.name', 'drinks.category')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'drink_id' => (int) $row->drink_id,
                'drink_name' => $row->drink_name,
                'drink_category' => $row->drink_category,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue' => (float) $row->total_revenue,
            ]);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'status_counted' => OrderStatus::Done->value,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * UC-10: Hiệu quả gợi ý — đối chiếu `recommendation_logs.final_ranked_ids` với
     * order_items thực tế của cùng user trong khung thời gian sau đó (mặc định 24h),
     * theo đúng gợi ý đo lường ở database/tables/recommendation_logs.md.
     *
     * Lưu ý: báo cáo này sẽ trả về toàn số 0 cho tới khi UC-04 (Recommendation Engine)
     * được triển khai và bắt đầu ghi log — đây không phải lỗi.
     */
    public function recommendationEffectiveness(Request $request): JsonResponse
    {
        $windowHours = min($request->integer('window_hours', 24), 24 * 30);
        $limit = min($request->integer('limit', 500), 2000);

        $logs = RecommendationLog::query()
            ->when(
                $request->filled('from'),
                fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')),
            )
            ->when(
                $request->filled('to'),
                fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')),
            )
            ->latest()
            ->limit($limit)
            ->get(['id', 'user_id', 'final_ranked_ids', 'created_at']);

        $totalLogs = $logs->count();
        $convertedLogs = 0;
        $positionCounts = [];

        foreach ($logs as $log) {
            $rankedIds = $log->final_ranked_ids ?? [];

            if (empty($rankedIds)) {
                continue;
            }

            $purchasedDrinkId = OrderItem::query()
                ->whereIn('drink_id', $rankedIds)
                ->whereHas('order', fn ($query) => $query
                    ->where('user_id', $log->user_id)
                    ->whereBetween('created_at', [$log->created_at, $log->created_at->copy()->addHours($windowHours)]))
                ->value('drink_id');

            if ($purchasedDrinkId === null) {
                continue;
            }

            $convertedLogs++;
            $position = array_search($purchasedDrinkId, $rankedIds, true);
            $positionCounts[$position] = ($positionCounts[$position] ?? 0) + 1;
        }

        ksort($positionCounts);

        return response()->json([
            'data' => [
                'total_recommendations' => $totalLogs,
                'converted_recommendations' => $convertedLogs,
                'conversion_rate' => $totalLogs > 0 ? round($convertedLogs / $totalLogs * 100, 2) : 0.0,
                'conversion_by_position' => (object) $positionCounts,
            ],
            'meta' => [
                'window_hours' => $windowHours,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
                'logs_analyzed' => $totalLogs,
            ],
        ]);
    }
}
