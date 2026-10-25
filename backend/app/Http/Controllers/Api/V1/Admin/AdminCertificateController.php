<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCertificateController extends Controller
{
    /**
     * Listado paginado de certificados y diplomas
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
        $status = $request->input('status'); // 'valid', 'revoked'

        $query = Certificate::with(['user', 'course']);

        if ($courseId) {
            $query->where('course_id', (int) $courseId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('first_name', 'like', "%{$search}%")
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

    /**
     * Emitir nuevo certificado
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
            'grade' => 'nullable|string|max:20',
            'certificate_code' => 'nullable|string|max:60|unique:certificates,certificate_code',
        ]);

        $code = $validated['certificate_code'] ?? ('CERT-' . strtoupper(Str::random(10)));

        $certificate = Certificate::create([
            'user_id' => $validated['user_id'],
            'course_id' => $validated['course_id'],
            'certificate_code' => $code,
            'grade' => $validated['grade'] ?? 'Aprobado',
            'issued_at' => now(),
            'status' => 'valid',
        ]);

        $certificate->load(['user', 'course']);

        return response()->json([
            'success' => true,
            'message' => "Certificado '{$certificate->certificate_code}' emitido exitosamente.",
            'data' => $certificate,
        ], 201);
    }

    /**
     * Revocar certificado
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

        $certificate = Certificate::find($id);

        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificado no encontrado.',
            ], 404);
        }

        $certificate->status = 'revoked';
        $certificate->save();

        return response()->json([
            'success' => true,
            'message' => "Certificado '{$certificate->certificate_code}' revocado.",
            'data' => $certificate,
        ]);
    }
}
