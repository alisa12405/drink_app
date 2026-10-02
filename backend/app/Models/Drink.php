<?php

namespace App\Models;

use App\Enums\TemperatureType;
use Database\Factories\DrinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    'image_path',
    'is_available',
    'description_embedding',
])]
#[Hidden(['description_embedding'])]
class Drink extends Model
{
    /** @use HasFactory<DrinkFactory> */
    use HasFactory, SoftDeletes;

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

    /**
     * @param  Builder<Drink>  $query
     * @return Builder<Drink>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
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
