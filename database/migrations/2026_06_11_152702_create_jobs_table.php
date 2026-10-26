<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The canonical queue tables are already created by Laravel's base
        // migration. Preserve this inherited migration as a compatibility
        // no-op so existing production migration histories remain valid.
        if (Schema::hasTable('jobs')) {
            return;
        }
    }

    public function down(): void
    {
        // This compatibility migration does not own the canonical queue table.
    }
};
