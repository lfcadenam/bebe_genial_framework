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
        Schema::create('courses', function (Blueprint $table) {
            $table->id(); // Compatible con los IDs existentes (ej: 24 = Cuentos Leo con LEO, 6 = Curso, etc.)
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('cover_image', 150)->nullable();
            $table->string('background_image', 150)->nullable();
            $table->unsignedSmallInteger('hours')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
