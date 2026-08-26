<?php

namespace App\Http\Resources;

use App\Enums\IceLevel;
use App\Enums\SugarLevel;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserPreference
 */
class PreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'taste_tags' => $this->taste_tags ?? [],
            'sugar_level_default' => ($this->sugar_level_default ?? SugarLevel::Hundred)->value,
            'ice_level_default' => ($this->ice_level_default ?? IceLevel::NormalIce)->value,
            'allergy_notes' => $this->allergy_notes,
            'profile_text' => $this->profile_text,
            'has_embedding' => ! empty($this->profile_embedding),
            'updated_at' => $this->updated_at,
        ];
    }
}
