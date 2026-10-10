<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE version_racing DROP CHECK chk_racing_podiums_races');
        DB::statement('ALTER TABLE version_racing ADD CONSTRAINT chk_racing_wins_races CHECK (wins IS NULL OR races IS NULL OR wins <= races)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE version_racing DROP CHECK chk_racing_wins_races');
        DB::statement('ALTER TABLE version_racing ADD CONSTRAINT chk_racing_podiums_races CHECK (podiums IS NULL OR races IS NULL OR podiums <= races)');
    }
};
