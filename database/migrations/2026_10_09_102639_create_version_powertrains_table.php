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
        Schema::create('version_powertrains', function (Blueprint $table) {
            $table->foreignId('version_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('engine_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('ice_power_cv')->nullable();
            $table->unsignedSmallInteger('ice_torque_nm')->nullable();
            $table->unsignedSmallInteger('ice_max_rpm')->nullable();
            $table->unsignedTinyInteger('electric_motors_count')->nullable();
            $table->unsignedSmallInteger('electric_power_cv')->nullable();
            $table->decimal('battery_kwh', 5, 2)->nullable();
            $table->unsignedSmallInteger('electric_range_km')->nullable();
            $table->unsignedSmallInteger('system_power_cv')->nullable();
            $table->unsignedSmallInteger('system_torque_nm')->nullable();
            $table->string('powertrain_type', 10)->storedAs(
                "CASE
                WHEN engine_id IS NOT NULL AND electric_power_cv IS NOT NULL THEN 'ibrido'
                WHEN engine_id IS NOT NULL THEN 'termico'
                ELSE 'elettrico'
            END"
            )->index();
            $table->enum('gearbox_type', ['manuale', 'robotizzato', 'doppia_frizione', 'sequenziale', 'automatico'])->nullable();
            $table->unsignedTinyInteger('gears')->nullable();
            $table->enum('drivetrain', ['posteriore', 'integrale'])->nullable();
            $table->decimal('fuel_consumption_l100', 4, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_powertrains');
    }
};
