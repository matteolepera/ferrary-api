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
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('vehicle_type', ['stradale', 'pista', 'gt_corsa', 'prototipo', 'f1', 'concept']);
            $table->enum('body_type', ['coupe', 'spider', 'targa', 'barchetta', 'suv', 'monoposto'])->nullable();
            $table->string('project_code', 30)->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->unsignedSmallInteger('year_start');
            $table->unsignedSmallInteger('year_end')->nullable();
            $table->boolean('in_production')->default(false);
            $table->enum('production_type', ['serie', 'serie_limitata', 'one_off'])->nullable();
            $table->unsignedInteger('units_produced')->nullable();
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
