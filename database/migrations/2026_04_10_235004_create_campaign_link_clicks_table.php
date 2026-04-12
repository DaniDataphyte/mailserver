<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_send_id')
                ->constrained('campaign_sends')
                ->cascadeOnDelete();
            $table->text('url');
            $table->timestamp('clicked_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('campaign_send_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_link_clicks');
    }
};
