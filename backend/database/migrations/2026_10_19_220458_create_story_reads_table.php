<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('story_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('story_id')->index(); // ID del cuento en la colección
            $table->dateTime('read_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Garantiza que un usuario no duplique registros de un mismo cuento (idempotencia de relectura)
            $table->unique(['user_id', 'story_id'], 'uk_user_story');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_reads');
    }
};
