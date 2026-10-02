<?php

namespace Database\Seeders;

use App\Enums\TemperatureType;
use App\Models\Drink;
use Illuminate\Database\Seeder;
use JsonException;
use RuntimeException;

class DrinkSeeder extends Seeder
{
    protected bool $updateExisting = true;

    /**
     * Synchronize the menu exported from database/drinks_menu.xlsx.
     */
    public function run(): void
    {
        foreach ($this->menuRows() as $attributes) {
            $drink = Drink::query()
                ->withTrashed()
                ->where('name', $attributes['name'])
                ->first();

            if ($drink !== null && ! $this->updateExisting) {
                continue;
            }

            $drink ??= new Drink;
            $drink->fill($attributes);

            if ($drink->exists && $drink->isDirty(['description', 'ingredients', 'tags'])) {
                $drink->description_embedding = null;
            }

            if ($drink->trashed()) {
                $drink->restore();
            }

            $drink->save();
        }
    }

    /**
     * @return iterable<int, array<string, mixed>>
     */
    private function menuRows(): iterable
    {
        $path = database_path('data/drinks_menu.csv');
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Không thể mở danh mục đồ uống: {$path}");
        }

        try {
            $headers = fgetcsv($handle, escape: '');

            if ($headers === false) {
                throw new RuntimeException("Danh mục đồ uống không có dòng tiêu đề: {$path}");
            }

            $line = 1;

            while (($values = fgetcsv($handle, escape: '')) !== false) {
                $line++;

                if (count($headers) !== count($values)) {
                    throw new RuntimeException("Dòng {$line} của danh mục đồ uống không đủ cột.");
                }

                /** @var array<string, string> $row */
                $row = array_combine($headers, $values);

                yield $this->normalizeRow($row, $line);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, int $line): array
    {
        try {
            $tags = json_decode($row['tags'], true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Tags JSON không hợp lệ ở dòng {$line}.", previous: $exception);
        }

        if (! is_array($tags)) {
            throw new RuntimeException("Tags phải là một mảng JSON ở dòng {$line}.");
        }

        return [
            'name' => $row['name'],
            'description' => $row['description'],
            'ingredients' => $row['ingredients'],
            'category' => $row['category'],
            'price' => (float) $row['price'],
            'calories' => (int) $row['calories'],
            'temperature_type' => TemperatureType::from($row['temperature_type']),
            'tags' => array_values($tags),
            'image_url' => $row['image_url'] !== '' ? $row['image_url'] : null,
            'is_available' => filter_var($row['is_available'], FILTER_VALIDATE_BOOL),
        ];
    }
}
