<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Autentica un usuario con soporte para contraseñas legacy y re-hash automático
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $user = User::with('babyProfile')->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales suministradas son incorrectas o el usuario no existe.'],
            ]);
        }

        // Si la contraseña fue verificada con el algoritmo legacy de Bebé Genial, se rehashea automáticamente a bcrypt
        if (Hash::needsRehash($user->password)) {
            $user->password = Hash::make($password);
            $user->save();
        }

        // Validar si el usuario está activo
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Tu cuenta se encuentra inactiva o suspendida. Por favor contacta a soporte.',
            ], 403);
        }

        // Generar token personal de Sanctum
        $deviceName = $request->input('device_name', 'Web Client');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ],
        ]);
    }
}
