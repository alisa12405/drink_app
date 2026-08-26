<?php

namespace Database\Factories;

use App\Enums\TemperatureType;
use App\Models\Drink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Drink>
 */
class DrinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'ingredients' => fake()->words(4, true),
            'category' => fake()->randomElement(['trà sữa', 'cà phê', 'nước ép', 'trà trái cây']),
            'price' => fake()->randomFloat(2, 25000, 65000),
            'calories' => fake()->numberBetween(80, 400),
            'temperature_type' => fake()->randomElement(TemperatureType::cases()),
            'tags' => ['demo'],
            'image_url' => null,
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
