<?php

namespace App\Models;

use App\Enums\IceLevel;
use App\Enums\SugarLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'drink_id',
    'quantity',
    'sugar_level',
    'ice_level',
    'note',
    'unit_price',
    'subtotal',
])]
class OrderItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sugar_level' => SugarLevel::class,
            'ice_level' => IceLevel::class,
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function drink(): BelongsTo
    {
        return $this->belongsTo(Drink::class);
    }
}
