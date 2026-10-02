<?php

namespace App\Http\Requests\Recommendation;

use Illuminate\Foundation\Http\FormRequest;

class GetRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'lat' => ['nullable', 'required_with:lon', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'occasion' => ['nullable', 'string', 'max:100'],
        ];
    }
}
