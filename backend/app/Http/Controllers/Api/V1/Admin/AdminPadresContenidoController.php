<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PadresContenido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPadresContenidoController extends Controller
{
    /**
     * Listado paginado de contenidos para Smart Cards o Aprendamos Juntos
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
        $section = $request->input('section', 'smart_cards'); // 'smart_cards' | 'aprendamos_juntos'
        $category = $request->input('category');
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status'); // 'active', 'inactive'

        $query = PadresContenido::where('section', $section);

        if ($category) {
            $query->where('category', $category);
        }

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

        $paginated = $query->orderBy('order_index', 'asc')->orderBy('id', 'desc')->paginate($perPage);

        // Obtener lista de categorías únicas para la sección
        $categories = PadresContenido::where('section', $section)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'categories' => $categories,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Crear contenido
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
            'section' => ['required', Rule::in(['smart_cards', 'aprendamos_juntos'])],
            'category' => 'nullable|string|max:80',
            'title' => 'required|string|max:180',
            'resource_type' => ['required', Rule::in(['video', 'pdf', 'article'])],
            'url_video' => 'nullable|string|max:255',
            'file_path' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
            'allow_download' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $content = PadresContenido::create([
            'section' => $validated['section'],
            'category' => $validated['category'] ?? null,
            'title' => $validated['title'],
            'resource_type' => $validated['resource_type'],
            'url_video' => $validated['url_video'] ?? null,
            'file_path' => $validated['file_path'] ?? null,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'description' => $validated['description'] ?? null,
            'order_index' => $validated['order_index'] ?? 0,
            'allow_download' => $validated['allow_download'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Recurso '{$content->title}' creado exitosamente.",
            'data' => $content,
        ], 201);
    }

    /**
     * Ver detalle
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

        $content = PadresContenido::find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $content,
        ]);
    }

    /**
     * Actualizar contenido
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

        $content = PadresContenido::find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'category' => 'nullable|string|max:80',
            'title' => 'required|string|max:180',
            'resource_type' => ['required', Rule::in(['video', 'pdf', 'article'])],
            'url_video' => 'nullable|string|max:255',
            'file_path' => 'nullable|string|max:255',
            'thumbnail' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
            'allow_download' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $content->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Recurso '{$content->title}' actualizado correctamente.",
            'data' => $content->fresh(),
        ]);
    }

    /**
     * Alternar estado activo / inactivo
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

        $content = PadresContenido::find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado.',
            ], 404);
        }

        $content->is_active = !$content->is_active;
        $content->save();

        $estadoStr = $content->is_active ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "Recurso '{$content->title}' {$estadoStr}.",
            'data' => $content,
        ]);
    }

    /**
     * Eliminar contenido
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

        $content = PadresContenido::find($id);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado.',
            ], 404);
        }

        $content->delete();

        return response()->json([
            'success' => true,
            'message' => "Recurso '{$content->title}' eliminado correctamente.",
        ]);
    }
}
