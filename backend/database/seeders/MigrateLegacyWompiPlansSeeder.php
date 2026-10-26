<?php

namespace Database\Seeders;

use App\Models\WompiPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateLegacyWompiPlansSeeder extends Seeder
{
    /**
     * Migra los planes parametrizados de WOMPI desde la base de datos legacy (bebegenial_pruebas.wompi_planes)
     * hacia la base de datos desacoplada (bebegenial_desacople.wompi_plans).
     */
    public function run(): void
    {
        $this->command?->info('Iniciando migración de planes WOMPI desde legacy...');

        // 1. Obtener registros de wompi_planes legacy
        try {
            $legacyPlanes = DB::connection('legacy')->table('wompi_planes')->orderBy('id', 'asc')->get();
        } catch (\Throwable $e) {
            $legacyPlanes = DB::select('SELECT * FROM bebegenial_pruebas.wompi_planes ORDER BY id ASC');
        }

        if (empty($legacyPlanes) || count($legacyPlanes) === 0) {
            $this->command?->warn('No se encontraron planes en la tabla wompi_planes del legacy.');
            return;
        }

        $migratedCount = 0;

        foreach ($legacyPlanes as $p) {
            // Limpieza de formato para beneficios (eliminar HTML residual y dejar líneas limpias)
            $benefits = $p->beneficios ?? '';
            if (str_contains($benefits, '<') && str_contains($benefits, '>')) {
                // Reemplazar cierres de lista o párrafos por saltos de línea
                $benefits = str_replace(['</li>', '</p>', '<br>', '<br/>', '<br />'], "\n", $benefits);
                $benefits = strip_tags($benefits);
                $benefits = preg_replace("/\n{2,}/", "\n", $benefits);
                $benefits = trim($benefits);
            }

            // Mapeo de cursos relacionados a arreglo numérico JSON
            $relatedCourses = [];
            if (!empty($p->idcurso_relacionados)) {
                $rawList = explode(',', (string) $p->idcurso_relacionados);
                foreach ($rawList as $courseId) {
                    $cId = (int) trim($courseId);
                    if ($cId > 0) {
                        $relatedCourses[] = $cId;
                    }
                }
            }

            // Normalización de tipo
            $type = (strtolower(trim($p->tipo ?? '')) === 'recurrente') ? 'recurring' : 'single';

            // Actualizar o crear respetando el ID original legacy
            WompiPlan::updateOrCreate(
                ['id' => $p->id],
                [
                    'name' => trim($p->nombre),
                    'type' => $type,
                    'price' => (float) $p->precio,
                    'currency' => !empty($p->moneda) ? trim($p->moneda) : 'COP',
                    'duration_months' => (int) ($p->duracion_meses ?? 1),
                    'trial_days' => (int) ($p->dias_prueba ?? 0),
                    'is_active' => (bool) ($p->estado == 1),
                    'benefits' => $benefits,
                    'related_courses' => $relatedCourses,
                ]
            );

            $migratedCount++;
        }

        $this->command?->info("Se migraron exitosamente {$migratedCount} planes de WOMPI.");
    }
}
