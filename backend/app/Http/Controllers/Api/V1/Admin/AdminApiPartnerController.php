<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminApiPartnerController extends Controller
{
    /**
     * Listado paginado de partners/aliados B2B
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
        $status = $request->input('status');

        $query = ApiPartner::withCount(['campaigns', 'auditLogs']);

        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('partner_code', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('origin_code', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('origin_code', 'asc')->paginate($perPage);

        // Estimar próximo origin_code para facilitar la creación en el frontend
        $nextOriginCode = ((int) ApiPartner::max('origin_code')) + 1;

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'next_origin_code' => $nextOriginCode,
            ],
        ]);
    }

    /**
     * Crear nuevo partner / aliado B2B
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
            'name' => 'required|string|max:120',
            'partner_code' => 'required|string|max:60|regex:/^[a-zA-Z0-9_\-]+$/|unique:api_partners,partner_code',
            'contact_name' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email|max:150',
            'ip_whitelist' => 'nullable|string',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'origin_code' => 'nullable|integer|unique:api_partners,origin_code',
        ]);

        $originCode = $validated['origin_code'] ?? (((int) ApiPartner::max('origin_code')) + 1);
        $apiKey = 'bbg_live_' . bin2hex(random_bytes(16));

        $partner = ApiPartner::create([
            'origin_code' => $originCode,
            'name' => $validated['name'],
            'partner_code' => strtolower($validated['partner_code']),
            'api_key' => $apiKey,
            'ip_whitelist' => $validated['ip_whitelist'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Aliado comercial '{$partner->name}' creado exitosamente.",
            'data' => $partner,
        ], 201);
    }

    /**
     * Ver detalle de partner
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

        $partner = ApiPartner::withCount(['campaigns', 'auditLogs'])->find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Aliado no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $partner,
        ]);
    }

    /**
     * Actualizar datos del partner
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

        $partner = ApiPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Aliado no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'partner_code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-zA-Z0-9_\-]+$/',
                Rule::unique('api_partners', 'partner_code')->ignore($partner->id),
            ],
            'contact_name' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email|max:150',
            'ip_whitelist' => 'nullable|string',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $partner->update([
            'name' => $validated['name'],
            'partner_code' => strtolower($validated['partner_code']),
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'ip_whitelist' => $validated['ip_whitelist'] ?? null,
            'status' => $validated['status'] ?? $partner->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Datos de '{$partner->name}' actualizados correctamente.",
            'data' => $partner->fresh(),
        ]);
    }

    /**
     * Cambiar estado activo / inactivo
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

        $partner = ApiPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Aliado no encontrado.',
            ], 404);
        }

        $partner->status = $partner->status === 'active' ? 'inactive' : 'active';
        $partner->save();

        return response()->json([
            'success' => true,
            'message' => "Estado de '{$partner->name}' cambiado a '{$partner->status}'.",
            'data' => $partner,
        ]);
    }

    /**
     * Regenerar API Key del partner
     */
    public function regenerateKey(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $partner = ApiPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Aliado no encontrado.',
            ], 404);
        }

        $newKey = 'bbg_live_' . bin2hex(random_bytes(16));
        $partner->api_key = $newKey;
        $partner->save();

        return response()->json([
            'success' => true,
            'message' => "API Key regenerada con éxito para '{$partner->name}'.",
            'data' => [
                'id' => $partner->id,
                'api_key' => $newKey,
            ],
        ]);
    }

    /**
     * Eliminar partner
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

        $partner = ApiPartner::withCount('campaigns')->find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Aliado no encontrado.',
            ], 404);
        }

        if ($partner->campaigns_count > 0) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_HAS_CAMPAIGNS',
                'message' => "No se puede eliminar el aliado porque tiene {$partner->campaigns_count} campaña(s) asociada(s). Desactívalo en su lugar.",
            ], 422);
        }

        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => "Aliado '{$partner->name}' eliminado correctamente.",
        ]);
    }
}
