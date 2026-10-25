<?php

namespace Database\Seeders;

use App\Models\BabyProfile;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Cursos principales
        $courseBebe = Course::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Método Bebé Genial',
                'description' => 'Programa de estimulación temprana y desarrollo neuro-lingüístico para la primera infancia.',
                'cover_image' => 'metodo_bebe_cover.jpg',
                'hours' => 40,
                'price' => 180000,
                'is_visible' => true,
                'is_active' => true,
            ]
        );

        $courseLeo = Course::firstOrCreate(
            ['id' => 24],
            [
                'title' => 'Cuentos Leo con LEO',
                'description' => 'Biblioteca interactiva de cuentos ilustrados bilingües con actividades pedagógicas.',
                'cover_image' => 'leo_con_leo_cover.jpg',
                'hours' => 20,
                'price' => 120000,
                'is_visible' => true,
                'is_active' => true,
            ]
        );

        // 2. Usuario Acudiente Moderno (bcrypt)
        $parentUser = User::updateOrCreate(
            ['email' => 'acudiente@bebegenial.com'],
            [
                'first_name' => 'Carolina',
                'last_name' => 'Morales',
                'password' => Hash::make('Bebegenial2026*'),
                'phone' => '3158901234',
                'role' => 'parent',
                'status' => 'active',
                'must_change_password' => false,
            ]
        );

        BabyProfile::updateOrCreate(
            ['user_id' => $parentUser->id],
            [
                'full_name' => 'Martín Morales',
                'birth_date' => '2023-08-15',
                'avatar' => 'avatar_bebe_1.png',
                'school_grade' => 'Párvulos',
                'school_name' => 'Jardín Infantil Colsubsidio',
            ]
        );

        Enrollment::updateOrCreate(
            ['user_id' => $parentUser->id, 'course_id' => $courseLeo->id],
            [
                'enrolled_at' => now()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
                'access_type' => 'FREEMIUM',
                'book_limit' => 5,
                'origin_channel' => 'b2b_campaign',
            ]
        );

        // 3. Usuario Legacy (para probar verificación y re-hash de crypt('...', 'Bbp.'))
        $legacyHash = crypt('Legacy12345*', 'Bbp.');
        $legacyUser = User::updateOrCreate(
            ['email' => 'legacy@bebegenial.com'],
            [
                'first_name' => 'Andrea',
                'last_name' => 'Ríos',
                'password' => $legacyHash,
                'phone' => '3109876543',
                'role' => 'parent',
                'status' => 'active',
                'must_change_password' => false,
            ]
        );

        BabyProfile::updateOrCreate(
            ['user_id' => $legacyUser->id],
            [
                'full_name' => 'Lucas Ríos',
                'birth_date' => '2022-11-20',
                'avatar' => 'avatar_bebe_2.png',
            ]
        );

        // 4. Usuario Administrador
        User::updateOrCreate(
            ['email' => 'admin@bebegenial.com'],
            [
                'first_name' => 'Administrador',
                'last_name' => 'Nuvex',
                'password' => Hash::make('Admin12345*'),
                'phone' => '3001234567',
                'role' => 'admin',
                'status' => 'active',
                'must_change_password' => false,
            ]
        );
    }
}
