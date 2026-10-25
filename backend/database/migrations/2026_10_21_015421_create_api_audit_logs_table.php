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
        Schema::create('api_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained('api_partners')->nullOnDelete();
            $table->string('campaign_code', 80)->nullable()->index();
            $table->string('endpoint', 255)->default('/api/v1/leoconleo/registro');
            $table->string('method', 10)->default('POST');
            $table->unsignedSmallInteger('http_status')->default(200)->index();
            $table->string('ip_address', 45)->default('127.0.0.1');
            $table->string('requested_email', 150)->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_audit_logs');
    }
};
