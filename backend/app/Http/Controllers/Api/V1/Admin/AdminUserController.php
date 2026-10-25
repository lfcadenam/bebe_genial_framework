<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BabyProfile;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Lista paginada de usuarios con filtros por búsqueda, rol y estado
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

        $perPage = (int) $request->input('per_page', 15);
        $search = trim((string) $request->input('search', ''));
        $role = $request->input('role');
        $status = $request->input('status');

        $query = User::with(['babyProfile', 'enrollments'])
            ->withCount([
                'enrollments as total_enrollments',
                'enrollments as active_enrollments' => function ($q) {
                    $q->where('expires_at', '>=', now()->toDateString());
                },
            ]);

        // Filtro por rol
        if ($role && in_array($role, ['admin', 'parent', 'supervisor'])) {
            $query->where('role', $role);
        }

        // Filtro por estado
        if ($status && in_array($status, ['active', 'inactive', 'suspended'])) {
            $query->where('status', $status);
        }

        // Filtro por búsqueda de texto
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('babyProfile', function ($bq) use ($search) {
                        $bq->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginated->items())->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'status' => $u->status,
                'must_change_password' => (bool) $u->must_change_password,
                'created_at' => $u->created_at?->toIso8601String(),
                'baby_profile' => $u->babyProfile ? [
                    'id' => $u->babyProfile->id,
                    'full_name' => $u->babyProfile->full_name,
                    'birth_date' => $u->babyProfile->birth_date?->format('Y-m-d'),
                    'school_grade' => $u->babyProfile->school_grade,
                    'school_name' => $u->babyProfile->school_name,
                ] : null,
                'total_enrollments' => (int) $u->total_enrollments,
                'active_enrollments' => (int) $u->active_enrollments,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Obtiene el detalle completo de un usuario, su perfil de menor y matrículas
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $user = User::with([
            'babyProfile',
            'enrollments.course:id,title,hours,price',
            'enrollments.campaign:id,title,partner_name',
        ])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        $enrollments = $user->enrollments->map(function (Enrollment $e) {
            return [
                'id' => $e->id,
                'course_id' => $e->course_id,
                'course_title' => $e->course?->title ?? 'Curso #' . $e->course_id,
                'campaign_id' => $e->campaign_id,
                'campaign_title' => $e->campaign?->title,
                'partner_name' => $e->campaign?->partner_name,
                'enrolled_at' => $e->enrolled_at?->format('Y-m-d'),
                'expires_at' => $e->expires_at?->format('Y-m-d'),
                'access_type' => $e->access_type,
                'book_limit' => $e->book_limit,
                'origin_channel' => $e->origin_channel,
                'is_valid' => $e->isValid(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'must_change_password' => (bool) $user->must_change_password,
                'created_at' => $user->created_at?->toIso8601String(),
                'baby_profile' => $user->babyProfile ? [
                    'id' => $user->babyProfile->id,
                    'full_name' => $user->babyProfile->full_name,
                    'birth_date' => $user->babyProfile->birth_date?->format('Y-m-d'),
                    'school_grade' => $user->babyProfile->school_grade,
                    'school_name' => $user->babyProfile->school_name,
                ] : null,
                'enrollments' => $enrollments,
            ],
        ]);
    }

    /**
     * Actualiza los datos del usuario y de su menor asociado
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'El usuario no fue encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', Rule::in(['admin', 'parent', 'supervisor'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'must_change_password' => ['nullable', 'boolean'],
            // Perfil de bebé opcional
            'baby_full_name' => ['nullable', 'string', 'max:150'],
            'baby_birth_date' => ['nullable', 'date'],
            'baby_school_grade' => ['nullable', 'string', 'max:50'],
            'baby_school_name' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? '',
                'name' => trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? '')),
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
                'status' => $validated['status'],
                'must_change_password' => $validated['must_change_password'] ?? $user->must_change_password,
            ]);

            // Actualizar o crear perfil de bebé si se proporcionan datos
            if (!empty($validated['baby_full_name'])) {
                BabyProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'full_name' => $validated['baby_full_name'],
                        'birth_date' => $validated['baby_birth_date'] ?? null,
                        'school_grade' => $validated['baby_school_grade'] ?? null,
                        'school_name' => $validated['baby_school_name'] ?? null,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => $user->fresh(['babyProfile']),
        ]);
    }

    /**
     * Resetea la contraseña de un usuario a un valor especificado o a 123 por defecto
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'El usuario no fue encontrado.',
            ], 404);
        }

        $newPassword = $request->input('password', '123');
        $mustChange = (bool) $request->input('must_change_password', true);

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => $mustChange,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Contraseña restablecida exitosamente a '{$newPassword}'.",
        ]);
    }

    /**
     * Otorga o actualiza la matrícula de un curso para el usuario
     */
    public function storeEnrollment(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'El usuario no existe.',
            ], 404);
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'access_type' => ['required', 'string', Rule::in(['FULL', 'FREEMIUM'])],
            'book_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $expiresAt = !empty($validated['expires_at'])
            ? $validated['expires_at']
            : now()->addDays($validated['duration_days'] ?? 365)->toDateString();

        $enrollment = Enrollment::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $validated['course_id'],
            ],
            [
                'enrolled_at' => now()->toDateString(),
                'expires_at' => $expiresAt,
                'access_type' => $validated['access_type'],
                'book_limit' => $validated['access_type'] === 'FREEMIUM' ? ($validated['book_limit'] ?? 5) : null,
                'origin_channel' => 'admin_manual',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Matrícula asignada correctamente.',
            'data' => $enrollment->load('course:id,title'),
        ]);
    }

    /**
     * Cancela o elimina una matrícula existente
     */
    public function destroyEnrollment(Request $request, int $enrollmentId): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $enrollment = Enrollment::find($enrollmentId);
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'code' => 'ENROLLMENT_NOT_FOUND',
                'message' => 'La matrícula no fue encontrada.',
            ], 404);
        }

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Matrícula eliminada correctamente.',
        ]);
    }
}
