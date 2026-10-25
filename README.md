# 👶 Ecosistema Bebé Genial — Documentación de Migración y Modernización

Bienvenido al repositorio central de planificación técnica y arquitectura para la modernización de la plataforma **Bebé Genial**, migrando desde el monolito procedural en PHP hacia un stack desacoplado de alto rendimiento: **Laravel 11+ (Backend)** y **Angular 18/19+ (Frontend)**.

---

## 📚 Índice de Documentación Técnica

Los siguientes documentos detallan el análisis, la arquitectura y el plan de ejecución:

| Documento | Descripción |
| :--- | :--- |
| 📄 **[`01_analisis_arquitectura_actual_monolito.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/01_analisis_arquitectura_actual_monolito.md)** | Diagnóstico técnico exhaustivo del código actual en `D:\xampp\htdocs\aula`, inventario de módulos, diagrama entidad-relación y análisis de riesgos críticos (claves legacy, sesiones en disco, acoplamiento de cuentos y pagos). |
| 🏛️ **[`02_arquitectura_objetivo_backend_laravel.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/02_arquitectura_objetivo_backend_laravel.md)** | Diseño de la arquitectura objetivo en Laravel 11: capas de dominio, autenticación stateless con Sanctum, implementación del `LegacyBbpHasher` para contraseñas, colas asíncronas con Redis y streaming seguro de cuentos vía Signed URLs. |
| 🚀 **[`03_plan_trabajo_fase1_desacople_backend.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/03_plan_trabajo_fase1_desacople_backend.md)** | Plan de trabajo detallado para arrancar con la **Fase 1**, dividido en 4 sprints ágiles (Auth, Core Académico, Cuentos/API B2B y Wompi/Scheduler) con matriz de endpoints y criterios de aceptación (DoD). |
| 🗄️ **[`04_estrategia_base_datos_nueva_y_migracion_etl.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/04_estrategia_base_datos_nueva_y_migracion_etl.md)** | Estrategia de aislamiento con **Base de Datos Nueva (`bebegenial_desacople`)**, comparativa de esquemas (limpieza de `cpass`, corrección de `fecha-matricula`, normalización), configuración dual en Laravel y pipeline ETL de migración para el Go-Live. |
| 🎨 **[`05_frontend_arquitectura_y_login.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/05_frontend_arquitectura_y_login.md)** | Arquitectura base del frontend en **Angular 19**, integración con Sanctum, componentes standalone, diseño Nuvex Modern Curve UI y módulo de Login implementado. |
| 🛡️ **[`06_plan_trabajo_frontend_administrador.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/06_plan_trabajo_frontend_administrador.md)** | Plan de trabajo completo para la migración del Backoffice Administrativo en **Angular 19**, inventario de módulos legacy (`aula/admin/`), sprints de ejecución y estándar Nuvex Modern Curve UI. |
| 📓 **[`07_bitacora_desacople_y_frontend_admin.md`](file:///D:/Users/Usuario/Documents/Nuvex/BebeGenial/07_bitacora_desacople_y_frontend_admin.md)** | Bitácora oficial de avance: resumen de Sprints 1 y 2, estado de la base de datos `bebegenial_desacople`, enforzamiento del tema claro, endpoints creados y métricas de calidad (20 tests backend, 13 tests frontend). |

---

## 🎯 Resumen de la Fase 1 (Objetivo Inmediato)

* **Meta:** Desacoplar el 100% de la lógica de negocio del monolito legacy hacia un API RESTful en **Laravel 11**.
* **Resultado Esperado:** Un backend robusto, escalable y documentado (OpenAPI / Swagger) listo para alimentar las aplicaciones cliente en **Angular 18/19** (Portal del Alumno, Portal de Distribuidores y Backoffice Administrativo Nuvex Modern Curve UI).
