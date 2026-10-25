# 📓 Bitácora Oficial de Avance — Modernización & Desacople Bebé Genial
**Fecha de corte:** 20 de Octubre de 2026  
**Stack de Modernización:** Laravel 11+ (Backend RESTful) · Angular 19+ (Frontend SPA Standalone) · Tailwind CSS v4  
**Base de Datos Aislada:** `bebegenial_desacople` (MySQL 127.0.0.1:3306)  
**Sistema de Diseño:** Nuvex Modern Curve UI (Estrictamente Tema Claro Puro)

---

## 🧭 1. Resumen Ejecutivo de Estado

| Componente | Estado | Cobertura / Calidad | Notas Técnicas Clave |
| :--- | :---: | :---: | :--- |
| **Arquitectura y Modelado** | ✅ 100% | Migraciones limpias | Base de datos `bebegenial_desacople` aislada del legacy. |
| **Auth & Hasher Híbrido** | ✅ 100% | 6/6 tests passing | Hasher dual `LegacyBbpHasher` (`crypt Bbp.` a `bcrypt`). |
| **Sprint 1 Admin: Shell & KPIs**| ✅ 100% | 9/9 tests Karma | Layout curva `rounded-[32px]`, Spotlight, 6 KPIs, barras semestrales. |
| **Enfoque Visual: Modo Claro Puro**| ✅ 100% | 0 clases oscuras | Sidebar blanco, tarjetas blancas, cero modo oscuro según mandato del cliente. |
| **Sprint 2 Admin: Usuarios & Matrículas**| ✅ 100% | 20/20 tests PHPUnit, 13/13 Karma | Tabla elevada suave, User Drawer, Matrícula Modal y exportación CSV. |
| **Sprint 3 Admin: Comercial (WOMPI/Convenios)**| ✅ 100% | 26/26 tests PHPUnit, 26/26 Karma | CRUD convenios/distribuidores, planes Wompi, monitor transacciones, estudiantes asociados. |
| **Sprint 4 Admin: API Leo con LEO B2B**| ✅ 100% | 32/32 tests PHPUnit, 32/32 Karma | Aliados estratégicos, campañas, cuotas de libros y visor de auditoría en tiempo real. |
| **Sprint 5 Admin: Contenidos, Consultas & Certificados**| ⏳ PENDIENTE | Próximo a ejecutar | Cursos, módulos, smartcards, consultas avanzadas, certificados y plantillas. |

---

## 🗄️ 2. Entorno y Credenciales de Prueba Migradas

Se aislaron y migraron dos usuarios auténticos desde `bebegenial_pruebas` hacia `bebegenial_desacople` mediante el seeder idempotente [`MigrateSelectedLegacyUsersSeeder.php`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/backend/database/seeders/MigrateSelectedLegacyUsersSeeder.php):

| Usuario | Rol | Contraseña | Perfil del Menor | Matrículas Asignadas |
| :--- | :---: | :---: | :--- | :--- |
| **`spa@bebe.com`** | `admin` | `123` | BEBE JUNIOR (ID 1) | 6 Cursos vigentes |
| **`usuario1@bebe.com`** | `parent` | `123` | Pruebas (ID 10860) | 9 Cursos vigentes |

* **Regla de Re-hashing Transparente:** Al iniciar sesión con `123` (hash legacy `BbM65Dlq5qvvU`), Laravel valida el hash antiguo y automáticamente lo actualiza a `bcrypt` en base de datos sin fricción para el usuario.

---

## 🎨 3. Decisiones de Diseño y UI: Enforzamiento Tema Claro

