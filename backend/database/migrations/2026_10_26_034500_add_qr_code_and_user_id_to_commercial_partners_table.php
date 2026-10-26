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
        Schema::table('commercial_partners', function (Blueprint $table) {
            $table->string('qr_code', 32)->nullable()->unique()->after('status');
            $table->foreignId('user_id')->nullable()->after('qr_code')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_partners', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['qr_code', 'user_id']);
        });
    }
};
