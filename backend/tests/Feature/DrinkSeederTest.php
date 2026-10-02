<?php

namespace Tests\Feature;

use App\Models\Drink;
use Database\Seeders\DrinkSeeder;
use Database\Seeders\MissingDrinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrinkSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_synchronizes_all_workbook_drinks_without_removing_custom_drinks(): void
    {
        $this->assertSame('sqlite', config('database.default'));

        Drink::factory()->create(['name' => 'Món tùy chỉnh']);

        $this->seed(DrinkSeeder::class);

        $this->assertDatabaseCount('drinks', 51);
        $this->assertDatabaseHas('drinks', [
            'name' => 'Trà sữa trân châu đường đen',
            'price' => 45000,
            'image_url' => '/images/tra_sua_tccd.jpg',
        ]);
        $this->assertDatabaseHas('drinks', ['name' => 'Món tùy chỉnh']);
    }

    public function test_missing_seeder_preserves_existing_menu_edits_and_custom_drinks(): void
    {
        $this->seed(DrinkSeeder::class);

        $drink = Drink::query()->where('name', 'Trà đào cam sả')->firstOrFail();
        $drink->update(['price' => 12345]);
        Drink::factory()->create(['name' => 'Món tùy chỉnh']);

        $this->seed(MissingDrinkSeeder::class);

        $this->assertDatabaseCount('drinks', 51);
        $this->assertDatabaseHas('drinks', [
            'name' => 'Trà đào cam sả',
            'price' => 12345,
        ]);
        $this->assertDatabaseHas('drinks', ['name' => 'Món tùy chỉnh']);
    }
}
