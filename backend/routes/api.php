<?php

use App\Http\Controllers\Api\V1\Admin\AdminApiAuditLogController;
use App\Http\Controllers\Api\V1\Admin\AdminApiPartnerController;
use App\Http\Controllers\Api\V1\Admin\AdminCampaignController;
use App\Http\Controllers\Api\V1\Admin\AdminCertificateController;
use App\Http\Controllers\Api\V1\Admin\AdminCommercialPartnerController;
use App\Http\Controllers\Api\V1\Admin\AdminCourseCatalogController;
use App\Http\Controllers\Api\V1\Admin\AdminCourseController;
use App\Http\Controllers\Api\V1\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Api\V1\Admin\AdminOmniSearchController;
use App\Http\Controllers\Api\V1\Admin\AdminPadresContenidoController;
use App\Http\Controllers\Api\V1\Admin\AdminTrialController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminWompiController;
use App\Http\Controllers\Api\V1\Admin\DashboardMetricsController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Bebé Genial (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Rutas públicas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('login', LoginController::class)->name('api.v1.auth.login');
    });

    // Rutas protegidas por Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', MeController::class)->name('api.v1.auth.me');
            Route::post('logout', LogoutController::class)->name('api.v1.auth.logout');
            Route::post('change-password', ChangePasswordController::class)->name('api.v1.auth.change-password');
        });

        // Panel de Administración (requiere rol admin validado en controller/middleware)
        Route::prefix('admin')->group(function () {
            Route::get('dashboard/metrics', DashboardMetricsController::class)->name('api.v1.admin.dashboard.metrics');

            // Gestión de Cursos
            Route::get('courses', [AdminCourseController::class, 'index'])->name('api.v1.admin.courses.index');

            // Gestión de Usuarios y Alumnos
            Route::get('users', [AdminUserController::class, 'index'])->name('api.v1.admin.users.index');
            Route::get('users/{id}', [AdminUserController::class, 'show'])->name('api.v1.admin.users.show');
            Route::put('users/{id}', [AdminUserController::class, 'update'])->name('api.v1.admin.users.update');
            Route::post('users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('api.v1.admin.users.reset-password');
            Route::post('users/{id}/enrollments', [AdminUserController::class, 'storeEnrollment'])->name('api.v1.admin.users.enrollments.store');
            Route::delete('enrollments/{id}', [AdminUserController::class, 'destroyEnrollment'])->name('api.v1.admin.enrollments.destroy');

            // Gestión Comercial: Convenios y Distribuidores
            Route::prefix('commercial-partners')->group(function () {
                Route::get('/', [AdminCommercialPartnerController::class, 'index'])->name('api.v1.admin.commercial-partners.index');
                Route::post('/', [AdminCommercialPartnerController::class, 'store'])->name('api.v1.admin.commercial-partners.store');
                Route::get('export-all-students', [AdminCommercialPartnerController::class, 'exportAllStudents'])->name('api.v1.admin.commercial-partners.export-all');
                Route::get('{id}', [AdminCommercialPartnerController::class, 'show'])->name('api.v1.admin.commercial-partners.show');
                Route::put('{id}', [AdminCommercialPartnerController::class, 'update'])->name('api.v1.admin.commercial-partners.update');
                Route::delete('{id}', [AdminCommercialPartnerController::class, 'destroy'])->name('api.v1.admin.commercial-partners.destroy');
                Route::post('{id}/toggle-status', [AdminCommercialPartnerController::class, 'toggleStatus'])->name('api.v1.admin.commercial-partners.toggle');
                Route::post('{id}/reset-password', [AdminCommercialPartnerController::class, 'resetPassword'])->name('api.v1.admin.commercial-partners.reset-password');
                Route::get('{id}/qr-code', [AdminCommercialPartnerController::class, 'qrCode'])->name('api.v1.admin.commercial-partners.qr-code');
                Route::get('{id}/students', [AdminCommercialPartnerController::class, 'students'])->name('api.v1.admin.commercial-partners.students');
                Route::get('{id}/export-students', [AdminCommercialPartnerController::class, 'exportStudents'])->name('api.v1.admin.commercial-partners.export-students');
            });

            // Módulo Comercial WOMPI
            Route::prefix('wompi')->group(function () {
                Route::get('metrics', [AdminWompiController::class, 'getMetrics'])->name('api.v1.admin.wompi.metrics');
                Route::get('plans', [AdminWompiController::class, 'getPlans'])->name('api.v1.admin.wompi.plans.index');
                Route::post('plans', [AdminWompiController::class, 'storePlan'])->name('api.v1.admin.wompi.plans.store');
                Route::put('plans/{id}', [AdminWompiController::class, 'updatePlan'])->name('api.v1.admin.wompi.plans.update');
                Route::post('plans/{id}/toggle-status', [AdminWompiController::class, 'togglePlanStatus'])->name('api.v1.admin.wompi.plans.toggle');
                Route::get('subscriptions', [AdminWompiController::class, 'getSubscriptions'])->name('api.v1.admin.wompi.subscriptions.index');
            });

            // Módulo API Leo con LEO B2B (Aliados, Campañas, Auditoría)
            Route::prefix('api-partners')->group(function () {
                Route::get('/', [AdminApiPartnerController::class, 'index'])->name('api.v1.admin.api-partners.index');
                Route::post('/', [AdminApiPartnerController::class, 'store'])->name('api.v1.admin.api-partners.store');
                Route::get('{id}', [AdminApiPartnerController::class, 'show'])->name('api.v1.admin.api-partners.show');
                Route::put('{id}', [AdminApiPartnerController::class, 'update'])->name('api.v1.admin.api-partners.update');
                Route::delete('{id}', [AdminApiPartnerController::class, 'destroy'])->name('api.v1.admin.api-partners.destroy');
                Route::post('{id}/toggle-status', [AdminApiPartnerController::class, 'toggleStatus'])->name('api.v1.admin.api-partners.toggle');
                Route::post('{id}/regenerate-key', [AdminApiPartnerController::class, 'regenerateKey'])->name('api.v1.admin.api-partners.regenerate-key');
            });

            Route::prefix('campaigns')->group(function () {
                Route::get('/', [AdminCampaignController::class, 'index'])->name('api.v1.admin.campaigns.index');
                Route::get('cuentos-catalog', [AdminCampaignController::class, 'cuentosCatalog'])->name('api.v1.admin.campaigns.cuentos-catalog');
                Route::post('/', [AdminCampaignController::class, 'store'])->name('api.v1.admin.campaigns.store');
                Route::get('{id}', [AdminCampaignController::class, 'show'])->name('api.v1.admin.campaigns.show');
                Route::put('{id}', [AdminCampaignController::class, 'update'])->name('api.v1.admin.campaigns.update');
                Route::delete('{id}', [AdminCampaignController::class, 'destroy'])->name('api.v1.admin.campaigns.destroy');
                Route::post('{id}/toggle-status', [AdminCampaignController::class, 'toggleStatus'])->name('api.v1.admin.campaigns.toggle');
                Route::get('{id}/students', [AdminCampaignController::class, 'students'])->name('api.v1.admin.campaigns.students');
                Route::get('{id}/export-students', [AdminCampaignController::class, 'exportStudents'])->name('api.v1.admin.campaigns.export-students');
            });

            Route::prefix('api-audit-logs')->group(function () {
                Route::get('metrics', [AdminApiAuditLogController::class, 'getMetrics'])->name('api.v1.admin.api-audit-logs.metrics');
                Route::get('/', [AdminApiAuditLogController::class, 'index'])->name('api.v1.admin.api-audit-logs.index');
            });

            // ==================== SPRINT 5: CATÁLOGO, CONTENIDOS & DOCUMENTOS ====================
            // 1. Gestión Integral de Cursos del Catálogo
            Route::prefix('course-catalog')->group(function () {
                Route::get('/', [AdminCourseCatalogController::class, 'index'])->name('api.v1.admin.course-catalog.index');
                Route::post('/', [AdminCourseCatalogController::class, 'store'])->name('api.v1.admin.course-catalog.store');
                Route::post('upload-image', [AdminCourseCatalogController::class, 'uploadImage'])->name('api.v1.admin.course-catalog.upload-image');
                Route::get('available-images', [AdminCourseCatalogController::class, 'availableImages'])->name('api.v1.admin.course-catalog.available-images');
                Route::get('{id}', [AdminCourseCatalogController::class, 'show'])->name('api.v1.admin.course-catalog.show');
                Route::put('{id}', [AdminCourseCatalogController::class, 'update'])->name('api.v1.admin.course-catalog.update');
                Route::delete('{id}', [AdminCourseCatalogController::class, 'destroy'])->name('api.v1.admin.course-catalog.destroy');
                Route::post('{id}/toggle-active', [AdminCourseCatalogController::class, 'toggleActive'])->name('api.v1.admin.course-catalog.toggle-active');
                Route::post('{id}/toggle-visibility', [AdminCourseCatalogController::class, 'toggleVisibility'])->name('api.v1.admin.course-catalog.toggle-visibility');
            });

            // 2. Recursos y Contenidos (Smart Cards & Aprendamos Juntos)
            Route::prefix('padres-contenidos')->group(function () {
                Route::get('/', [AdminPadresContenidoController::class, 'index'])->name('api.v1.admin.padres-contenidos.index');
                Route::post('/', [AdminPadresContenidoController::class, 'store'])->name('api.v1.admin.padres-contenidos.store');
                Route::get('{id}', [AdminPadresContenidoController::class, 'show'])->name('api.v1.admin.padres-contenidos.show');
                Route::put('{id}', [AdminPadresContenidoController::class, 'update'])->name('api.v1.admin.padres-contenidos.update');
                Route::delete('{id}', [AdminPadresContenidoController::class, 'destroy'])->name('api.v1.admin.padres-contenidos.destroy');
                Route::post('{id}/toggle-status', [AdminPadresContenidoController::class, 'toggleStatus'])->name('api.v1.admin.padres-contenidos.toggle-status');
            });

            // 3. Códigos Demo / Trials
            Route::prefix('trials')->group(function () {
                Route::get('/', [AdminTrialController::class, 'index'])->name('api.v1.admin.trials.index');
                Route::post('/', [AdminTrialController::class, 'store'])->name('api.v1.admin.trials.store');
                Route::post('{id}/revoke', [AdminTrialController::class, 'revoke'])->name('api.v1.admin.trials.revoke');
            });

            // 4. Consultas Avanzadas & Buscador Omnicanal
            Route::get('omni-search', [AdminOmniSearchController::class, 'search'])->name('api.v1.admin.omni-search');

            // 5. Plantillas de Correo
            Route::prefix('email-templates')->group(function () {
                Route::get('/', [AdminEmailTemplateController::class, 'index'])->name('api.v1.admin.email-templates.index');
                Route::post('/', [AdminEmailTemplateController::class, 'store'])->name('api.v1.admin.email-templates.store');
                Route::get('{id}', [AdminEmailTemplateController::class, 'show'])->name('api.v1.admin.email-templates.show');
                Route::put('{id}', [AdminEmailTemplateController::class, 'update'])->name('api.v1.admin.email-templates.update');
            });

            // 6. Certificados y Diplomas
            Route::prefix('certificates')->group(function () {
                Route::get('/', [AdminCertificateController::class, 'index'])->name('api.v1.admin.certificates.index');
                Route::post('/', [AdminCertificateController::class, 'store'])->name('api.v1.admin.certificates.store');
                Route::post('{id}/revoke', [AdminCertificateController::class, 'revoke'])->name('api.v1.admin.certificates.revoke');
            });
        });
    });

});
