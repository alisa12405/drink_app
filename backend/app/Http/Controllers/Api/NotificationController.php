<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(30)->get();

        return response()->json([
            'data' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'kind' => $notification->data['kind'] ?? 'general',
                'title' => $notification->data['title'] ?? 'Thông báo',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'order_id' => $notification->data['order_id'] ?? null,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at?->toISOString(),
            ])->values(),
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return response()->json(['message' => 'Đã đánh dấu thông báo là đã đọc.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Đã đọc tất cả thông báo.']);
    }
}
