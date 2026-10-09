<?php

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
        Schema::create('version_specs', function (Blueprint $table) {
            $table->foreignId('version_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('length_mm')->nullable();
            $table->unsignedSmallInteger('width_mm')->nullable();
            $table->unsignedSmallInteger('height_mm')->nullable();
            $table->unsignedSmallInteger('wheelbase_mm')->nullable();
            $table->unsignedSmallInteger('dry_weight_kg')->nullable();
            $table->unsignedTinyInteger('weight_front_pct')->nullable();
            $table->enum('chassis_material', ['acciaio', 'alluminio', 'fibra_di_carbonio', 'misto'])->nullable();
            $table->enum('brakes', ['tamburo', 'disco_acciaio', 'carboceramici'])->nullable();
            $table->unsignedSmallInteger('top_speed_kmh')->nullable();
            $table->decimal('zero_to_100_sec', 3, 1)->nullable();
            $table->decimal('zero_to_200_sec', 4, 1)->nullable();
            $table->decimal('fiorano_lap_sec', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_specs');
    }
};
