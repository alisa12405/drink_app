<?php

namespace App\Http\Requests\Preference;

use App\Enums\IceLevel;
use App\Enums\SugarLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'taste_tags' => ['sometimes', 'nullable', 'array'],
            'taste_tags.*' => ['string', 'max:50'],
            'sugar_level_default' => ['sometimes', Rule::enum(SugarLevel::class)],
            'ice_level_default' => ['sometimes', Rule::enum(IceLevel::class)],
            'allergy_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
