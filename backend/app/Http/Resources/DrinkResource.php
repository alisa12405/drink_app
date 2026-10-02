<?php

namespace App\Http\Resources;

use App\Models\Drink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Drink
 */
class DrinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'category' => $this->category,
            'price' => $this->price,
            'calories' => $this->calories,
            'temperature_type' => $this->temperature_type->value,
            'tags' => $this->tags,
            'image_url' => $this->image_path
                ? "/api/drinks/{$this->getKey()}/image?v=".($this->updated_at?->timestamp ?? 0)
                : $this->image_url,
            'is_available' => $this->is_available,
        ];
    }
}
