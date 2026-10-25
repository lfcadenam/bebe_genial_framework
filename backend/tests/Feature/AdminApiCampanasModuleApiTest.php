<?php

namespace Tests\Feature;

use App\Models\ApiAuditLog;
use App\Models\ApiPartner;
use App\Models\Campaign;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiCampanasModuleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $parent;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_campanas@bebe.com',
            'role' => 'admin',
        ]);

        $this->parent = User::factory()->create([
            'email' => 'parent_campanas@bebe.com',
            'role' => 'parent',
        ]);

        $this->course = Course::firstOrCreate(
            ['slug' => 'leo-con-leo'],
            ['title' => 'Leo con LEO', 'status' => 'active']
        );
    }

    public function test_non_admin_cannot_access_b2b_endpoints(): void
    {
        $token = $this->parent->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/api-partners')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/campaigns')
            ->assertStatus(403);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/api-audit-logs')
            ->assertStatus(403);
    }

    public function test_admin_can_crud_api_partners(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        // 1. Crear partner
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/api-partners', [
                'name' => 'Colsubsidio Alianzas',
                'partner_code' => 'colsubsidio',
                'contact_name' => 'Dr. Carlos Pardo',
                'contact_email' => 'carlos@colsubsidio.com',
                'ip_whitelist' => "190.14.25.1\n190.14.25.2",
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Colsubsidio Alianzas');

        $partnerId = $createRes->json('data.id');
        $this->assertNotEmpty($createRes->json('data.api_key'));
        $this->assertStringStartsWith('bbg_live_', $createRes->json('data.api_key'));

        // 2. Listar partners
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/api-partners?search=colsubsidio')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Ver detalle
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/api-partners/{$partnerId}")
            ->assertStatus(200)
            ->assertJsonPath('data.partner_code', 'colsubsidio');

        // 4. Actualizar partner
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/api-partners/{$partnerId}", [
                'name' => 'Colsubsidio Educación',
                'partner_code' => 'colsubsidio_edu',
                'contact_name' => 'Carlos Pardo M.',
                'contact_email' => 'carlos.pardo@colsubsidio.com',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Colsubsidio Educación');

        // 5. Toggle status
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/api-partners/{$partnerId}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        // 6. Regenerate API key
        $oldKey = $createRes->json('data.api_key');
        $regenRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/api-partners/{$partnerId}/regenerate-key")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNotEquals($oldKey, $regenRes->json('data.api_key'));

        // 7. Eliminar partner sin campañas
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/api-partners/{$partnerId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('api_partners', ['id' => $partnerId]);
    }

    public function test_cannot_delete_partner_with_associated_campaigns(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $partner = ApiPartner::create([
            'origin_code' => 50,
            'name' => 'Compensar B2B',
            'partner_code' => 'compensar',
            'api_key' => 'bbg_live_test123',
            'status' => 'active',
        ]);

        Campaign::create([
            'partner_id' => $partner->id,
            'course_id' => $this->course->id,
            'code' => 'COMPENSAR_2026',
            'name' => 'Campaña Escolar Compensar',
            'book_limit' => 10,
            'duration_days' => 90,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/api-partners/{$partner->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'PARTNER_HAS_CAMPAIGNS');
    }

    public function test_admin_can_crud_campaigns(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $partner = ApiPartner::create([
            'origin_code' => 10,
            'name' => 'Alianza Cafam',
            'partner_code' => 'cafam',
            'api_key' => 'bbg_live_cafam123',
            'status' => 'active',
        ]);

        // 1. Crear campaña
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/campaigns', [
                'partner_id' => $partner->id,
                'course_id' => $this->course->id,
                'code' => 'CAFAM_VACACIONES',
                'name' => 'Vacaciones Divertidas Cafam',
                'book_limit' => 5, // 5 libros
                'duration_days' => 60,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonths(2)->toDateString(),
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'CAFAM_VACACIONES')
            ->assertJsonPath('data.book_limit', 5);

        $campaignId = $createRes->json('data.id');

        // 2. Listar campañas con filtro por partner
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/campaigns?partner_id={$partner->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Ver detalle
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/campaigns/{$campaignId}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Vacaciones Divertidas Cafam');

        // 4. Actualizar campaña
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/admin/campaigns/{$campaignId}", [
                'partner_id' => $partner->id,
                'course_id' => $this->course->id,
                'code' => 'CAFAM_VACACIONES_2026',
                'name' => 'Vacaciones Divertidas 2026 Cafam',
                'book_limit' => 0, // ilimitado
                'duration_days' => 90,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonths(3)->toDateString(),
                'is_active' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.code', 'CAFAM_VACACIONES_2026')
            ->assertJsonPath('data.book_limit', 0);

        // 5. Toggle status
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/campaigns/{$campaignId}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 6. Eliminar campaña sin matrículas
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/campaigns/{$campaignId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('campaigns', ['id' => $campaignId]);
    }

    public function test_cannot_delete_campaign_with_enrollments(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $campaign = Campaign::create([
            'partner_id' => null,
            'course_id' => $this->course->id,
            'code' => 'PROMO_DIRECTA',
            'name' => 'Promoción Directa 2026',
            'book_limit' => 12,
            'duration_days' => 30,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ]);

        Enrollment::create([
            'user_id' => $this->parent->id,
            'course_id' => $this->course->id,
            'campaign_id' => $campaign->id,
            'enrolled_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/campaigns/{$campaign->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'CAMPAIGN_HAS_ENROLLMENTS');
    }

    public function test_admin_can_fetch_audit_metrics_and_logs(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $partner = ApiPartner::create([
            'origin_code' => 88,
            'name' => 'Partner Audit Test',
            'partner_code' => 'part_audit',
            'api_key' => 'bbg_live_audit_test',
            'status' => 'active',
        ]);

        // Crear logs de prueba
        ApiAuditLog::create([
            'partner_id' => $partner->id,
            'campaign_code' => 'CAMP_TEST',
            'endpoint' => '/api/v1/leoconleo/registro',
            'method' => 'POST',
            'http_status' => 200,
            'ip_address' => '186.28.10.5',
            'requested_email' => 'familia1@test.com',
            'payload' => ['email' => 'familia1@test.com', 'codigo_campana' => 'CAMP_TEST'],
            'response' => ['success' => true, 'message' => 'Matrícula exitosa'],
            'execution_time_ms' => 45,
            'created_at' => now(),
        ]);

        ApiAuditLog::create([
            'partner_id' => $partner->id,
            'campaign_code' => 'CAMP_TEST',
            'endpoint' => '/api/v1/leoconleo/registro',
            'method' => 'POST',
            'http_status' => 400,
            'ip_address' => '186.28.10.5',
            'requested_email' => 'familia_invalida@test.com',
            'payload' => ['email' => 'familia_invalida'],
            'response' => ['success' => false, 'message' => 'Correo inválido'],
            'execution_time_ms' => 12,
            'created_at' => now(),
        ]);

        // 1. Métricas
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/api-audit-logs/metrics')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_requests', 2)
            ->assertJsonPath('data.total_successful', 1)
            ->assertJsonPath('data.total_validation', 1);

        // 2. Listado filtrado por status_group
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/api-audit-logs?status_group=success')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.http_status', 200);

        // 3. Búsqueda por email
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/api-audit-logs?search=familia1')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }
}
