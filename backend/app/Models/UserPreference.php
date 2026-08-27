<?php

namespace App\Models;

use App\Enums\IceLevel;
use App\Enums\SugarLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'taste_tags',
    'sugar_level_default',
    'ice_level_default',
    'allergy_notes',
    'profile_text',
    'profile_embedding',
])]
#[Hidden(['profile_embedding'])]
class UserPreference extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'taste_tags' => 'array',
            'sugar_level_default' => SugarLevel::class,
            'ice_level_default' => IceLevel::class,
            'profile_embedding' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
