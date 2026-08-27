<?php

namespace Database\Seeders;

use App\Enums\TemperatureType;
use App\Models\Drink;
use Illuminate\Database\Seeder;

class DrinkSeeder extends Seeder
{
    public function run(): void
    {
        $drinks = [
            [
                'name' => 'Trà đào cam sả',
                'description' => 'Trà đen ủ lạnh kết hợp đào ngâm, cam tươi và sả thơm mát.',
                'ingredients' => 'Trà đen, đào ngâm, cam tươi, sả, đường',
                'category' => 'trà trái cây',
                'price' => 45000,
                'calories' => 180,
                'temperature_type' => TemperatureType::Cold,
                'tags' => ['best_seller', 'trái_cây', 'giải_khát'],
            ],
            [
                'name' => 'Trà sữa trân châu',
                'description' => 'Trà sữa đậm vị kết hợp trân châu đen dai.',
                'ingredients' => 'Trà đen, sữa, trân châu, đường',
                'category' => 'trà sữa',
                'price' => 40000,
                'calories' => 320,
                'temperature_type' => TemperatureType::Cold,
                'tags' => ['best_seller', 'ngọt'],
            ],
            [
                'name' => 'Cà phê sữa đá',
                'description' => 'Cà phê phin truyền thống đánh với sữa đặc.',
                'ingredients' => 'Cà phê, sữa đặc, đá',
                'category' => 'cà phê',
                'price' => 35000,
                'calories' => 180,
                'temperature_type' => TemperatureType::Cold,
                'tags' => ['có_caffeine', 'truyền_thống'],
            ],
            [
                'name' => 'Cà phê đen nóng',
                'description' => 'Cà phê phin nguyên chất, vị đắng đậm.',
                'ingredients' => 'Cà phê, nước nóng',
                'category' => 'cà phê',
                'price' => 30000,
                'calories' => 5,
                'temperature_type' => TemperatureType::Hot,
                'tags' => ['có_caffeine', 'ít_ngọt'],
            ],
            [
                'name' => 'Nước ép cam',
                'description' => 'Cam tươi vắt, không đường.',
                'ingredients' => 'Cam tươi',
                'category' => 'nước ép',
                'price' => 42000,
                'calories' => 110,
                'temperature_type' => TemperatureType::Cold,
                'tags' => ['trái_cây', 'ít_ngọt'],
            ],
            [
                'name' => 'Trà ô long sữa',
                'description' => 'Trà ô long thơm nhẹ kết hợp sữa tươi.',
                'ingredients' => 'Trà ô long, sữa tươi, đường',
                'category' => 'trà sữa',
                'price' => 45000,
                'calories' => 250,
                'temperature_type' => TemperatureType::Both,
                'tags' => ['ít_ngọt', 'thơm'],
            ],
            [
                'name' => 'Matcha latte',
                'description' => 'Bột matcha Nhật đánh với sữa tươi.',
                'ingredients' => 'Matcha, sữa tươi, đường',
                'category' => 'cà phê',
                'price' => 48000,
                'calories' => 210,
                'temperature_type' => TemperatureType::Both,
                'tags' => ['matcha', 'có_caffeine'],
            ],
            [
                'name' => 'Trà sen vàng',
                'description' => 'Trà sen thanh mát, vị ngọt nhẹ.',
                'ingredients' => 'Trà sen, hạt sen, đường',
                'category' => 'trà trái cây',
                'price' => 43000,
                'calories' => 140,
                'temperature_type' => TemperatureType::Cold,
                'tags' => ['thanh_mát'],
            ],
        ];

        foreach ($drinks as $drink) {
            Drink::query()->create($drink);
        }
    }
}
