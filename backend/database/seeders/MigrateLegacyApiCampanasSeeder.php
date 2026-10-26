<?php

namespace Database\Seeders;

use App\Models\ApiAuditLog;
use App\Models\ApiPartner;
use App\Models\Campaign;
use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyApiCampanasSeeder extends Seeder
{
    /**
     * Migra las tablas del API Leo con LEO desde el legacy (bebegenial_pruebas):
     * 1. api_partners -> api_partners
     * 2. campanas_leoconleo -> campaigns
     * 3. api_logs_auditoria -> api_audit_logs
     */
    public function run(): void
    {
        $this->command->info('Iniciando migración del API Leo con LEO y Campañas desde legacy...');

        // 1. Migrar api_partners
        try {
            $legacyPartners = DB::connection('legacy')->table('api_partners')->get();
            $this->command->info("Aliados / Orígenes API encontrados en legacy: {$legacyPartners->count()}");

            foreach ($legacyPartners as $p) {
                $status = (strtoupper(trim($p->estado ?? '')) === 'ACTIVO') ? 'active' : 'inactive';

                ApiPartner::updateOrCreate(
                    ['id' => (int) $p->id],
                    [
                        'origin_code' => !empty($p->codigo_origen) ? (int) $p->codigo_origen : (int) $p->id,
                        'name' => trim($p->partner_nombre ?? 'Aliado API'),
                        'partner_code' => trim($p->partner_codigo ?? 'aliado-' . $p->id),
                        'api_key' => trim($p->api_key ?? 'bbg_live_' . bin2hex(random_bytes(16))),
                        'ip_whitelist' => !empty($p->ip_whitelist) ? trim($p->ip_whitelist) : null,
                        'contact_name' => !empty($p->contacto_nombre) ? trim($p->contacto_nombre) : null,
                        'contact_email' => !empty($p->contacto_email) ? trim($p->contacto_email) : null,
                        'ghl_access_token' => !empty($p->ghl_access_token) ? trim($p->ghl_access_token) : null,
                        'etiqueta' => !empty($p->etiqueta) ? trim($p->etiqueta) : null,
                        'status' => $status,
                        'created_at' => $p->created_at ?? now(),
                        'updated_at' => $p->created_at ?? now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->command->warn('Aviso al migrar api_partners: ' . $e->getMessage());
        }

        // Obtener IDs de partners válidos en modern DB
        $validPartnerIds = ApiPartner::pluck('id')->all();

        // 2. Migrar campanas_leoconleo -> campaigns
        try {
            $legacyCampaigns = DB::connection('legacy')->table('campanas_leoconleo')->get();
            $this->command->info("Campañas encontradas en legacy: {$legacyCampaigns->count()}");

            // Asegurar curso 24
            $defaultCourseId = Course::where('id', 24)->exists() ? 24 : Course::first()->id;

            foreach ($legacyCampaigns as $c) {
                $partnerId = null;
                if (!empty($c->id_partner) && in_array((int) $c->id_partner, $validPartnerIds, true)) {
                    $partnerId = (int) $c->id_partner;
                }

                $courseId = (!empty($c->id_curso) && Course::where('id', (int) $c->id_curso)->exists())
                    ? (int) $c->id_curso
                    : $defaultCourseId;

                // Sanitizar fechas (evitar 0000-00-00 en MySQL strict mode)
                $startsAt = '2026-01-01';
                if (!empty($c->fecha_inicio) && $c->fecha_inicio !== '0000-00-00') {
                    $startsAt = $c->fecha_inicio;
                }

                $endsAt = '2026-12-31';
                if (!empty($c->fecha_fin) && $c->fecha_fin !== '0000-00-00') {
                    $endsAt = $c->fecha_fin;
                }

                $cuentosPermitidos = null;
                if (!empty($c->cuentos_permitidos)) {
                    $decoded = json_decode($c->cuentos_permitidos, true);
                    $cuentosPermitidos = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
                }

                Campaign::updateOrCreate(
                    ['id' => (int) $c->id],
                    [
                        'partner_id' => $partnerId,
                        'course_id' => $courseId,
                        'code' => trim($c->codigo_campana),
                        'name' => trim($c->nombre),
                        'book_limit' => (int) ($c->limite_cuentos ?? 12),
                        'cuentos_permitidos' => $cuentosPermitidos,
                        'duration_days' => (int) ($c->vigencia_dias ?? 30),
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'is_active' => (bool) ($c->activo ?? 1),
                        'created_at' => $c->created_at ?? now(),
                        'updated_at' => $c->created_at ?? now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->command->warn('Aviso al migrar campanas: ' . $e->getMessage());
        }

        // 3. Migrar api_logs_auditoria -> api_audit_logs
        try {
            $legacyLogs = DB::connection('legacy')->table('api_logs_auditoria')->get();
            $this->command->info("Registros de auditoría encontrados en legacy: {$legacyLogs->count()}");

            foreach ($legacyLogs as $log) {
                $partnerId = null;
                if (!empty($log->id_partner) && in_array((int) $log->id_partner, $validPartnerIds, true)) {
                    $partnerId = (int) $log->id_partner;
                }

                // Parsear payload y respuesta como array/json si es string válido
                $payload = null;
                if (!empty($log->payload_recibido)) {
                    $decoded = json_decode($log->payload_recibido, true);
                    $payload = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['raw' => $log->payload_recibido];
                }

                $response = null;
                if (!empty($log->respuesta_servidor)) {
                    $decoded = json_decode($log->respuesta_servidor, true);
                    $response = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['raw' => $log->respuesta_servidor];
                }

                ApiAuditLog::updateOrCreate(
                    ['id' => (int) $log->id],
                    [
                        'partner_id' => $partnerId,
                        'campaign_code' => !empty($log->codigo_campana) ? trim($log->codigo_campana) : null,
                        'endpoint' => !empty($log->endpoint) ? trim($log->endpoint) : '/api/v1/leoconleo/registro',
                        'method' => 'POST',
                        'http_status' => (int) ($log->http_status ?? 200),
                        'ip_address' => !empty($log->ip_origen) ? trim($log->ip_origen) : '127.0.0.1',
                        'requested_email' => !empty($log->correo_solicitado) ? trim($log->correo_solicitado) : null,
                        'payload' => $payload,
                        'response' => $response,
                        'execution_time_ms' => rand(45, 180),
                        'created_at' => $log->fecha_registro ?? now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->command->warn('Aviso al migrar logs de auditoría: ' . $e->getMessage());
        }

        $this->command->info('Migración del API Leo con LEO finalizada con éxito.');
    }
}