Por directriz explícita del usuario:
1. **Sin Modo Oscuro:** Se eliminó cualquier switch o soporte de tema oscuro en el panel de administración. Toda la interfaz corre en colores claros y luminosos.
2. **Lienzo y Fondos:**
   - Lienzo general: Gris suave `#F4F7FB`.
   - Sidebar Flotante: Blanco puro `#FFFFFF` con radio `rounded-[32px]`, borde suave `border-slate-200/80` y píldoras activas en verde esmeralda `#10B981`.
   - Tarjetas Spotlight y KPIs: Fondo blanco puro `#FFFFFF`, tipografía en Slate oscuro `#0F172A`, sombras sutiles difusas y sin stickers ni emojis decorativos.

---

## ⚙️ 4. Inventario de Endpoints Backend Implementados (Laravel 11)

### Autenticación (`/api/v1/auth/`)
* `POST /login`: Autenticación con soporte de credenciales legacy y modernas. Retorna token Sanctum y perfil de usuario.
* `GET /me`: Consulta del perfil autenticado en sesión.
* `POST /logout`: Revocación del Personal Access Token.
* `POST /change-password`: Cambio de contraseña y reseteo del flag `must_change_password`.

### Panel Administrativo (`/api/v1/admin/`) — Protegido por Sanctum & Rol `admin`
* `GET /dashboard/metrics`: 6 KPIs consolidados, series analíticas semestrales y últimos alumnos registrados.
* `GET /courses`: Catálogo completo de cursos activos para selectores de matrícula.
* `GET /users`: Listado paginado con búsqueda por nombre, email, teléfono o nombre del menor, filtros por rol (`admin`, `parent`, `supervisor`) y estado (`active`, `inactive`, `suspended`).
* `GET /users/{id}`: Ficha técnica completa de usuario, menor vinculado (`BabyProfile`) e historial de matrículas.
* `PUT /users/{id}`: Actualización transaccional del acudiente y datos escolares del infante.
* `POST /users/{id}/reset-password`: Restablecimiento asistido de contraseña a `123` con activación de `must_change_password`.
* `POST /users/{id}/enrollments`: Asignación o renovación de matrícula con modalidades `FULL` o `FREEMIUM` (con cupo de libros para *Leo con LEO*) y vigencia configurable.
* `DELETE /enrollments/{id}`: Cancelación o eliminación lógica de una matrícula.

### Módulo Comercial & WOMPI (`/api/v1/admin/`) — Sprint 3
* `GET /commercial-partners`: Listado paginado de aliados comerciales con filtros por tipo (`agreement` o `distributor`), estado y búsqueda textual.
* `POST /commercial-partners`: Creación de convenios o distribuidores con cursos autorizados (JSON) y meses de vigencia permitidos.
* `GET /commercial-partners/{id}`: Detalle de aliado con conteo de alumnos matriculados.
* `PUT /commercial-partners/{id}`: Edición transaccional de parámetros comerciales.
* `DELETE /commercial-partners/{id}`: Eliminación lógica o física del aliado.
* `GET /commercial-partners/{id}/students`: Listado paginado de alumnos matriculados bajo el código del aliado comercial con búsqueda reactiva.
* `GET /wompi/metrics`: 4 métricas consolidadas (Ingresos totales, suscripciones activas, planes activos, tasa de conversión/aprobación).
* `GET /wompi/plans`: Listado de planes de cobro únicos y recurrentes.
* `POST /wompi/plans`: Creación de planes de cobro con periodicidad, días de prueba y cursos incluidos.
* `PUT /wompi/plans/{id}`: Modificación de planes.
* `PATCH /wompi/plans/{id}/toggle-status`: Activación o desactivación rápida de plan.
* `GET /wompi/subscriptions`: Monitor en vivo de transacciones y suscripciones con filtros por estado (`APPROVED`, `DECLINED`, `PENDING`), búsqueda por referencia/correo y paginación.

