<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Cancelled = 'cancelled';

    /**
     * Chiều chuyển trạng thái hợp lệ (database/tables/orders.md):
     * pending -> confirmed -> done, hoặc pending/confirmed -> cancelled. Không có chiều ngược lại.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::Done, self::Cancelled], true),
            self::Done, self::Cancelled => false,
        };
    }
}
