<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL only — SQLite stores enum as varchar and accepts any value already
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscribers MODIFY COLUMN status ENUM('active','unsubscribed','bounced','complained','erased') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscribers MODIFY COLUMN status ENUM('active','unsubscribed','bounced','complained') NOT NULL DEFAULT 'active'");
        }
    }
};
