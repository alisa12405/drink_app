<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
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
            'kind' => 'new_order',
            'title' => "Đơn hàng mới #{$this->order->id}",
            'message' => sprintf(
                '%s vừa đặt đơn trị giá %sđ.',
                $this->order->customer_name ?? 'Khách hàng',
                number_format((float) $this->order->total_price, 0, ',', '.'),
            ),
            'order_id' => $this->order->id,
            'url' => '/admin/orders',
        ];
    }
}
