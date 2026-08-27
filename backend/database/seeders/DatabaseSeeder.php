<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Khách hàng',
            'email' => 'test@example.com',
        ]);

        User::factory()->admin()->create([
            'name' => 'Quản trị',
            'email' => 'admin@example.com',
        ]);

        $this->call(DrinkSeeder::class);
    }
}