### Módulo API Leo con LEO B2B & Auditoría (`/api/v1/admin/`) — Sprint 4
* `GET /api-partners`: Listado de aliados B2B con filtros por estado, búsqueda y conteo de campañas y peticiones.
* `POST /api-partners`: Registro de nuevo aliado con generación automática de `origin_code`, `api_key` (`bbg_live_` + random hex) e IP Whitelist.
* `GET /api-partners/{id}`: Detalle del aliado.
* `PUT /api-partners/{id}`: Edición de razón social, slug, contacto e IP Whitelist.
* `POST /api-partners/{id}/toggle-status`: Activación o desactivación de aliado.
* `POST /api-partners/{id}/regenerate-key`: Regeneración segura de API Key.
* `DELETE /api-partners/{id}`: Eliminación protegida (bloqueada si tiene campañas vinculadas).
* `GET /campaigns`: Listado de campañas B2B con filtros por aliado, estado y búsqueda.
* `POST /campaigns`: Creación de campaña con selector de curso, cupo de libros (0 = ilimitado, o cupo numérico) y fechas de vigencia.
* `GET /campaigns/{id}`: Detalle de campaña con conteo de matrículas activas.
* `PUT /campaigns/{id}`: Modificación de parámetros de campaña.
* `POST /campaigns/{id}/toggle-status`: Activación o pausa de campaña.
* `DELETE /campaigns/{id}`: Eliminación de campaña protegida contra campañas con alumnos matriculados.
* `GET /api-audit-logs/metrics`: 4 métricas de tráfico en vivo (total, exitosas 200/201 con % de éxito, validación 400 y rechazos 401/422/500).
* `GET /api-audit-logs`: Listado paginado de logs de auditoría en tiempo real con filtros por aliado, status HTTP, rango de fechas y buscador por correo/campaña.

---

## 💻 5. Componentes Frontend Implementados (Angular 19)

Ubicación: [`src/app/features/admin/`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/)

1. **[`AdminLayoutComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/layouts/admin-layout/admin-layout.component.ts):** Shell maestro con Sidebar curva flotante blanca, navegación tipo píldora, Topbar con buscador rápido, notificaciones y perfil de administrador.
2. **[`AdminDashboardComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/dashboard/dashboard.component.ts):** Hero Spotlight en blanco luminoso, 6 tarjetas KPI con badges circulares de colores sutiles, gráfico semestral de barras esbeltas y tabla de actividad reciente.
3. **[`UsuariosComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/usuarios/usuarios.component.ts):** Módulo central de gestión de alumnos con filtros interactivos, búsqueda reactiva, tabla elevada y exportador a CSV.
4. **[`UserDrawerComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/usuarios/components/user-drawer/user-drawer.component.ts):** Slide-over panel deslizable para soporte técnico, edición de datos del acudiente/bebé, auditoría de matrículas y reseteo de clave.
5. **[`MatriculaModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/usuarios/components/matricula-modal/matricula-modal.component.ts):** Modal dialog con bordes suaves para otorgar cursos con cuotas y vigencias rápidas.
6. **[`ConveniosComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/convenios/convenios.component.ts):** Gestión de convenios institucionales y distribuidores comerciales.
7. **[`PartnerDrawerComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/convenios/components/partner-drawer/partner-drawer.component.ts):** Panel deslizable para alta y edición de aliados comerciales.
8. **[`PartnerStudentsModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/convenios/components/partner-students-modal/partner-students-modal.component.ts):** Modal reactivo de alumnos matriculados por aliado.
9. **[`WompiComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/wompi/wompi.component.ts):** Hub financiero de WOMPI con 4 KPIs de recaudación, planes y monitor de transacciones.
10. **[`PlanModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/wompi/components/plan-modal/plan-modal.component.ts):** Modal flotante de configuración de planes de pago.
11. **[`CampanasComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/campanas/campanas.component.ts):** Hub unificado para API Leo con LEO B2B con pestañas para Aliados, Campañas y Auditoría.
12. **[`PartnerModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/campanas/components/partner-modal/partner-modal.component.ts):** Modal de gestión de aliados externos con IP Whitelist.
13. **[`CampaignModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/campanas/components/campaign-modal/campaign-modal.component.ts):** Modal de configuración de campañas con cuotas pedagógicas.
14. **[`AuditDetailModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/campanas/components/audit-detail-modal/audit-detail-modal.component.ts):** Modal visor de inspección JSON en tiempo real para payloads recibidos y respuestas emitidas.
15. **[`AdminApiCampanasService`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/core/services/admin-api-campanas.service.ts):** Servicio reactivo con Angular Signals para estado B2B unificado.
### ✅ Sprint 5: Catálogo Académico, Contenidos Pedagógicos, Trials, Consultas y Certificados
* **Backend:**
  * Migraciones y modelos para `padres_contenidos`, `course_trials`, `email_templates`, y `certificates`.
  * Controladores RESTful en Laravel 11:
    * `AdminCourseCatalogController`: CRUD de catálogo pedagógico, toggles de activación y visibilidad, y borrado protegido ante matrículas activas.
    * `AdminPadresContenidoController`: Gestión de Smart Cards y Aprendamos Juntos (Zona Padres) por sección, formatos (video, pdf, artículo) y switches de descarga.
    * `AdminTrialController`: Concesión de accesos demo temporales (1 a 365 días), prevención de colisiones de matrícula y revocación inmediata.
    * `AdminOmniSearchController`: Búsqueda omnicanal por nombre, correo, teléfono y nombre del menor retornando expedientes y matrículas vinculadas.
    * `AdminEmailTemplateController`: Actualización de asuntos y plantillas HTML con variables dinámicas.
    * `AdminCertificateController`: Emisión de diplomas con autogeneración de códigos únicos (`CERT-...`) y revocación.
  * 40 pruebas automatizadas en PHPUnit aprobadas (269 aserciones).
