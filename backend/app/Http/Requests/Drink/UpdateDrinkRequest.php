<?php

namespace App\Http\Requests\Drink;

use App\Enums\TemperatureType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDrinkRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'ingredients' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'calories' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'temperature_type' => ['sometimes', 'required', Rule::enum(TemperatureType::class)],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
