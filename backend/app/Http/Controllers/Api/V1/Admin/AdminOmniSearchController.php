<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOmniSearchController extends Controller
{
    /**
     * Búsqueda omnicanal avanzada para soporte, contratos y consultas
     */
    public function search(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $term = trim((string) $request->input('q', ''));

        if ($term === '' || strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => ['total' => 0],
            ]);
        }

        $users = User::with(['babyProfile', 'enrollments.course'])
            ->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('babyProfile', function ($b) use ($term) {
                        $b->where('baby_name', 'like', "%{$term}%");
                    });
            })
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => $users->count(),
            ],
        ]);
    }
}
