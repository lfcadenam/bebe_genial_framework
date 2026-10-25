<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCampaignController extends Controller
{
    /**
     * Listado paginado de campañas B2B
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
        $partnerId = $request->input('partner_id');
        $status = $request->input('status'); // 'active', 'inactive', 'expired'

        $query = Campaign::with(['partner', 'course'])->withCount('enrollments');

        if ($partnerId) {
            $query->where('partner_id', (int) $partnerId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
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
     * Crear nueva campaña
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
            'code' => 'required|string|max:80|regex:/^[A-Z0-9_\-]+$/i|unique:campaigns,code',
            'name' => 'required|string|max:150',
            'partner_id' => 'nullable|exists:api_partners,id',
            'course_id' => 'required|exists:courses,id',
            'book_limit' => 'required|integer|min:0', // 0 = ilimitado
            'duration_days' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $campaign = Campaign::create([
            'partner_id' => $validated['partner_id'] ?? null,
            'course_id' => $validated['course_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'book_limit' => $validated['book_limit'],
            'duration_days' => $validated['duration_days'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $campaign->load(['partner', 'course']);

        return response()->json([
            'success' => true,
            'message' => "Campaña '{$campaign->code}' creada exitosamente.",
            'data' => $campaign,
        ], 201);
    }

    /**
     * Detalle de campaña
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

        $campaign = Campaign::with(['partner', 'course'])->withCount('enrollments')->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaña no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    /**
     * Actualizar campaña
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

        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaña no encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z0-9_\-]+$/i',
                Rule::unique('campaigns', 'code')->ignore($campaign->id),
            ],
            'name' => 'required|string|max:150',
            'partner_id' => 'nullable|exists:api_partners,id',
            'course_id' => 'required|exists:courses,id',
            'book_limit' => 'required|integer|min:0',
            'duration_days' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $campaign->update([
            'partner_id' => $validated['partner_id'] ?? null,
            'course_id' => $validated['course_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'book_limit' => $validated['book_limit'],
            'duration_days' => $validated['duration_days'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? $campaign->is_active,
        ]);

        $campaign->load(['partner', 'course']);

        return response()->json([
            'success' => true,
            'message' => "Campaña '{$campaign->code}' actualizada correctamente.",
            'data' => $campaign,
        ]);
    }

    /**
     * Cambiar estado activo / pausado
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaña no encontrada.',
            ], 404);
        }

        $campaign->is_active = !$campaign->is_active;
        $campaign->save();

        $estadoStr = $campaign->is_active ? 'activada' : 'pausada';

        return response()->json([
            'success' => true,
            'message' => "Campaña '{$campaign->code}' {$estadoStr} correctamente.",
            'data' => $campaign,
        ]);
    }

    /**
     * Eliminar campaña
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $campaign = Campaign::withCount('enrollments')->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaña no encontrada.',
            ], 404);
        }

        if ($campaign->enrollments_count > 0) {
            return response()->json([
                'success' => false,
                'code' => 'CAMPAIGN_HAS_ENROLLMENTS',
                'message' => "No es posible eliminar la campaña '{$campaign->code}' porque tiene {$campaign->enrollments_count} alumno(s) matriculado(s). Pausa la campaña en su lugar.",
            ], 422);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => "Campaña '{$campaign->code}' eliminada correctamente.",
        ]);
    }
}
