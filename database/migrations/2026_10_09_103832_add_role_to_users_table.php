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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['master', 'admin', 'moderator'])
                ->default('moderator')
                ->after('password');

            $table->unsignedTinyInteger('master_lock')
                ->nullable()
                ->storedAs("IF(role = 'master', 1, NULL)")
                ->unique()
                ->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['master_lock', 'role']);
        });
    }
};
