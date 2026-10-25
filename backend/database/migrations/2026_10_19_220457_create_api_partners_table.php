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
        Schema::create('api_partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('origin_code')->unique()->index();
            $table->string('name', 120);
            $table->string('partner_code', 60)->unique()->index();
            $table->string('api_key', 128)->unique()->index();
            $table->text('ip_whitelist')->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_partners');
    }
};
