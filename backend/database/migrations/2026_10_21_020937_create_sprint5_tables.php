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
        // 1. Contenidos interactivos para padres y Smart Cards
        Schema::create('padres_contenidos', function (Blueprint $table) {
            $table->id();
            $table->string('section', 50)->default('smart_cards')->index(); // 'smart_cards' | 'aprendamos_juntos'
            $table->string('category', 80)->nullable()->index(); // ej. 'Lógico Matemática', 'Visual Espacial', 'Tips Nutrición'
            $table->string('title', 180);
            $table->string('resource_type', 30)->default('video'); // 'video' | 'pdf' | 'article'
            $table->string('url_video', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('allow_download')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Códigos demo y cursos triales
        Schema::create('course_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedSmallInteger('days')->default(15);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active')->index();
            $table->timestamps();
        });

        // 3. Plantillas de correo electrónico
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('subject', 200);
            $table->longText('body_html');
            $table->json('available_variables')->nullable();
            $table->timestamps();
        });

        // 4. Certificados y Diplomas emitidos
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('certificate_code', 60)->unique();
            $table->string('grade', 20)->default('Aprobado');
            $table->string('file_path', 255)->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->enum('status', ['valid', 'revoked'])->default('valid')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('course_trials');
        Schema::dropIfExists('padres_contenidos');
    }
};
