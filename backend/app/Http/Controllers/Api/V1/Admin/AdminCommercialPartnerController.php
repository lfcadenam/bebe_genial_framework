<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommercialPartner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCommercialPartnerController extends Controller
{
    /**
     * Listado paginado de distribuidores y convenios
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
        $type = $request->input('type'); // 'agreement' (convenio) | 'distributor' (distribuidor)

        $query = CommercialPartner::withCount('students');

        if ($type === 'agreement') {
            $query->where('is_agreement', true);
        } elseif ($type === 'distributor') {
            $query->where('is_agreement', false);
        }

        if ($status && in_array($status, ['active', 'inactive', 'suspended'])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%");
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
            'kpis' => [
                'total_partners' => CommercialPartner::count(),
                'total_agreements' => CommercialPartner::where('is_agreement', true)->count(),
                'total_distributors' => CommercialPartner::where('is_agreement', false)->count(),
                'active_partners' => CommercialPartner::where('status', 'active')->count(),
            ],
        ]);
    }

    /**
     * Detalle de un distribuidor o convenio
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

        $partner = CommercialPartner::withCount('students')->find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado o distribuidor no existe.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $partner,
        ]);
    }

    /**
     * Crear un nuevo convenio o distribuidor
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
            'company_name' => ['required', 'string', 'max:191'],
            'contact_name' => ['required', 'string', 'max:191'],
            'contact_email' => ['required', 'email', 'max:191', 'unique:commercial_partners,contact_email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'is_agreement' => ['required', 'boolean'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'allowed_courses' => ['nullable', 'array'],
            'allowed_courses.*' => ['integer', 'exists:courses,id'],
            'allowed_months' => ['required', 'integer', 'min:1', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $partner = CommercialPartner::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Aliado comercial registrado exitosamente.',
            'data' => $partner,
        ], 201);
    }

    /**
     * Actualizar datos del convenio o distribuidor
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

        $partner = CommercialPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado no existe.',
            ], 404);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:191'],
            'contact_name' => ['required', 'string', 'max:191'],
            'contact_email' => ['required', 'email', 'max:191', Rule::unique('commercial_partners', 'contact_email')->ignore($partner->id)],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'is_agreement' => ['required', 'boolean'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'allowed_courses' => ['nullable', 'array'],
            'allowed_courses.*' => ['integer', 'exists:courses,id'],
            'allowed_months' => ['required', 'integer', 'min:1', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $partner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Aliado comercial actualizado exitosamente.',
            'data' => $partner,
        ]);
    }

    /**
     * Eliminar aliado comercial (soft delete)
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

        $partner = CommercialPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado no existe.',
            ], 404);
        }

        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aliado comercial eliminado correctamente.',
        ]);
    }

    /**
     * Lista de alumnos vinculados a este distribuidor / convenio
     */
    public function students(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $partner = CommercialPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado no existe.',
            ], 404);
        }

        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));

        $query = User::with(['babyProfile', 'enrollments.course:id,title'])
            ->where('created_by', $partner->id)
            ->where('role', 'parent');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('babyProfile', function ($bq) use ($search) {
                        $bq->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'partner' => [
                'id' => $partner->id,
                'company_name' => $partner->company_name,
                'is_agreement' => $partner->is_agreement,
            ],
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
