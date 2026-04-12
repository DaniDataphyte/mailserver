<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriber_sub_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['subscriber_group_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_sub_groups');
    }
};
