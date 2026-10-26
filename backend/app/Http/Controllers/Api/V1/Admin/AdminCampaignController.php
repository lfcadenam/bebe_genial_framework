<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\CuentosCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $status = $request->input('status'); // 'active', 'inactive'

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
     * Catálogo interactivo de categorías y cuentos (Curso #24)
     */
    public function cuentosCatalog(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $catalog = CuentosCatalogService::getCatalog();
        $totalStories = CuentosCatalogService::getTotalStoriesCount();

        return response()->json([
            'success' => true,
            'total_stories' => $totalStories,
            'data' => $catalog,
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
            'book_limit' => 'nullable|integer|min:0',
            'duration_days' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'cuentos_seleccionados' => 'nullable|array',
            'cuentos_seleccionados.*' => 'integer|min:1',
        ]);

        $courseId = (int) $validated['course_id'];
        $cuentosPermitidos = null;
        $totalCuentosCatalog = CuentosCatalogService::getTotalStoriesCount();

        if ($courseId === 24) {
            if ($request->has('cuentos_seleccionados')) {
                $rawList = (array) $request->input('cuentos_seleccionados', []);
                $selected = array_values(array_unique(array_filter(array_map('intval', $rawList), fn($v) => $v > 0)));
                sort($selected);
                $totalSelected = count($selected);

                if ($totalSelected === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Debes seleccionar al menos un cuento interactivo para la campaña.',
                    ], 422);
                }

                if ($totalSelected >= $totalCuentosCatalog) {
                    $bookLimit = $totalCuentosCatalog;
                    $cuentosPermitidos = null; // null = acceso completo a todos los cuentos
                } else {
                    $bookLimit = $totalSelected;
                    $cuentosPermitidos = $selected;
                }
            } else {
                $bookLimit = isset($validated['book_limit']) ? (int) $validated['book_limit'] : $totalCuentosCatalog;
                if ($bookLimit < $totalCuentosCatalog && $bookLimit > 0) {
                    // Si se pasó un límite numérico sin lista, permitidos queda null o según cupo
                    $cuentosPermitidos = null;
                }
            }
        } else {
            $bookLimit = (int) ($validated['book_limit'] ?? 0);
            $cuentosPermitidos = null;
        }

        $campaign = Campaign::create([
            'partner_id' => $validated['partner_id'] ?? null,
            'course_id' => $courseId,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'book_limit' => $bookLimit,
            'cuentos_permitidos' => $cuentosPermitidos,
            'duration_days' => $validated['duration_days'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $campaign->load(['partner', 'course']);

        $detalleCupo = ($courseId === 24)
            ? ($cuentosPermitidos === null ? "con Acceso Total ({$totalCuentosCatalog} cuentos)" : "con {$bookLimit} cuento(s) seleccionado(s)")
            : "para el curso {$campaign->course?->title}";

        return response()->json([
            'success' => true,
            'message' => "Campaña '{$campaign->code}' creada exitosamente {$detalleCupo}.",
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
            'book_limit' => 'nullable|integer|min:0',
            'duration_days' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'cuentos_seleccionados' => 'nullable|array',
            'cuentos_seleccionados.*' => 'integer|min:1',
        ]);

        $courseId = (int) $validated['course_id'];
        $cuentosPermitidos = $campaign->cuentos_permitidos;
        $totalCuentosCatalog = CuentosCatalogService::getTotalStoriesCount();

        if ($courseId === 24) {
            if ($request->has('cuentos_seleccionados')) {
                $rawList = (array) $request->input('cuentos_seleccionados', []);
                $selected = array_values(array_unique(array_filter(array_map('intval', $rawList), fn($v) => $v > 0)));
                sort($selected);
                $totalSelected = count($selected);

                if ($totalSelected === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Debes seleccionar al menos un cuento interactivo para la campaña.',
                    ], 422);
                }

                if ($totalSelected >= $totalCuentosCatalog) {
                    $bookLimit = $totalCuentosCatalog;
                    $cuentosPermitidos = null;
                } else {
                    $bookLimit = $totalSelected;
                    $cuentosPermitidos = $selected;
                }
            } else {
                $bookLimit = isset($validated['book_limit']) ? (int) $validated['book_limit'] : $campaign->book_limit;
            }
        } else {
            $bookLimit = (int) ($validated['book_limit'] ?? 0);
            $cuentosPermitidos = null;
        }

        $campaign->update([
            'partner_id' => $validated['partner_id'] ?? null,
            'course_id' => $courseId,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'book_limit' => $bookLimit,
            'cuentos_permitidos' => $cuentosPermitidos,
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
     * Alumnos matriculados en una campaña específica
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

        $campaign = Campaign::with(['partner', 'course'])->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaña no encontrada.',
            ], 404);
        }

        $perPage = (int) $request->input('per_page', 15);
        $search = trim((string) $request->input('search', ''));

        $query = Enrollment::with(['user.babyProfile', 'course'])
            ->where('campaign_id', $campaign->id);

        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('babyProfile', function ($bq) use ($search) {
                        $bq->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = collect($paginated->items())->map(function (Enrollment $enrollment) {
            $user = $enrollment->user;
            $baby = $user?->babyProfile;
            $birthDate = $baby?->birth_date?->format('Y-m-d') ?? ($baby?->birth_date ? (string) $baby->birth_date : null);

            return [
                'id' => $enrollment->id,
                'user_id' => $user?->id,
                'parent_name' => $user ? $user->full_name : 'No registrado',
                'email' => $user?->email,
                'phone' => $user?->phone,
                'user_status' => $user?->status ?? 'inactive',
                'baby_name' => $baby?->full_name ?? 'Sin infante',
                'baby_birth_date' => $birthDate,
                'baby_age_text' => $this->calculateAgeText($birthDate),
                'access_type' => $enrollment->access_type,
                'book_limit' => $enrollment->book_limit,
                'origin_channel' => $enrollment->origin_channel,
                'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d'),
                'expires_at' => $enrollment->expires_at?->format('Y-m-d'),
                'is_valid' => $enrollment->isValid(),
            ];
        });

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'code' => $campaign->code,
                'name' => $campaign->name,
                'course_name' => $campaign->course?->title ?? 'N/A',
                'partner_name' => $campaign->partner?->name ?? 'Directo (Sin aliado)',
            ],
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
     * Exportación de alumnos a archivo CSV con UTF-8 BOM
     */
    public function exportStudents(Request $request, int $id): StreamedResponse|JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $campaign = Campaign::with(['partner', 'course'])->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaña no encontrada.',
            ], 404);
        }

        $enrollments = Enrollment::with(['user.babyProfile'])
            ->where('campaign_id', $campaign->id)
            ->orderBy('id', 'desc')
            ->get();

        $filename = "alumnos_campana_{$campaign->code}_" . date('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($enrollments, $campaign) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID Matrícula',
                'Campaña Código',
                'Campaña Nombre',
                'Nombre Acudiente',
                'Correo Electrónico',
                'Teléfono / Contacto',
                'Estado Usuario',
                'Nombre Menor',
                'Fecha Nac. Menor',
                'Edad Menor',
                'Tipo Acceso',
                'Cupo Cuentos',
                'Canal Origen',
                'Fecha Matrícula',
                'Fecha Vencimiento',
                'Estado Matrícula',
            ], ';');

            foreach ($enrollments as $enrollment) {
                $user = $enrollment->user;
                $baby = $user?->babyProfile;
                $birthDate = $baby?->birth_date?->format('Y-m-d') ?? ($baby?->birth_date ? (string) $baby->birth_date : null);

                fputcsv($handle, [
                    $enrollment->id,
                    $campaign->code,
                    $campaign->name,
                    $user ? $user->full_name : 'No registrado',
                    $user?->email ?? '',
                    $user?->phone ?? '',
                    $user?->status ?? 'inactive',
                    $baby?->full_name ?? 'Sin infante',
                    $birthDate ?? 'N/A',
                    $this->calculateAgeText($birthDate),
                    $enrollment->access_type,
                    $enrollment->book_limit,
                    $enrollment->origin_channel ?? 'API',
                    $enrollment->enrolled_at?->format('Y-m-d') ?? '',
                    $enrollment->expires_at?->format('Y-m-d') ?? '',
                    $enrollment->isValid() ? 'Vigente' : 'Vencida',
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
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

    /**
     * Formateador de edad para infantes
     */
    private function calculateAgeText(?string $birthDate): string
    {
        if (empty($birthDate) || $birthDate === '0000-00-00' || $birthDate === '1970-01-01') {
            return 'No registrada';
        }
        try {
            $dob = new \DateTime($birthDate);
            $now = new \DateTime();
            if ($dob > $now) {
                return 'Recién nacido';
            }
            $diff = $now->diff($dob);
            if ($diff->y > 0) {
                $txt = $diff->y . ' ' . ($diff->y === 1 ? 'año' : 'años');
                if ($diff->m > 0 && $diff->y < 5) {
                    $txt .= ' y ' . $diff->m . ' ' . ($diff->m === 1 ? 'mes' : 'meses');
                }
                return $txt;
            } elseif ($diff->m > 0) {
                return $diff->m . ' ' . ($diff->m === 1 ? 'mes' : 'meses');
            } else {
                return $diff->d . ' ' . ($diff->d === 1 ? 'día' : 'días');
            }
        } catch (\Throwable $e) {
            return 'No registrada';
        }
    }
}
