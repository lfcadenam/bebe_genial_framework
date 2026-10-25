<?php

namespace Tests\Feature;

use App\Models\BabyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_legacy_bbp_password_and_is_rehashed_to_bcrypt(): void
    {
        $rawPassword = 'PasswordLegacy123*';
        $legacyHash = crypt($rawPassword, 'Bbp.');

        $user = User::create([
            'first_name' => 'Carolina',
            'last_name' => 'Morales',
            'email' => 'carolina.test@bebegenial.com',
            'password' => $legacyHash,
            'role' => 'parent',
            'status' => 'active',
        ]);

        BabyProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Martín Morales',
            'birth_date' => '2023-08-15',
            'avatar' => 'avatar_bebe_1.png',
        ]);

        // Verificamos que inicialmente tiene el hash legacy
        $this->assertStringStartsNotWith('$2y$', $user->password);

        // Petición al API
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'carolina.test@bebegenial.com',
            'password' => $rawPassword,
            'device_name' => 'Postman Test',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'full_name',
                        'email',
                        'role',
                        'status',
                        'baby_profile' => [
                            'id',
                            'full_name',
                            'birth_date',
                            'avatar',
                        ],
                    ],
                ],
            ]);

        // Verificamos que la contraseña se migró automáticamente en la BD a bcrypt ($2y$)
        $user->refresh();
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::create([
            'first_name' => 'Andrés',
            'last_name' => 'Gómez',
            'email' => 'andres.test@bebegenial.com',
            'password' => 'BcryptSecret123*',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $token = $user->createToken('TestToken')->plainTextToken;

        // Petición con Bearer Token
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'andres.test@bebegenial.com',
                        'first_name' => 'Andrés',
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_request_to_me_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::create([
            'first_name' => 'Laura',
            'last_name' => 'Pérez',
            'email' => 'laura.test@bebegenial.com',
            'password' => 'Secret123*',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $token = $user->createToken('LogoutToken')->plainTextToken;

        // Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente.',
            ]);

        // Verificamos que el token fue eliminado de la base de datos
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // En tests integrados de Laravel, reseteamos los guards en memoria para simular una petición HTTP limpia
        app('auth')->forgetGuards();

        // Petición subsecuente con el mismo token debe ser rechazada (401)
        $subsequentResponse = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/me');

        $subsequentResponse->assertStatus(401);
    }

    public function test_user_can_change_password_and_clears_must_change_password_flag(): void
    {
        $user = User::create([
            'first_name' => 'Mario',
            'last_name' => 'Bros',
            'email' => 'mario@bebegenial.com',
            'password' => Hash::make('OldPassword123*'),
            'role' => 'parent',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $token = $user->createToken('ChangePassToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123*',
                'password' => 'NewSecret2026*',
                'password_confirmation' => 'NewSecret2026*',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente.',
                'data' => [
                    'user' => [
                        'must_change_password' => false,
                    ],
                ],
            ]);

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewSecret2026*', $user->password));
    }

    public function test_change_password_fails_if_current_password_is_incorrect(): void
    {
        $user = User::create([
            'first_name' => 'Luigi',
            'last_name' => 'Bros',
            'email' => 'luigi@bebegenial.com',
            'password' => Hash::make('CorrectPassword123*'),
            'role' => 'parent',
            'status' => 'active',
        ]);

        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'WrongPassword!',
                'password' => 'BrandNewPassword123*',
                'password_confirmation' => 'BrandNewPassword123*',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }
}
