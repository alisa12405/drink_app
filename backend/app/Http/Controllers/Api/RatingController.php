<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rating\StoreRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\Order;
use App\Models\Rating;
use App\Services\UserProfileTextService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RatingController extends Controller
{
    public function __construct(private readonly UserProfileTextService $profileTextService) {}

    /**
     * UC-07: Đánh giá món đã uống — chỉ cho phép trên đơn hàng `done` có chứa món đó.
     * Mỗi món trong một đơn chỉ được đánh giá một lần.
     */
    public function store(StoreRatingRequest $request): RatingResource
    {
        $user = $request->user();
        $order = Order::query()->findOrFail($request->validated('order_id'));

        abort_unless($order->user_id === $user->id, 403);
        abort_unless(
            $order->status === OrderStatus::Done,
            422,
            'Chỉ có thể đánh giá món trong đơn hàng đã hoàn tất.',
        );

        $drinkId = $request->validated('drink_id');
        abort_unless(
            $order->items()->where('drink_id', $drinkId)->exists(),
            422,
            'Món này không có trong đơn hàng đã chọn.',
        );

        abort_if(
            Rating::query()
                ->where('user_id', $user->id)
                ->where('order_id', $order->id)
                ->where('drink_id', $drinkId)
                ->exists(),
            422,
            'Món này đã được đánh giá và không thể chỉnh sửa lại.',
        );

        $rating = Rating::query()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'drink_id' => $drinkId,
            ...$request->only('rating', 'comment'),
        ]);

        // firstOrCreate() không nạp lại giá trị mặc định DB áp dụng khi tạo mới
        // (sugar_level_default/ice_level_default) — refresh để service tính đúng profile_text.
        $preference = $user->preference()->firstOrCreate([])->fresh();
        $this->profileTextService->sync($preference, $user);

        return new RatingResource($rating->load('drink:id,name'));
    }

    /**
     * Danh sách đánh giá của user hiện tại (hỗ trợ UI hiển thị trạng thái "đã đánh giá").
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ratings = $request->user()
            ->ratings()
            ->with('drink:id,name')
            ->latest()
            ->get();

        return RatingResource::collection($ratings);
    }
}
