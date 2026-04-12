<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Sender overrides are optional — null means "use collection config defaults"
            $table->string('from_name')->nullable()->change();
            $table->string('from_email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('from_name')->nullable(false)->change();
            $table->string('from_email')->nullable(false)->change();
        });
    }
};
