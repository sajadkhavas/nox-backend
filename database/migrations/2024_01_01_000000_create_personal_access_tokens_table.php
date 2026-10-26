<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The canonical Sanctum table is already created by the 2019 migration.
        // Keep this inherited duplicate migration as a safe no-op so existing
        // migration histories remain valid without recreating the same table.
        if (Schema::hasTable('personal_access_tokens')) {
            return;
        }
    }

    public function down(): void
    {
        // This compatibility migration does not own the canonical table and
        // must never drop data created by the earlier Sanctum migration.
    }
};
