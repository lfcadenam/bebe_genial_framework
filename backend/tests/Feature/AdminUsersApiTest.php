<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUsersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Course::create([
            'id' => 1,
            'title' => 'Método Bebé Genial',
            'description' => 'Curso integral',
            'is_visible' => true,
            'is_active' => true,
        ]);

        Course::create([
            'id' => 24,
            'title' => 'Cuentos Leo con LEO',
            'description' => 'Biblioteca interactiva',
            'is_visible' => true,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_users_list(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
        ]);

        Sanctum::actingAs($parent);

        $response = $this->getJson(route('api.v1.admin.users.index'));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
            ]);
    }

    public function test_admin_can_list_users_paginated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        User::factory()->count(3)->create([
            'role' => 'parent',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(route('api.v1.admin.users.index'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'email',
                        'role',
                        'status',
                        'total_enrollments',
                        'active_enrollments',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total',
                ],
            ]);
    }

    public function test_admin_can_view_user_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'first_name' => 'Carlos',
            'last_name' => 'Gómez',
            'role' => 'parent',
        ]);

        $student->babyProfile()->create([
            'full_name' => 'Bebé Carlitos',
            'birth_date' => '2023-05-10',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(route('api.v1.admin.users.show', ['id' => $student->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $student->id,
                    'first_name' => 'Carlos',
                    'baby_profile' => [
                        'full_name' => 'Bebé Carlitos',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_user_and_baby_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'parent']);

        Sanctum::actingAs($admin);

        $payload = [
            'first_name' => 'María',
            'last_name' => 'Rodríguez',
            'email' => 'maria.rodriguez@example.com',
            'phone' => '3001234567',
            'role' => 'parent',
            'status' => 'active',
            'baby_full_name' => 'Sofía Rodríguez',
            'baby_birth_date' => '2022-08-15',
            'baby_school_grade' => 'Párvulos',
            'baby_school_name' => 'Jardín Infantil Sol',
        ];

        $response = $this->putJson(route('api.v1.admin.users.update', ['id' => $student->id]), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'email' => 'maria.rodriguez@example.com',
            'first_name' => 'María',
        ]);

        $this->assertDatabaseHas('baby_profiles', [
            'user_id' => $student->id,
            'full_name' => 'Sofía Rodríguez',
            'school_grade' => 'Párvulos',
        ]);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'parent']);

        Sanctum::actingAs($admin);

        $response = $this->postJson(route('api.v1.admin.users.reset-password', ['id' => $student->id]), [
            'password' => 'nuevaClave2026',
            'must_change_password' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $student->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nuevaClave2026', $student->password));
        $this->assertTrue($student->must_change_password);
    }

    public function test_admin_can_store_and_destroy_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'parent']);

        Sanctum::actingAs($admin);

        $course = Course::first();

        // Asignar matrícula
        $storeResponse = $this->postJson(route('api.v1.admin.users.enrollments.store', ['id' => $student->id]), [
            'course_id' => $course->id,
            'access_type' => 'FREEMIUM',
            'book_limit' => 10,
            'duration_days' => 90,
        ]);

        $storeResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Matrícula asignada correctamente.',
            ]);

        $enrollmentId = $storeResponse->json('data.id');

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollmentId,
            'user_id' => $student->id,
            'course_id' => $course->id,
            'book_limit' => 10,
        ]);

        // Eliminar matrícula
        $deleteResponse = $this->deleteJson(route('api.v1.admin.enrollments.destroy', ['id' => $enrollmentId]));
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Matrícula eliminada correctamente.',
            ]);

        $this->assertSoftDeleted('enrollments', ['id' => $enrollmentId]);
    }

    public function test_admin_can_list_courses_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson(route('api.v1.admin.courses.index'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }
}
