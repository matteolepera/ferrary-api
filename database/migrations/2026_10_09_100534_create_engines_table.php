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
        Schema::create('engines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100)->nullable();
            $table->enum('architecture', ['V', 'piatto', 'in_linea']);
            $table->unsignedSmallInteger('v_angle_deg')->nullable();
            $table->unsignedTinyInteger('cylinders');
            $table->unsignedSmallInteger('displacement_cc');
            $table->decimal('bore_mm', 5, 2)->nullable();
            $table->decimal('stroke_mm', 5, 2)->nullable();
            $table->unsignedTinyInteger('valves_per_cylinder')->nullable();
            $table->enum('aspiration', ['aspirato', 'turbo', 'compressore']);
            $table->json('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engines');
    }
};
