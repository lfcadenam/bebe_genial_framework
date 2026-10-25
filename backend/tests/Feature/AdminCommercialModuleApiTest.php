<?php

namespace Tests\Feature;

use App\Models\CommercialPartner;
use App\Models\Course;
use App\Models\User;
use App\Models\WompiPlan;
use App\Models\WompiSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCommercialModuleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Course::create([
            'id' => 1,
            'title' => 'Método Bebé Genial',
            'is_visible' => true,
            'is_active' => true,
        ]);
        Course::create([
            'id' => 24,
            'title' => 'Cuentos Leo con LEO',
            'is_visible' => true,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_commercial_partners(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        Sanctum::actingAs($parent);

        $response = $this->getJson(route('api.v1.admin.commercial-partners.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_and_list_commercial_partners(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $courseIds = Course::pluck('id')->toArray();

        // Crear aliado comercial
        $createResponse = $this->postJson(route('api.v1.admin.commercial-partners.store'), [
            'company_name' => 'Colsubsidio Educación',
            'contact_name' => 'Ana Martínez',
            'contact_email' => 'convenios@colsubsidio.com',
            'contact_phone' => '3109876543',
            'is_agreement' => true,
            'status' => 'active',
            'allowed_courses' => $courseIds,
            'allowed_months' => 12,
            'notes' => 'Convenio corporativo 2026',
        ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'company_name' => 'Colsubsidio Educación',
                    'is_agreement' => true,
                ],
            ]);

        $this->assertDatabaseHas('commercial_partners', [
            'contact_email' => 'convenios@colsubsidio.com',
            'is_agreement' => true,
        ]);

        // Listar aliados
        $listResponse = $this->getJson(route('api.v1.admin.commercial-partners.index'));
        $listResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_and_delete_commercial_partner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $courseIds = Course::pluck('id')->toArray();

        $partner = CommercialPartner::create([
            'company_name' => 'Distribuidor Caribe',
            'contact_name' => 'Pedro Solano',
            'contact_email' => 'pedro@caribe.com',
            'is_agreement' => false,
            'status' => 'active',
            'allowed_courses' => [$courseIds[0]],
            'allowed_months' => 6,
        ]);

        // Actualizar
        $updateResponse = $this->putJson(route('api.v1.admin.commercial-partners.update', ['id' => $partner->id]), [
            'company_name' => 'Distribuidor Caribe SAS',
            'contact_name' => 'Pedro Solano Gómez',
            'contact_email' => 'pedro@caribe.com',
            'is_agreement' => false,
            'status' => 'inactive',
            'allowed_courses' => $courseIds,
            'allowed_months' => 12,
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'company_name' => 'Distribuidor Caribe SAS',
                    'status' => 'inactive',
                ],
            ]);

        // Eliminar
        $deleteResponse = $this->deleteJson(route('api.v1.admin.commercial-partners.destroy', ['id' => $partner->id]));
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('commercial_partners', ['id' => $partner->id]);
    }

    public function test_admin_can_view_students_of_commercial_partner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $partner = CommercialPartner::create([
            'company_name' => 'Alianza Jardines',
            'contact_name' => 'Laura',
            'contact_email' => 'laura@jardines.com',
            'is_agreement' => true,
            'status' => 'active',
            'allowed_months' => 12,
        ]);

        User::factory()->count(2)->create([
            'role' => 'parent',
            'created_by' => $partner->id,
        ]);

        $response = $this->getJson(route('api.v1.admin.commercial-partners.students', ['id' => $partner->id]));
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_manage_wompi_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $courseIds = Course::pluck('id')->toArray();

        // Crear plan
        $createResponse = $this->postJson(route('api.v1.admin.wompi.plans.store'), [
            'name' => 'Plan Anual Bebé Genial',
            'type' => 'recurring',
            'price' => 299000,
            'currency' => 'COP',
            'duration_months' => 12,
            'trial_days' => 7,
            'is_active' => true,
            'benefits' => 'Acceso total a todos los cursos y biblioteca ilimitada',
            'related_courses' => $courseIds,
        ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Plan Anual Bebé Genial',
                    'price' => '299000.00',
                ],
            ]);

        $planId = $createResponse->json('data.id');

        // Toggle status
        $toggleResponse = $this->postJson(route('api.v1.admin.wompi.plans.toggle', ['id' => $planId]));
        $toggleResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => false,
                ],
            ]);

        // Listar planes
        $listResponse = $this->getJson(route('api.v1.admin.wompi.plans.index'));
        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_fetch_wompi_metrics_and_subscriptions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'parent']);
        Sanctum::actingAs($admin);

        $plan = WompiPlan::create([
            'name' => 'Plan Mensual Leo',
            'type' => 'recurring',
            'price' => 49900,
            'currency' => 'COP',
            'duration_months' => 1,
            'is_active' => true,
        ]);

        WompiSubscription::create([
            'user_id' => $student->id,
            'plan_id' => $plan->id,
            'reference' => 'WOMPI-TEST-001',
            'amount' => 49900,
            'currency' => 'COP',
            'status' => 'APPROVED',
            'payment_method' => 'CARD',
        ]);

        // Métricas
        $metricsResponse = $this->getJson(route('api.v1.admin.wompi.metrics'));
        $metricsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_revenue' => 49900,
                    'approved_count' => 1,
                ],
            ]);

        // Listar transacciones / suscripciones
        $subsResponse = $this->getJson(route('api.v1.admin.wompi.subscriptions.index'));
        $subsResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
