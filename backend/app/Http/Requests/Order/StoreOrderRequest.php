<?php

namespace App\Http\Requests\Order;

use App\Enums\IceLevel;
use App\Enums\SugarLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.drink_id' => [
                'required',
                'integer',
                Rule::exists('drinks', 'id')->where('is_available', true)->whereNull('deleted_at'),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.sugar_level' => ['nullable', Rule::enum(SugarLevel::class)],
            'items.*.ice_level' => ['nullable', Rule::enum(IceLevel::class)],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'occasion' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.drink_id.exists' => 'Món đã chọn không tồn tại hoặc hiện không còn phục vụ.',
        ];
    }
}
