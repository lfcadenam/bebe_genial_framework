<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseTrial;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTrialController extends Controller
{
    /**
     * Listado paginado de accesos de prueba / trials
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $perPage = (int) $request->input('per_page', 20);
        $search = trim((string) $request->input('search', ''));
        $courseId = $request->input('course_id');
        $status = $request->input('status'); // 'active', 'expired', 'revoked'

        $query = CourseTrial::with(['user', 'course']);

        if ($courseId) {
            $query->where('course_id', (int) $courseId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Otorgar acceso trial a un usuario
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'days' => 'required|integer|min:1|max:365',
        ]);

        $userId = $validated['user_id'];
        $courseId = $validated['course_id'];
        $days = (int) $validated['days'];

        // Verificar si el usuario ya tiene matrícula activa
        $hasEnrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();

        if ($hasEnrollment) {
            return response()->json([
                'success' => false,
                'code' => 'USER_ALREADY_ENROLLED',
                'message' => 'El usuario ya cuenta con una matrícula activa para este curso.',
            ], 422);
        }

        $startsAt = now()->toDateString();
        $expiresAt = now()->addDays($days)->toDateString();

        $trial = CourseTrial::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'days' => $days,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        $trial->load(['user', 'course']);

        return response()->json([
            'success' => true,
            'message' => "Acceso de prueba de {$days} días otorgado exitosamente.",
            'data' => $trial,
        ], 201);
    }

    /**
     * Revocar acceso trial
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $trial = CourseTrial::find($id);

        if (!$trial) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso trial no encontrado.',
            ], 404);
        }

        $trial->status = 'revoked';
        $trial->save();

        return response()->json([
            'success' => true,
            'message' => 'Acceso trial revocado correctamente.',
            'data' => $trial,
        ]);
    }
}
