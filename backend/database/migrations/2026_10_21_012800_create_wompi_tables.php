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
        Schema::create('wompi_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->enum('type', ['single', 'recurring'])->default('recurring');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('COP');
            $table->unsignedInteger('duration_months')->default(1);
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('benefits')->nullable();
            $table->json('related_courses')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wompi_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('wompi_plans')->cascadeOnDelete();
            $table->string('wompi_transaction_id', 120)->nullable()->index();
            $table->string('reference', 120)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('COP');
            $table->enum('status', ['APPROVED', 'DECLINED', 'PENDING', 'VOIDED', 'ERROR'])->default('PENDING')->index();
            $table->string('payment_method', 60)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wompi_subscriptions');
        Schema::dropIfExists('wompi_plans');
    }
};
