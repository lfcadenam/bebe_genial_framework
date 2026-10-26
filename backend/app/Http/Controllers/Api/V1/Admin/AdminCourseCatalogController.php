<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCourseCatalogController extends Controller
{
    /**
     * Listado paginado de cursos del catálogo
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
        $status = $request->input('status'); // 'active', 'inactive'

        $query = Course::withCount('enrollments');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('id', 'asc')->paginate($perPage);

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
     * Crear nuevo curso
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
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'hours' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'is_visible' => 'boolean',
            'is_active' => 'boolean',
            'cover_image' => 'nullable|string|max:150',
            'background_image' => 'nullable|string|max:150',
        ]);

        $course = Course::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'hours' => $validated['hours'] ?? 0,
            'price' => $validated['price'] ?? 0.00,
            'is_visible' => $validated['is_visible'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
            'cover_image' => $validated['cover_image'] ?? null,
            'background_image' => $validated['background_image'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Curso '{$course->title}' creado exitosamente.",
            'data' => $course,
        ], 201);
    }

    /**
     * Ver detalle de curso
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

        $course = Course::withCount('enrollments')->find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $course,
        ]);
    }

    /**
     * Actualizar curso
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

        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'hours' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'is_visible' => 'boolean',
            'is_active' => 'boolean',
            'cover_image' => 'nullable|string|max:150',
            'background_image' => 'nullable|string|max:150',
        ]);

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Curso '{$course->title}' actualizado correctamente.",
            'data' => $course->fresh(),
        ]);
    }

    /**
     * Alternar estado activo / inactivo
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado.',
            ], 404);
        }

        $course->is_active = !$course->is_active;
        $course->save();

        $estadoStr = $course->is_active ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "Curso '{$course->title}' {$estadoStr}.",
            'data' => $course,
        ]);
    }

    /**
     * Alternar visibilidad en catálogo público
     */
    public function toggleVisibility(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado.',
            ], 404);
        }

        $course->is_visible = !$course->is_visible;
        $course->save();

        $visStr = $course->is_visible ? 'visible' : 'oculto';

        return response()->json([
            'success' => true,
            'message' => "Curso '{$course->title}' ahora está {$visStr}.",
            'data' => $course,
        ]);
    }

    /**
     * Eliminar curso
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

        $course = Course::withCount('enrollments')->find($id);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado.',
            ], 404);
        }

        if ($course->enrollments_count > 0) {
            return response()->json([
                'success' => false,
                'code' => 'COURSE_HAS_ENROLLMENTS',
                'message' => "No es posible eliminar el curso '{$course->title}' porque tiene {$course->enrollments_count} matrícula(s) asociadas. Desactívalo en su lugar.",
            ], 422);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => "Curso '{$course->title}' eliminado correctamente.",
        ]);
    }

    /**
     * Subir imagen para curso pedagógico
     */
    public function uploadImage(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,svg,gif|max:5120',
        ]);

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = 'CuRSo' . Str::random(20) . '.' . $extension;

        // Directorio destino backend
        $backendDir = public_path('images/cursos');
        if (!file_exists($backendDir)) {
            mkdir($backendDir, 0755, true);
        }
        $file->move($backendDir, $filename);

        // Replicar en frontend/public para desarrollo y build
        $frontendDir = base_path('../frontend/public/images/cursos');
        if (file_exists($frontendDir)) {
            copy($backendDir . '/' . $filename, $frontendDir . '/' . $filename);
        }

        return response()->json([
            'success' => true,
            'message' => 'Imagen cargada correctamente.',
            'filename' => $filename,
            'url' => '/images/cursos/' . $filename,
        ]);
    }

    /**
     * Lista de imágenes disponibles del catálogo legacy y subidas
     */
    public function availableImages(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED_ACCESS',
                'message' => 'No tienes permisos de administrador.',
            ], 403);
        }

        $dir = public_path('images/cursos');
        $images = [];

        if (file_exists($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || is_dir($dir . '/' . $file)) {
                    continue;
                }
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'])) {
                    $images[] = [
                        'filename' => $file,
                        'url' => '/images/cursos/' . $file,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $images,
        ]);
    }
}
