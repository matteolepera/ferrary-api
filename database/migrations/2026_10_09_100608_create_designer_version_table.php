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
        Schema::create('designer_version', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['design', 'carrozzeria'])->default('design');
            $table->unique(['designer_id', 'version_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designer_version');
    }
};
