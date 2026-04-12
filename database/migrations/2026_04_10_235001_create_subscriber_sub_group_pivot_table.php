<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriber_sub_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_sub_group_id')
                ->constrained('subscriber_sub_groups')
                ->cascadeOnDelete();
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();

            $table->unique(['subscriber_id', 'subscriber_sub_group_id'], 'sub_group_subscriber_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_sub_group');
    }
};
