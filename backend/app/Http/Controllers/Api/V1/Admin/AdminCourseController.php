<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    /**
     * Lista todos los cursos del catálogo para asignación de matrículas
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

        $courses = Course::orderBy('id', 'asc')
            ->get(['id', 'title', 'description', 'hours', 'price', 'is_visible', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }
}
