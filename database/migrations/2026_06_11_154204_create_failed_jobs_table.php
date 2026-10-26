<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel's base queue migration already owns the failed_jobs table.
        // Retain this inherited migration as a safe no-op for installations
        // whose migration history already references this filename.
        if (Schema::hasTable('failed_jobs')) {
            return;
        }
    }

    public function down(): void
    {
        // This compatibility migration must not drop the canonical table.
    }
};
