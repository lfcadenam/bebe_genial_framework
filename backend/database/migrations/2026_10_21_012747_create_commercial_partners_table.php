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
        Schema::create('commercial_partners', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 191);
            $table->string('contact_name', 191);
            $table->string('contact_email', 191)->unique();
            $table->string('contact_phone', 50)->nullable();
            $table->boolean('is_agreement')->default(false)->index(); // false: Distribuidor, true: Convenio
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->json('allowed_courses')->nullable(); // [1, 5, 6, 24]
            $table->unsignedInteger('allowed_months')->default(12);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_partners');
    }
};
