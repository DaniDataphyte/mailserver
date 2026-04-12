<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('targetable_type')->nullable();
            $table->unsignedBigInteger('targetable_id')->nullable();
            $table->boolean('send_to_all')->default(false);
            $table->timestamps();

            $table->index(['targetable_type', 'targetable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_audiences');
    }
};
