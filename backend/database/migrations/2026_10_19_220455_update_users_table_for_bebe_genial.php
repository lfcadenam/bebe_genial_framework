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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->enum('role', ['superadmin', 'admin', 'parent', 'distributor'])->default('parent')->after('phone')->index();
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active')->after('role')->index();
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->foreignId('created_by')->nullable()->after('must_change_password')->constrained('users')->nullOnDelete();
            $table->softDeletes()->after('updated_at');

            // Hacemos que la columna 'name' por defecto de Laravel sea nullable para no romper llamadas genéricas
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'role',
                'status',
                'must_change_password',
                'created_by',
                'deleted_at'
            ]);
            $table->string('name')->nullable(false)->change();
        });
    }
};
