<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly OrderStatus $status,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $label = match ($this->status) {
            OrderStatus::Pending => 'đang chờ xác nhận',
            OrderStatus::Confirmed => 'đã được xác nhận',
            OrderStatus::Done => 'đã hoàn tất',
            OrderStatus::Cancelled => 'đã bị hủy',
        };

        return [
            'kind' => 'order_status',
            'title' => "Cập nhật đơn #{$this->order->id}",
            'message' => "Đơn hàng của bạn {$label}.",
            'order_id' => $this->order->id,
            'status' => $this->status->value,
            'url' => '/orders',
        ];
    }
}
