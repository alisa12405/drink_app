<?php

use App\Enums\IceLevel;
use App\Enums\SugarLevel;
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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('taste_tags')->nullable();
            $table->enum('sugar_level_default', SugarLevel::cases())->default(SugarLevel::Hundred);
            $table->enum('ice_level_default', IceLevel::cases())->default(IceLevel::NormalIce);
            $table->text('allergy_notes')->nullable();
            $table->text('profile_text')->nullable();
            $table->json('profile_embedding')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
