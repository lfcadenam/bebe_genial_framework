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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained('api_partners')->nullOnDelete();
            $table->foreignId('course_id')->default(24)->constrained('courses')->cascadeOnDelete();
            $table->string('code', 80)->unique()->index();
            $table->string('name', 150);
            $table->unsignedInteger('book_limit')->default(12); // 0 = Libros ilimitados
            $table->json('cuentos_permitidos')->nullable();
            $table->unsignedInteger('duration_days')->default(30);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
