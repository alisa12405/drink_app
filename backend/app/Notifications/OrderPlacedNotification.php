<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'order_placed',
            'title' => 'Đặt hàng thành công',
            'message' => "Đơn #{$this->order->id} đã được tiếp nhận và đang chờ xác nhận.",
            'order_id' => $this->order->id,
            'url' => '/orders',
        ];
    }
}
