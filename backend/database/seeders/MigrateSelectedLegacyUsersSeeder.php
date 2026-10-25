<?php

namespace Database\Seeders;

use App\Models\BabyProfile;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StoryRead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class MigrateSelectedLegacyUsersSeeder extends Seeder
{
    /**
     * Limpia los usuarios actuales y migra exactamente los dos usuarios legacy solicitados:
     * - spa@bebe.com
     * - usuario1@bebe.com
     * junto con sus perfiles de bebé y sus matrículas activas.
     */
    public function run(): void
    {
        // 1. Limpieza total de usuarios previos en bebegenial_desacople
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PersonalAccessToken::truncate();
        StoryRead::truncate();
        Enrollment::truncate();
        BabyProfile::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $targetEmails = ['spa@bebe.com', 'usuario1@bebe.com'];

        // 2. Consulta en la base de datos legacy
        $legacyUsers = DB::connection('legacy')
            ->table('usuarios')
            ->whereIn('correo', ['spa@bebe.com', 'USUARIO1@BEBE.COM', 'usuario1@bebe.com'])
            ->get();

        foreach ($legacyUsers as $legacyUser) {
            $normalizedEmail = strtolower(trim($legacyUser->correo));

            // Determinar rol
            $role = match ((int) $legacyUser->idperfil) {
                1 => 'admin',
                4 => 'parent',
                default => 'parent',
            };

            // Determinar estado
            $status = ((int) $legacyUser->estado === 1) ? 'active' : 'inactive';

            // Crear usuario en bebegenial_desacople con su hash legacy exacto
            $newUser = User::create([
                'id' => $legacyUser->id,
                'first_name' => trim($legacyUser->nombre),
                'last_name' => trim($legacyUser->apellidos),
                'email' => $normalizedEmail,
                'phone' => $legacyUser->contacto ?? null,
                'password' => $legacyUser->pass, // Mantiene el hash legacy BbM65Dlq5qvvU (clave: 123)
                'role' => $role,
                'status' => $status,
                'must_change_password' => (bool) ($legacyUser->debe_cambiar_pass ?? false),
            ]);

            // 3. Migrar perfil del bebé desde usuario_baby
            $legacyBaby = DB::connection('legacy')
                ->table('usuario_baby')
                ->where('id', $legacyUser->id)
                ->first();

            if ($legacyBaby) {
                BabyProfile::create([
                    'user_id' => $newUser->id,
                    'full_name' => trim($legacyBaby->nombre),
                    'birth_date' => (!empty($legacyBaby->edad) && $legacyBaby->edad !== '0000-00-00') ? $legacyBaby->edad : '2023-01-01',
                    'avatar' => $legacyBaby->avatar ?? 'avatar_bebe_1.png',
                    'school_grade' => $legacyBaby->grado ?? null,
                    'school_name' => $legacyBaby->colegio ?? null,
                ]);
            }

            // 4. Migrar cursos asociados y matrículas desde matricula legacy
            $legacyMatriculas = DB::connection('legacy')
                ->table('matricula')
                ->where('idusuario', $legacyUser->id)
                ->get();

            foreach ($legacyMatriculas as $mat) {
                // Verificar que el curso exista en la tabla courses
                $legacyCourse = DB::connection('legacy')
                    ->table('cursos')
                    ->where('id', $mat->idcurso)
                    ->first();

                if ($legacyCourse) {
                    Course::firstOrCreate(
                        ['id' => $legacyCourse->id],
                        [
                            'title' => trim($legacyCourse->nombre),
                            'description' => $legacyCourse->descripcion ?? '',
                            'cover_image' => $legacyCourse->imagen ?? 'default_course.jpg',
                            'hours' => $legacyCourse->horas ?? 10,
                            'price' => $legacyCourse->precio ?? 0,
                            'is_visible' => (bool) ($legacyCourse->visible ?? true),
                            'is_active' => (bool) ($legacyCourse->estado ?? true),
                        ]
                    );
                }

                // Normalizar fechas de matrícula
                $enrolledAt = (!empty($mat->{'fecha-matricula'}) && $mat->{'fecha-matricula'} !== '0000-00-00')
                    ? $mat->{'fecha-matricula'}
                    : now()->toDateString();

                $expiresAt = (!empty($mat->fecha) && $mat->fecha !== '0000-00-00')
                    ? $mat->fecha
                    : now()->addYear()->toDateString();

                $accessType = strtoupper($mat->tipo_acceso ?? 'FREEMIUM');
                if (!in_array($accessType, ['FULL', 'FREEMIUM'])) {
                    $accessType = 'FREEMIUM';
                }

                Enrollment::create([
                    'user_id' => $newUser->id,
                    'course_id' => $mat->idcurso,
                    'campaign_id' => null,
                    'enrolled_at' => $enrolledAt,
                    'expires_at' => $expiresAt,
                    'access_type' => $accessType,
                    'book_limit' => $mat->limite_cuentos_asignado ?? 12,
                    'origin_channel' => $mat->canal_origen ?? 'API',
                ]);
            }
        }
    }
}
