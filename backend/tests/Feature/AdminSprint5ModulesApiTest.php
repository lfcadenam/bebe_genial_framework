<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseTrial;
use App\Models\EmailTemplate;
use App\Models\Enrollment;
use App\Models\PadresContenido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSprint5ModulesApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $parent;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_sprint5@bebe.com',
            'role' => 'admin',
        ]);

        $this->parent = User::factory()->create([
            'email' => 'parent_sprint5@bebe.com',
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'phone' => '3119876543',
            'role' => 'parent',
        ]);

        $this->course = Course::create([
            'title' => 'Método Bebé Políglota',
            'description' => 'Estimulación temprana en 7 idiomas',
            'hours' => 120,
            'price' => 1200000.00,
            'is_visible' => true,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_sprint5_endpoints(): void
    {
        $token = $this->parent->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/course-catalog')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/padres-contenidos')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/trials')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/omni-search?q=Ana')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/email-templates')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/certificates')
            ->assertStatus(403);
    }

    public function test_admin_can_crud_course_catalog(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // 1. Crear nuevo curso
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/course-catalog', [
                'title' => 'Gimnasia Cerebral Kids',
                'description' => 'Desarrollo neuronal para infantes',
                'hours' => 40,
                'price' => 450000.00,
                'is_visible' => true,
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Gimnasia Cerebral Kids');

        $courseId = $res->json('data.id');

        // 2. Listar cursos
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/course-catalog?search=Gimnasia')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Ver detalle
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/course-catalog/{$courseId}")
            ->assertStatus(200)
            ->assertJsonPath('data.hours', 40);

        // 4. Actualizar
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/course-catalog/{$courseId}", [
                'title' => 'Gimnasia Cerebral & Neurodesarrollo',
                'hours' => 60,
                'price' => 500000.00,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Gimnasia Cerebral & Neurodesarrollo');

        // 5. Toggle activo y visibilidad
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/course-catalog/{$courseId}/toggle-active")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/course-catalog/{$courseId}/toggle-visibility")
            ->assertStatus(200)
            ->assertJsonPath('data.is_visible', false);

        // 6. Eliminar curso sin matrículas
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/course-catalog/{$courseId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_cannot_delete_course_with_active_enrollments(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        Enrollment::create([
            'user_id' => $this->parent->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
            'expires_at' => now()->addMonths(6),
            'status' => 'active',
        ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/course-catalog/{$this->course->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'COURSE_HAS_ENROLLMENTS');
    }

    public function test_admin_can_crud_padres_contenidos(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // 1. Crear contenido Smart Card
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/padres-contenidos', [
                'section' => 'smart_cards',
                'category' => 'Lógico Matemática',
                'title' => 'Actividad Smart Cards 1 - Figuras Geométricas',
                'resource_type' => 'video',
                'url_video' => 'https://vimeo.com/1047009907',
                'order_index' => 1,
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.section', 'smart_cards');

        $contentId = $res->json('data.id');

        // 2. Listar contenidos filtrados
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/padres-contenidos?section=smart_cards&category=Lógico Matemática')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Actualizar
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/padres-contenidos/{$contentId}", [
                'title' => 'Actividad Smart Cards 1 - Geometría Avanzada',
                'resource_type' => 'video',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Actividad Smart Cards 1 - Geometría Avanzada');

        // 4. Toggle status
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/padres-contenidos/{$contentId}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 5. Eliminar
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/padres-contenidos/{$contentId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_manage_trials(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // 1. Otorgar trial
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/trials', [
                'user_id' => $this->parent->id,
                'course_id' => $this->course->id,
                'days' => 15,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.days', 15)
            ->assertJsonPath('data.status', 'active');

        $trialId = $res->json('data.id');

        // 2. Listar trials
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/trials?status=active')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Revocar trial
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/trials/{$trialId}/revoke")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'revoked');
    }

    public function test_admin_can_omni_search(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/omni-search?q=Ana')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'parent_sprint5@bebe.com');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/omni-search?q=3119876543')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_manage_email_templates(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // 1. Crear plantilla
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/email-templates', [
                'slug' => 'bienvenida_alumno',
                'name' => 'Bienvenida de Alumno',
                'subject' => '¡Bienvenido a Bebé Genial, {{nombre}}!',
                'body_html' => '<p>Hola {{nombre}}, tu acceso a {{curso}} está activo.</p>',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'bienvenida_alumno');

        $tplId = $res->json('data.id');

        // 2. Listar plantillas
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/email-templates')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Actualizar
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/email-templates/{$tplId}", [
                'name' => 'Bienvenida Alumno Actualizada',
                'subject' => '¡Tu aventura en Bebé Genial comienza hoy!',
                'body_html' => '<p>Estimada familia, bienvenidos.</p>',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Bienvenida Alumno Actualizada');
    }

    public function test_admin_can_manage_certificates(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // 1. Emitir certificado
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/certificates', [
                'user_id' => $this->parent->id,
                'course_id' => $this->course->id,
                'certificate_code' => 'DIPLOMA-BBG-2026-001',
                'grade' => 'Sobresaliente',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.certificate_code', 'DIPLOMA-BBG-2026-001');

        $certId = $res->json('data.id');

        // 2. Listar certificados
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/certificates?search=DIPLOMA-BBG')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Revocar certificado
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/certificates/{$certId}/revoke")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'revoked');
    }
}
