<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    /**
     * Listado de plantillas de correo
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

        $templates = EmailTemplate::orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Crear plantilla
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
            'slug' => 'required|string|max:80|regex:/^[a-z0-9_\-]+$/|unique:email_templates,slug',
            'name' => 'required|string|max:120',
            'subject' => 'required|string|max:200',
            'body_html' => 'required|string',
            'available_variables' => 'nullable|array',
        ]);

        $template = EmailTemplate::create([
            'slug' => strtolower($validated['slug']),
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'available_variables' => $validated['available_variables'] ?? ['nombre', 'correo', 'curso', 'enlace'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Plantilla '{$template->name}' creada exitosamente.",
            'data' => $template,
        ], 201);
    }

    /**
     * Detalle de plantilla
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

        $template = EmailTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Actualizar plantilla
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

        $template = EmailTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'subject' => 'required|string|max:200',
            'body_html' => 'required|string',
            'available_variables' => 'nullable|array',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Plantilla '{$template->name}' actualizada correctamente.",
            'data' => $template->fresh(),
        ]);
    }
}
