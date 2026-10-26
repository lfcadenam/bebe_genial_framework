<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommercialPartner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $password = $validated['password'] ?? null;
        unset($validated['password']);

        // Generar hash de QR único si no existe
        $validated['qr_code'] = substr(md5(uniqid((string) rand(), true)), 0, 10);

        // Crear usuario para acceso como distribuidor si se proporcionó contraseña
        if (!empty($password)) {
            $user = User::updateOrCreate(
                ['email' => strtolower(trim($validated['contact_email']))],
                [
                    'first_name' => $validated['contact_name'],
                    'last_name' => '',
                    'phone' => $validated['contact_phone'] ?? null,
                    'password' => Hash::make($password),
                    'role' => 'distributor',
                    'status' => $validated['status'] === 'active' ? 'active' : 'inactive',
                    'must_change_password' => false,
                ]
            );
            $validated['user_id'] = $user->id;
        }

        $partner = CommercialPartner::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Aliado comercial registrado exitosamente.',
            'data' => $partner->loadCount('students'),
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

        // Si aún no tiene qr_code, asignarle uno
        if (empty($partner->qr_code)) {
            $validated['qr_code'] = substr(md5(uniqid((string) rand(), true)), 0, 10);
        }

        $partner->update($validated);

        // Si tiene cuenta de usuario vinculada, actualizar estado y correo
        if ($partner->user_id) {
            User::where('id', $partner->user_id)->update([
                'email' => strtolower(trim($validated['contact_email'])),
                'status' => $validated['status'] === 'active' ? 'active' : 'inactive',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aliado comercial actualizado exitosamente.',
            'data' => $partner->loadCount('students'),
        ]);
    }

    /**
     * Alternar estado Activo / Inactivo rápidamente
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

        $partner = CommercialPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado no existe.',
            ], 404);
        }

        $newStatus = ($partner->status === 'active') ? 'inactive' : 'active';
        $partner->status = $newStatus;
        $partner->save();

        if ($partner->user_id) {
            User::where('id', $partner->user_id)->update(['status' => $newStatus]);
        }

        $statusText = ($newStatus === 'active') ? 'activado' : 'inactivado';

        return response()->json([
            'success' => true,
            'message' => "Aliado {$statusText} exitosamente.",
            'data' => $partner->loadCount('students'),
        ]);
    }

    /**
     * Restablecer contraseña para la cuenta del distribuidor
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

        $partner = CommercialPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado no existe.',
            ], 404);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        // Buscar o crear usuario distribuidor
        $user = null;
        if ($partner->user_id) {
            $user = User::find($partner->user_id);
        }

        if (!$user) {
            $user = User::where('email', strtolower(trim($partner->contact_email)))->first();
        }

        if (!$user) {
            $user = User::create([
                'first_name' => $partner->contact_name,
                'last_name' => '',
                'email' => strtolower(trim($partner->contact_email)),
                'phone' => $partner->contact_phone,
                'password' => Hash::make($validated['password']),
                'role' => 'distributor',
                'status' => $partner->status === 'active' ? 'active' : 'inactive',
                'must_change_password' => false,
            ]);
            $partner->user_id = $user->id;
            $partner->save();
        } else {
            $user->password = Hash::make($validated['password']);
            $user->must_change_password = false;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Contraseña del distribuidor restablecida correctamente.',
        ]);
    }

    /**
     * Obtener enlace de registro directo y código QR
     */
    public function qrCode(Request $request, int $id): JsonResponse
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

        if (empty($partner->qr_code)) {
            $partner->qr_code = substr(md5(uniqid((string) rand(), true)), 0, 10);
            $partner->save();
        }

        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:4200'), '/');
        $registerUrl = "{$frontendUrl}/registro?dist={$partner->qr_code}";

        return response()->json([
            'success' => true,
            'data' => [
                'partner_id' => $partner->id,
                'company_name' => $partner->company_name,
                'qr_code' => $partner->qr_code,
                'register_url' => $registerUrl,
            ],
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
            ->where(function ($q) use ($partner) {
                $q->where('created_by', $partner->id);
                if ($partner->user_id) {
                    $q->orWhere('created_by', $partner->user_id);
                }
            })
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

    /**
     * Exportar estudiantes de un aliado específico a Excel / CSV (Legacy Format)
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

        $partner = CommercialPartner::find($id);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code' => 'PARTNER_NOT_FOUND',
                'message' => 'El aliado no existe.',
            ], 404);
        }

        $filename = 'estudiantes_aliado_' . $partner->id . '_' . date('Ymd_His') . '.csv';

        return $this->streamStudentsCsv([$partner->id, $partner->user_id], $filename, $partner->company_name);
    }

    /**
     * Exportar TODOS los estudiantes de distribuidores y convenios a Excel / CSV
     */
    public function exportAllStudents(Request $request): StreamedResponse|JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $partnerIds = CommercialPartner::pluck('id')->toArray();
        $userIds = CommercialPartner::whereNotNull('user_id')->pluck('user_id')->toArray();
        $allIds = array_unique(array_filter(array_merge($partnerIds, $userIds)));

        $filename = 'todos_estudiantes_distribuidores_' . date('Ymd_His') . '.csv';

        return $this->streamStudentsCsv($allIds, $filename);
    }

    /**
     * Generador de CSV en streaming con formato y columnas del legacy
     */
    private function streamStudentsCsv(array $creatorIds, string $filename, ?string $partnerNameFallback = null): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($creatorIds, $partnerNameFallback) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para garantizar que Excel interprete tildes y caracteres correctamente
            fputs($handle, "\xEF\xBB\xBF");

            // Encabezados legacy
            fputcsv($handle, [
                'ID Distribuidor',
                'Distribuidor / Convenio',
                'Nombres y Apellidos (Tutor)',
                'Nombre del Infante',
                'Fecha Nacimiento Infante',
                'Edad Calculada',
                'Correo Electrónico',
                'Celular / Contacto',
                'Cursos Asignados',
                'Fecha Registro',
                'Fecha Vencimiento',
                'Estado',
            ], ';');

            $students = User::with(['babyProfile', 'enrollments.course'])
                ->whereIn('created_by', $creatorIds)
                ->where('role', 'parent')
                ->orderBy('id', 'desc')
                ->cursor();

            $partnersMap = CommercialPartner::whereIn('id', $creatorIds)
                ->orWhereIn('user_id', $creatorIds)
                ->get()
                ->keyBy('id');

            foreach ($students as $s) {
                $partner = $partnersMap->get($s->created_by);
                $distName = $partner ? $partner->company_name : ($partnerNameFallback ?: 'N/A');

                $baby = $s->babyProfile;
                $babyName = $baby ? $baby->full_name : 'No registrado';
                $babyDob = ($baby && !empty($baby->birth_date) && $baby->birth_date !== '0000-00-00')
                    ? date('d/m/Y', strtotime($baby->birth_date))
                    : 'No registrada';
                $babyAge = $baby ? $this->calculateAgeText($baby->birth_date) : 'No registrada';

                // Cursos y fechas
                $courseNames = $s->enrollments->map(fn($e) => $e->course?->title ?? "Curso #{$e->course_id}")->filter()->join(', ');
                $maxExpiresAt = $s->enrollments->max('expires_at');
                $isExpired = (!empty($maxExpiresAt) && strtotime($maxExpiresAt) < time());
                $statusText = ($maxExpiresAt && !$isExpired) ? 'Activo' : 'Vencido';

                $registeredAt = $s->created_at ? $s->created_at->format('d/m/Y H:i') : 'N/A';
                $expirationDate = $maxExpiresAt ? date('d/m/Y', strtotime($maxExpiresAt)) : 'N/A';

                fputcsv($handle, [
                    $s->created_by ?: 'N/A',
                    $distName,
                    $s->full_name ?: $s->name,
                    $babyName,
                    $babyDob,
                    $babyAge,
                    $s->email,
                    $s->phone ?: 'No registrado',
                    $courseNames ?: 'Sin cursos',
                    $registeredAt,
                    $expirationDate,
                    $statusText,
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Helper para calcular la edad textual del infante (idéntico a la función legacy)
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
                $text = $diff->y . ' ' . ($diff->y === 1 ? 'año' : 'años');
                if ($diff->m > 0 && $diff->y < 5) {
                    $text .= ' y ' . $diff->m . ' ' . ($diff->m === 1 ? 'mes' : 'meses');
                }
                return $text;
            } elseif ($diff->m > 0) {
                return $diff->m . ' ' . ($diff->m === 1 ? 'mes' : 'meses');
            } else {
                return $diff->d . ' ' . ($diff->d === 1 ? 'día' : 'días');
            }
        } catch (\Throwable) {
            return (string) $birthDate;
        }
    }
}
