<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminApiAuditLogController extends Controller
{
    /**
     * Métricas de auditoría en tiempo real
     */
    public function getMetrics(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $total = ApiAuditLog::count();
        $successful = ApiAuditLog::whereIn('http_status', [200, 201])->count();
        $validation = ApiAuditLog::where('http_status', 400)->count();
        $rejected = ApiAuditLog::whereIn('http_status', [401, 403, 409, 422, 500])->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_requests' => $total,
                'total_successful' => $successful,
                'total_validation' => $validation,
                'total_rejected' => $rejected,
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 100.0,
            ],
        ]);
    }

    /**
     * Listado paginado de registros de auditoría
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
        $partnerId = $request->input('partner_id');
        $statusGroup = $request->input('status_group'); // 'success', 'validation', 'error'
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $search = trim((string) $request->input('search', ''));

        $query = ApiAuditLog::with('partner');

        if ($partnerId) {
            $query->where('partner_id', (int) $partnerId);
        }

        if ($statusGroup === 'success') {
            $query->whereIn('http_status', [200, 201]);
        } elseif ($statusGroup === 'validation') {
            $query->where('http_status', 400);
        } elseif ($statusGroup === 'error') {
            $query->whereIn('http_status', [401, 403, 409, 422, 500]);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('requested_email', 'like', "%{$search}%")
                    ->orWhere('campaign_code', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
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
}
