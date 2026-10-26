<?php

namespace Database\Seeders;

use App\Models\BabyProfile;
use App\Models\CommercialPartner;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyCommercialPartnersSeeder extends Seeder
{
    /**
     * Migra todos los convenios y distribuidores desde bebegenial_pruebas a bebegenial_desacople,
     * incluyendo sus parámetros de cursos y tiempos autorizados, y los alumnos registrados por ellos.
     */
    public function run(): void
    {
        $this->command->info('Iniciando migración de Convenios y Distribuidores desde el legacy...');

        // 1. Obtener distribuidores y convenios (idperfil = 7)
        $legacyDistributors = DB::connection('legacy')
            ->table('usuarios')
            ->where('idperfil', 7)
            ->get();

        $this->command->info('Distribuidores/Convenios encontrados en legacy: ' . $legacyDistributors->count());

        foreach ($legacyDistributors as $dist) {
            $distId = (int) $dist->id;
            $email = strtolower(trim($dist->correo));
            $companyName = !empty($dist->nombre_distribuidor)
                ? trim($dist->nombre_distribuidor)
                : trim("{$dist->nombre} {$dist->apellidos}");
            $contactName = trim("{$dist->nombre} {$dist->apellidos}");
            $isAgreement = ((int) ($dist->es_convenio ?? 0) === 1);
            $status = ((int) $dist->estado === 1) ? 'active' : 'inactive';

            // Obtener cursos asignados
            $courseRows = DB::connection('legacy')
                ->table('distribuidores_cursos')
                ->where('id_distribuidor', $distId)
                ->pluck('id_curso')
                ->map(fn($id) => (int) $id)
                ->toArray();

            // Obtener meses de vigencia autorizados
            $monthsRow = DB::connection('legacy')
                ->table('distribuidores_tiempos')
                ->where('id_distribuidor', $distId)
                ->value('meses');
            $allowedMonths = $monthsRow ? (int) $monthsRow : 12;

            // Código QR / Enlace de registro directo
            $qrCode = !empty($dist->codigo_qr) ? trim($dist->codigo_qr) : substr(md5(uniqid((string) rand(), true)), 0, 10);

            // 1. Crear o actualizar cuenta de usuario (role: distributor)
            $distUser = User::updateOrCreate(
                ['id' => $distId],
                [
                    'first_name' => trim($dist->nombre) ?: 'Distribuidor',
                    'last_name' => trim($dist->apellidos) ?: 'Comercial',
                    'email' => $email,
                    'phone' => !empty($dist->contacto) ? trim($dist->contacto) : null,
                    'password' => $dist->pass ?? bcrypt('Bbp.2026*'),
                    'role' => 'distributor',
                    'status' => $status,
                    'must_change_password' => (bool) ($dist->debe_cambiar_pass ?? false),
                ]
            );

            // 2. Crear o actualizar entidad commercial_partner
            $partner = CommercialPartner::updateOrCreate(
                ['id' => $distId],
                [
                    'company_name' => $companyName,
                    'contact_name' => $contactName,
                    'contact_email' => $email,
                    'contact_phone' => !empty($dist->contacto) ? trim($dist->contacto) : null,
                    'is_agreement' => $isAgreement,
                    'status' => $status,
                    'qr_code' => $qrCode,
                    'user_id' => $distUser->id,
                    'allowed_courses' => !empty($courseRows) ? $courseRows : null,
                    'allowed_months' => $allowedMonths,
                    'notes' => $isAgreement
                        ? 'Convenio Institucional migrado desde base legacy'
                        : 'Distribuidor comercial independiente migrado desde base legacy',
                ]
            );

            $this->command->info("-> Migrado Aliado #{$distId}: {$companyName} (" . ($isAgreement ? 'Convenio' : 'Distribuidor') . ")");

            // 3. Migrar alumnos matriculados por este distribuidor (idcreador = distId)
            $legacyStudents = DB::connection('legacy')
                ->table('usuarios')
                ->where('idcreador', $distId)
                ->where('idperfil', 4)
                ->get();

            $this->command->info("   Alumnos encontrados para #{$distId}: " . $legacyStudents->count());

            foreach ($legacyStudents as $s) {
                $studentId = (int) $s->id;
                $studentEmail = strtolower(trim($s->correo));
                $studentStatus = ((int) $s->estado === 1) ? 'active' : 'inactive';

                $studentUser = User::updateOrCreate(
                    ['id' => $studentId],
                    [
                        'first_name' => trim($s->nombre) ?: 'Estudiante',
                        'last_name' => trim($s->apellidos) ?: '',
                        'email' => $studentEmail,
                        'phone' => !empty($s->contacto) ? trim($s->contacto) : null,
                        'password' => $s->pass ?? bcrypt('123'),
                        'role' => 'parent',
                        'status' => $studentStatus,
                        'must_change_password' => (bool) ($s->debe_cambiar_pass ?? false),
                        'created_by' => $distId,
                    ]
                );

                // Migrar perfil del bebé
                $baby = DB::connection('legacy')
                    ->table('usuario_baby')
                    ->where('id', $studentId)
                    ->first();

                if ($baby) {
                    BabyProfile::updateOrCreate(
                        ['user_id' => $studentId],
                        [
                            'full_name' => trim($baby->nombre) ?: 'Infante',
                            'birth_date' => (!empty($baby->edad) && $baby->edad !== '0000-00-00') ? $baby->edad : '2020-01-01',
                            'avatar' => $baby->avatar ?? 'avatar_bebe_1.png',
                            'school_grade' => $baby->grado ?? null,
                            'school_name' => $baby->colegio ?? null,
                        ]
                    );
                }

                // Migrar matrículas del alumno
                $enrollments = DB::connection('legacy')
                    ->table('matricula')
                    ->where('idusuario', $studentId)
                    ->get();

                foreach ($enrollments as $mat) {
                    $courseId = (int) $mat->idcurso;

                    // Asegurar que el curso exista en courses
                    $legacyCourse = DB::connection('legacy')
                        ->table('cursos')
                        ->where('id', $courseId)
                        ->first();

                    if ($legacyCourse) {
                        Course::firstOrCreate(
                            ['id' => $courseId],
                            [
                                'title' => trim($legacyCourse->nombre),
                                'description' => $legacyCourse->descripcion ?? null,
                                'is_visible' => true,
                                'is_active' => true,
                            ]
                        );
                    }

                    Enrollment::updateOrCreate(
                        [
                            'user_id' => $studentId,
                            'course_id' => $courseId,
                        ],
                        [
                            'campaign_id' => $mat->id_campana ?? null,
                            'enrolled_at' => $mat->{'fecha-matricula'} ?? ($mat->fecha ?? now()->toDateString()),
                            'expires_at' => $mat->fecha ?? now()->addMonths(6)->toDateString(),
                            'access_type' => $mat->tipo_acceso ?? 'FULL',
                            'book_limit' => $mat->limite_cuentos_asignado ?? 0,
                            'origin_channel' => $mat->canal_origen ?? 'distribuidor',
                        ]
                    );
                }
            }
        }

        $this->command->info('Migración de Convenios y Distribuidores finalizada exitosamente.');
    }
}
