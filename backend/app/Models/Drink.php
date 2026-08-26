<?php

namespace App\Models;

use App\Enums\TemperatureType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'description',
    'ingredients',
    'category',
    'price',
    'calories',
    'temperature_type',
    'tags',
    'image_url',
    'is_available',
    'description_embedding',
])]
class Drink extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'calories' => 'integer',
            'temperature_type' => TemperatureType::class,
            'tags' => 'array',
            'is_available' => 'boolean',
            'description_embedding' => 'array',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
