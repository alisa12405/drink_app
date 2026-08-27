<?php

use App\Enums\TemperatureType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drinks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('ingredients')->nullable();
            $table->string('category', 100)->index();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('calories')->nullable();
            $table->enum('temperature_type', TemperatureType::cases())
                ->default(TemperatureType::Both)
                ->index();
            $table->json('tags')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->json('description_embedding')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drinks');
    }
};
