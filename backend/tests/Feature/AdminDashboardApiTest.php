<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_fetch_dashboard_metrics(): void
    {
        $admin = User::create([
            'first_name' => 'BEBE',
            'last_name' => 'JUNIOR',
            'email' => 'spa@bebe.com',
            'password' => 'secret',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $token = $admin->createToken('AdminToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/admin/dashboard/metrics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'kpis' => [
                        'total_students',
                        'active_partners',
                        'active_campaigns',
                        'active_enrollments',
                        'stories_read',
                        'estimated_revenue',
                    ],
                    'recent_users',
                    'analytics_series',
                ],
            ]);
    }

    public function test_non_admin_user_is_forbidden_from_dashboard_metrics(): void
    {
        $parent = User::create([
            'first_name' => 'Padre',
            'last_name' => 'Familia',
            'email' => 'padre@bebe.com',
            'password' => 'secret',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $token = $parent->createToken('ParentToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/admin/dashboard/metrics');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
            ]);
    }
}
