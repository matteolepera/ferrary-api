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
        Schema::create('version_racing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained()->cascadeOnDelete();
            $table->string('championship', 100);
            $table->unsignedSmallInteger('season_start');
            $table->unsignedSmallInteger('season_end')->nullable();
            $table->unsignedSmallInteger('races')->nullable();
            $table->unsignedSmallInteger('wins')->nullable();
            $table->unsignedSmallInteger('poles')->nullable();
            $table->unsignedSmallInteger('podiums')->nullable();
            $table->unsignedSmallInteger('fastest_laps')->nullable();
            $table->unsignedTinyInteger('drivers_titles')->nullable();
            $table->unsignedTinyInteger('constructors_titles')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
            $table->unique(['version_id', 'championship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_racing');
    }
};