* **Frontend:**
  * [`CursosComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/cursos/cursos.component.ts) & [`CourseDrawerComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/cursos/components/course-drawer/course-drawer.component.ts): Gestión integral de cursos con precios en COP, horas y visibilidad pública.
  * [`SmartCardsComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/smart-cards/smart-cards.component.ts) & [`AprendamosJuntosComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/aprendamos-juntos/aprendamos-juntos.component.ts) con [`ContentModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/smart-cards/components/content-modal/content-modal.component.ts): Repositorios interactivos y biblioteca de crianza con soporte multimedia.
  * [`TrialsComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/trials/trials.component.ts) & [`TrialModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/trials/components/trial-modal/trial-modal.component.ts): Monitor y otorgamiento de demos temporales.
  * [`ConsultasComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/consultas/consultas.component.ts): Buscador omnicanal con debounce en tiempo real para rastreo de familias y alumnos.
  * [`PlantillasCorreoComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/plantillas-correo/plantillas-correo.component.ts) & [`TemplateModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/plantillas-correo/components/template-modal/template-modal.component.ts): Editor de correos transaccionales con botones para insertar variables al vuelo.
  * [`CertificadosComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/certificados/certificados.component.ts) & [`CertificateModalComponent`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/features/admin/certificados/components/certificate-modal/certificate-modal.component.ts): Acreditación oficial y emisión de diplomas.
  * [`AdminAcademicService`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/frontend/src/app/core/services/admin-academic.service.ts): Servicio reactivo con Angular Signals para todos los módulos académicos.
  * 52 pruebas unitarias Karma aprobadas (`TOTAL: 52 SUCCESS`, 100% Green).

---

## 🧪 6. Métricas de Calidad y Pruebas Globales

* **Backend (Laravel 11 PHPUnit):**
  * `AdminSprint5ModulesApiTest.php`: 8/8 pasados.
  * `AdminApiCampanasModuleApiTest.php`: 6/6 pasados.
  * `AdminCommercialModuleApiTest.php`: 6/6 pasados.
  * `AdminDashboardApiTest.php`: 2/2 pasados.
  * `AdminUsersApiTest.php`: 7/7 pasados.
  * `AuthApiTest.php`: 6/6 pasados.
  * `LegacyBbpHasherTest.php`: 3/3 pasados.
  * `ExampleTest.php`: 2/2 pasados.
  * **Total Backend:** **40 pruebas exitosas (269 aserciones)** — 100% Green.
* **Frontend (Angular 19 Karma / Jasmine):**
  * `CursosComponent`: pasados.
  * `SmartCardsComponent`: pasados.
  * `AprendamosJuntosComponent`: pasados.
  * `TrialsComponent`: pasados.
  * `ConsultasComponent`: pasados.
  * `PlantillasCorreoComponent`: pasados.
  * `CertificadosComponent`: pasados.
  * `CampanasComponent`: 6/6 pasados.
  * `ConveniosComponent`: 7/7 pasados.
  * `WompiComponent`: 6/6 pasados.
  * `UsuariosComponent`: 7/7 pasados.
  * `DashboardComponent`: 2/2 pasados.
  * `AdminLayoutComponent`: 2/2 pasados.
  * `LoginComponent` & `AppComponent`: 2/2 pasados.
  * **Total Frontend:** **52/52 pruebas unitarias exitosas (100% Green)**.
* **Frontend Bundle:** Compilación exitosa en Angular 19 (`npm run build`, 0 errores).

---

## 🏆 7. Estado del Proyecto: Migración del Administrador Culminada al 100%

Todos los 5 Sprints planificados para la migración del backoffice del administrador legacy de Bebé Genial hacia la arquitectura desacoplada moderna (**Laravel 11 RESTful API + Angular 19 SPA Standalone**) han sido ejecutados y validados con éxito:
1. **Sprint 1:** Shell Nuvex Modern Curve UI, Guardias de Acceso y Dashboard KPIs.
2. **Sprint 2:** Gestión de Usuarios, Alumnos, Matrículas y Expedientes del Menor.
3. **Sprint 3:** Módulo Comercial de Convenios, Distribuidores y Pasarela WOMPI.
4. **Sprint 4:** API Leo con LEO B2B, Campañas, Cuotas y Auditoría en Tiempo Real.
5. **Sprint 5:** Catálogo Académico, Smart Cards, Zona Padres, Trials, OmniSearch y Diplomas.

*Toda la suite de pruebas del backend (40/40) y del frontend (52/52) se encuentra 100% en verde y la interfaz opera estrictamente bajo el estándar Nuvex Modern Curve UI en modo claro puro.*

---

## ⚡ 8. Modernización del Frontend a Angular 21 (LTS Moderno)

Se completó con éxito la migración escalonada del frontend desde **Angular 19** hacia **Angular 21** (`@angular/core@21.2.25` y `@angular/cli@21.2.28`):

1. **Ruta Escalonada Oficial:**
   * **Paso 1:** Salto a Angular 20 (`v20.3.34`) con schematics automáticos de tipado y componentes Standalone.
   * **Paso 2:** Salto a Angular 21 (`v21.2.25`) y `@angular-devkit/build-angular@21.2.28`.
2. **Optimizaciones de Compilación y Rendimiento:**
   * Tiempo de compilación de bundle de producción reducido de **10.7s a 6.1s** (mejora del ~43%).
   * Arquitectura basada en *Application Builder* nativo (esbuild/Vite) con Tailwind CSS v4.
3. **Estabilidad de Testing y Entorno Seguro (`src/test.ts`):**
   * Configuración de inicialización del entorno de pruebas (`initTestEnvironment`) con teardown controlado para evitar fugas de suscripciones asíncronas en Karma.
   * Nuevo script de ejecución única sin modo escucha: `npm run test:ci`.
   * **Métricas Finales en Angular 21:** **52 de 52 pruebas unitarias exitosas (100% Green)** en **0.99 segundos**.

