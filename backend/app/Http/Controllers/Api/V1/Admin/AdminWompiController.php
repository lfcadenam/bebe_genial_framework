<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WompiPlan;
use App\Models\WompiSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminWompiController extends Controller
{
    /**
     * Resumen de Métricas Financieras y KPIs de WOMPI
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

        $totalRevenue = WompiSubscription::where('status', 'APPROVED')->sum('amount');
        $approvedCount = WompiSubscription::where('status', 'APPROVED')->count();
        $declinedCount = WompiSubscription::where('status', 'DECLINED')->count();
        $pendingCount = WompiSubscription::where('status', 'PENDING')->count();
        $totalTransactions = WompiSubscription::count();
        $activePlans = WompiPlan::where('is_active', true)->count();

        $successRate = $totalTransactions > 0
            ? round(($approvedCount / $totalTransactions) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => (float) $totalRevenue,
                'formatted_revenue' => '$ ' . number_format($totalRevenue, 0, ',', '.'),
                'approved_count' => $approvedCount,
                'declined_count' => $declinedCount,
                'pending_count' => $pendingCount,
                'total_transactions' => $totalTransactions,
                'active_plans' => $activePlans,
                'success_rate' => $successRate . '%',
            ],
        ]);
    }

    /**
     * Catálogo de Planes de Pago WOMPI
     */
    public function getPlans(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $plans = WompiPlan::withCount(['subscriptions', 'subscriptions as active_subscriptions_count' => function ($q) {
            $q->where('status', 'APPROVED');
        }])->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Crear nuevo Plan de Pago WOMPI
     */
    public function storePlan(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', Rule::in(['single', 'recurring'])],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'benefits' => ['nullable', 'string'],
            'related_courses' => ['nullable', 'array'],
            'related_courses.*' => ['integer', 'exists:courses,id'],
        ]);

        $plan = WompiPlan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan de pago creado exitosamente.',
            'data' => $plan,
        ], 201);
    }

    /**
     * Actualizar Plan de Pago WOMPI
     */
    public function updatePlan(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $plan = WompiPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'code' => 'PLAN_NOT_FOUND',
                'message' => 'El plan no fue encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', Rule::in(['single', 'recurring'])],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'benefits' => ['nullable', 'string'],
            'related_courses' => ['nullable', 'array'],
            'related_courses.*' => ['integer', 'exists:courses,id'],
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan de pago actualizado exitosamente.',
            'data' => $plan,
        ]);
    }

    /**
     * Alternar estado activo / inactivo de un Plan
     */
    public function togglePlanStatus(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $plan = WompiPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'code' => 'PLAN_NOT_FOUND',
                'message' => 'El plan no existe.',
            ], 404);
        }

        $plan->is_active = !$plan->is_active;
        $plan->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado del plan actualizado.',
            'data' => $plan,
        ]);
    }

    /**
     * Monitor de Transacciones y Suscripciones WOMPI (en tiempo real)
     */
    public function getSubscriptions(Request $request): JsonResponse
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
        $planId = $request->input('plan_id');

        $query = WompiSubscription::with(['user:id,first_name,last_name,email,phone', 'plan:id,name,price,type']);

        if ($status && in_array($status, ['APPROVED', 'DECLINED', 'PENDING', 'VOIDED', 'ERROR'])) {
            $query->where('status', $status);
        }

        if ($planId) {
            $query->where('plan_id', $planId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('wompi_transaction_id', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
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
