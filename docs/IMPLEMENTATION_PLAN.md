# Plan de implementación inicial

El documento de análisis/diseño y el TDR describen una solución LMS + CRM. El desarrollo se implementa en incrementos pequeños sobre Moodle, empezando por el dominio propio que Moodle no resuelve de forma explícita.

## Corte 1 — Dominio LMS CMC

Objetivo: crear una base de datos y API interna para modelar conceptos centrales del negocio.

Incluye:

- Empresas cliente B2B.
- Relación usuario ↔ empresa.
- Programas/mallas formativas.
- Relación programa ↔ cursos Moodle.
- Funciones externas read-only para consumo vía MCP.
- Funciones externas write protegidas por capabilities para crear empresas, crear programas y vincular cursos.
- UI administrativa inicial para gestionar empresas, programas y cursos vinculados.
- UI/API para asociar usuarios Moodle a empresas cliente con rol de alumno o supervisor.
- Tool MCP para asociar usuario↔empresa y matricularlo en todos los cursos de un programa.
- Dashboard/reportes B2B iniciales con resumen por empresa y detalle de avance por usuario.

Requerimientos atendidos:

- LMS 2.1: programas compuestos, cursos y organización de mallas.
- LMS 2.3: asociación de alumnos a empresas cliente.
- LMS 2.7: reportes iniciales por empresa cliente, acotados a cursos vinculados a programas CMC.
- TDR 7: base para sincronización LMS–CRM por cliente/empresa.

## Corte 2 — Certificados verificables CMC

Objetivo: habilitar un primer flujo seguro de emisión y verificación pública de certificados sin acoplarse todavía a generación PDF.

Incluye:

- Tabla `local_cmc_lms_cert` enlazada a usuario Moodle, curso Moodle, empresa CMC opcional y programa CMC opcional.
- Código único legible y token/hash público para verificación.
- Estado `issued`/`revoked` con metadatos de revocación.
- Repositorio de dominio para emisión idempotente, listado reciente, verificación pública y revocación.
- Capabilities `local/cmc_lms:viewcertificates` y `local/cmc_lms:issuecertificates`.
- UI administrativa para emitir manualmente, listar certificados recientes, revocar y copiar URL/payload QR.
- Página pública `/local/cmc_lms/verify_certificate.php?t=...` sin login para validar certificados.

Decisión del corte: no se agrega generación de PDF ni librerías externas de QR. El payload QR es la URL pública de verificación, lista para ser codificada por una herramienta posterior o por una integración segura.

Requerimientos atendidos:

- LMS 2.4/2.8: constancia/certificado verificable asociado a avance académico.
- TDR 7: base trazable para futuras integraciones CRM/documentales por usuario, empresa y programa.

## Próximos cortes sugeridos

1. Generación de PDF de certificado usando una vía compatible con Moodle core si está disponible en el entorno objetivo.
2. Generación/renderizado QR server-side sólo si se confirma una API core/local existente; si no, mantener payload y delegar QR a la plantilla/PDF.
3. Endurecer reportes B2B con filtros por programa/fechas y exportación CSV si el negocio lo prioriza.
