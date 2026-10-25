<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Retorna la información del usuario autenticado y su perfil de infante
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('babyProfile');

        return response()->json([
            'success' => true,
            'message' => 'Perfil del usuario autenticado obtenido con éxito.',
            'data' => [
                'user' => new UserResource($user),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ],
        ]);
    }
}
