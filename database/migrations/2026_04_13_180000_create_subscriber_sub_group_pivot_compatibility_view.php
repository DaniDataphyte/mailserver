<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'subscriber_sub_group_pivot')
            ->exists();

        if ($exists) {
            return;
        }

        DB::statement('CREATE VIEW subscriber_sub_group_pivot AS SELECT * FROM subscriber_sub_group');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS subscriber_sub_group_pivot');
    }
};
