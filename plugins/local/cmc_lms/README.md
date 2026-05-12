# CMC LMS domain plugin

`local_cmc_lms` implementa la base de dominio propia para adaptar Moodle al análisis/diseño de CMC.

## Primer alcance

- Empresas cliente B2B para segmentar alumnos y reportes.
- Asociación de usuarios Moodle a empresas cliente con roles CMC (`student`, `client_supervisor`, docentes y coordinadores). El valor histórico `supervisor` se acepta y se muestra como supervisor cliente.
- Programas/mallas formativas compuestas por cursos Moodle.
- Asociación ordenada Programa → Cursos.
- Metadatos estrictos 5.1/5.2 para programas: versión, modalidad (`async`, `sync`, `blended`), resumen de cambios, vigencia, horas planificadas y estado activo.
- Metadatos estrictos 5.1/5.2 para contenidos vinculados: rol/etiqueta, formato, reutilización, horas, agenda, proveedor/URL de sesión en vivo y bandera de asistencia.
- Base mínima de asistencia por vínculo Programa → Curso y usuario (`present`, `absent`, `late`, `excused`).
- Fundación estricta 5.3 de roles/acceso CMC: constantes de negocio, capabilities, y asignaciones coordinador/docente por programa sin reemplazar roles Moodle de curso.
- Cobertura estricta 5.4 para evaluaciones/control de aprendizaje mediante mapeo CMC a cuestionarios Moodle existentes, umbrales de aprobación y reporte histórico de intentos/calificaciones.
- UI administrativa en Moodle para gestionar empresas, programas y cursos por programa.
- Reporte B2B administrativo con resumen por empresa y avance por usuario.
- Certificados CMC emitidos para usuario+curso con empresa/programa opcionales, código único, token público de verificación y estado emitido/revocado.
- Funciones externas read/write listas para exponerse por `webservice_mcp`:
  - `local_cmc_lms_get_companies`
  - `local_cmc_lms_get_programs`
- Funciones externas write protegidas por capabilities:
  - `local_cmc_lms_create_company`
  - `local_cmc_lms_create_program`
  - `local_cmc_lms_add_program_course`
  - `local_cmc_lms_get_company_users`
  - `local_cmc_lms_add_company_user`
  - `local_cmc_lms_enrol_user_in_program`
  - `local_cmc_lms_assign_program_role`
  - `local_cmc_lms_get_evaluation_rules`

## Cobertura estricta 5.4 — evaluaciones y control del aprendizaje

El plugin **no crea preguntas ni actividades de evaluación propias**. Moodle Quiz sigue siendo la autoridad para:

- Preguntas de opción múltiple y verdadero/falso.
- Nota, método de calificación, intentos, límite de tiempo e historial de intentos.
- Tablas core `quiz`, `quiz_attempts` y `quiz_grades`.

CMC agrega la capa de negocio que Moodle core no conoce:

- Tabla `local_cmc_lms_eval_rule` para mapear un programa CMC opcional, curso Moodle, instancia Quiz y course module.
- Alcance `module` o `course`, nota/porcentaje de aprobación CMC, intentos máximos, límite de tiempo y estado activo.
- UI administrativa en `/local/cmc_lms/evaluations.php` para elegir cuestionarios existentes, crear/actualizar reglas idempotentes y ver un resumen histórico por alumno.
- Tool MCP/read-only `local_cmc_lms_get_evaluation_rules` para listar reglas y, opcionalmente, resultados agregados de una regla.

Supuesto de reporte: la nota final se lee de `quiz_grades.grade`, porque Moodle Quiz ya aplicó ahí el método de calificación configurado. Si todavía no hay nota final, el reporte escala defensivamente el mejor intento finalizado (`quiz_attempts.sumgrades`) a la escala `quiz.grade`.

## Cobertura estricta 5.3 — usuarios, roles y acceso

Moodle core sigue siendo la autoridad para autenticación, creación de usuarios, enrolments y permisos dentro del curso. Este plugin agrega la capa de negocio CMC para B2B/programas:

- Roles CMC centralizados: `coordinator`, `teacher_internal`, `teacher_external`, `student`, `client_supervisor`.
- Empresas cliente: soportan `student` y `client_supervisor` como roles principales, y también docentes/coordinadores cuando el flujo B2B lo necesita. Entradas legacy `supervisor` se normalizan a `client_supervisor`.
- Capabilities nuevas sin crear roles Moodle por código:
  - `local/cmc_lms:manageprogramcontent` para coordinar/gestionar programas y contenido académico.
  - `local/cmc_lms:manageprogramroles` para asignaciones CMC de docentes/coordinadores.
  - `local/cmc_lms:teachprograms` como base para ver/dictar programas o cursos asignados.
  - `local/cmc_lms:viewstudentpanel` como base del panel de alumno.
  - `local/cmc_lms:viewcompanyreports` como base para reportes acotados por cliente/empresa.
- Tabla `local_cmc_lms_program_role`: asigna Moodle users a programas CMC con rol `coordinator`, `teacher_internal` o `teacher_external` y bandera activa. Esto complementa Moodle enrolment roles; NO decide permisos dentro del curso.

No se crean usuarios Moodle automáticamente en este slice. La creación manual/automática queda en Moodle core/auth/MCP según la configuración de la plataforma.

## Certificados CMC

El primer corte de certificados prioriza trazabilidad y verificación pública sin agregar dependencias externas:

- Tabla `local_cmc_lms_cert` con usuario Moodle, curso Moodle, empresa/programa CMC opcionales, código legible único, token de verificación, emisor, fecha de emisión y metadatos de revocación.
- Emisión idempotente para el mismo usuario+curso+empresa+programa mientras el certificado siga en estado `issued`.
- Administración capability-gated bajo `local/cmc_lms:viewcertificates` y `local/cmc_lms:issuecertificates`.
- Verificación pública sin login en `/local/cmc_lms/verify_certificate.php?t=TOKEN_O_CODIGO`.
- Payload QR: la URL pública de verificación. Este corte no genera imagen QR para evitar dependencias externas; se muestra la URL/payload listo para codificar.

## Cobertura estricta 5.1/5.2 — Slice 1

Este plugin **no duplica** el modelo académico nativo de Moodle. Cursos, secciones, actividades, recursos, lecciones, cuestionarios y finalizaciones siguen siendo responsabilidad de Moodle core y sus plugins estándar. El dominio CMC agrega la capa B2B/programática que Moodle core no conoce:

- `local_cmc_lms_program`: versionado de programa, modalidad, notas de cambio, vigencia y horas planificadas.
- `local_cmc_lms_program_course`: metadatos de rol/formato del contenido, trazabilidad de reutilización, horas planificadas, agenda, proveedor/URL de sesión sincrónica y activación de asistencia.
- `local_cmc_lms_attendance`: fundación de asistencia por vínculo programa-curso y usuario, suficiente para registrar estados iniciales y ampliar reportes en slices posteriores.

La cobertura 5.1/5.2 de este slice es deliberadamente honesta: CMC estructura, versiona y agenda la oferta; Moodle core entrega el contenido real, actividades, lecciones, cuestionarios y recursos.

## Plan de implementación LMS 5.1/5.2

1. ✅ Slice 1: metadatos/versionado de programas, modalidad, agenda y enlaces sincrónicos para contenidos, más tabla/repositorio de asistencia inicial.
2. 🔲 Próximo slice: UI/reportes de asistencia, validaciones cruzadas de agenda y experiencia de edición de vínculos existentes.
3. 🔲 Próximo slice: indicadores de cobertura por programa usando actividades, lecciones y cuestionarios de Moodle core.

### `local_cmc_lms_enrol_user_in_program`

Asocia idempotentemente un usuario Moodle a una empresa cliente y lo matricula mediante enrolment manual en todos los cursos Moodle vinculados a un programa CMC.

- Parámetros: `companyid`, `userid`, `programid`, `companyrole` opcional (`student`), `roleshortname` opcional (`student`).
- Requiere contexto sistema y ambas capabilities: `local/cmc_lms:managecompanies` y `local/cmc_lms:manageprograms`.
- Valida existencia de empresa, programa, usuario activo/no eliminado y rol Moodle por shortname.
- Retorna `companyassociationid`, ids de entrada y `enrolments[]` con `courseid`, `shortname` y `status` (`enrolled` o `already_enrolled`).

### `local_cmc_lms_assign_program_role`

Asigna idempotentemente un rol CMC de programa (`coordinator`, `teacher_internal`, `teacher_external`) a un usuario Moodle existente.

- Parámetros: `programid`, `userid`, `cmcrole` opcional (`teacher_internal`), `active` opcional (`true`).
- Requiere contexto sistema y capability `local/cmc_lms:manageprogramroles`.
- Valida existencia del programa y usuario Moodle activo/no eliminado.
- No crea enrolments ni roles Moodle de curso; eso sigue separado a propósito.

## Requerimientos cubiertos inicialmente

- Gestión de programas compuestos y cursos individuales.
- Asociación de usuarios a empresas cliente.
- Base para filtro B2B en reportes académicos.
- Dashboard B2B inicial con métricas de usuarios, matrículas y finalizaciones por empresa.
- Emisión/revocación administrativa de certificados y verificación pública por código/token.
- Base para integración MCP/REST sin acoplarse a UI.

## Instalación local

Copiar este directorio a:

```text
moodle/public/local/cmc_lms
```

Después ejecutar el upgrade de Moodle.

## Administración

Una vez instalado, las páginas quedan bajo administración del sitio:

- `Site administration → Plugins → CMC LMS domain → Client companies`
- `Site administration → Plugins → CMC LMS domain → Training programs`
- `Site administration → Plugins → CMC LMS domain → Program roles`
- `Site administration → Plugins → CMC LMS domain → Certificates`
- `Site administration → Plugins → CMC LMS domain → Evaluations`
- `Site administration → Plugins → CMC LMS domain → B2B reports`

También se puede acceder directamente en desarrollo:

```text
/local/cmc_lms/companies.php
/local/cmc_lms/programs.php
/local/cmc_lms/program_roles.php
/local/cmc_lms/certificates.php
/local/cmc_lms/evaluations.php
/local/cmc_lms/reports.php
/local/cmc_lms/verify_certificate.php?t=TOKEN_O_CODIGO
```

Desde la lista de empresas se puede entrar a **Users** para asociar usuarios Moodle a la empresa cliente.

## Reportes B2B

La página **B2B reports** requiere la capability `local/cmc_lms:viewreports` y muestra únicamente empresas activas.

- Resumen por empresa: usuarios asociados activos, alumnos, supervisores cliente, matrículas en cursos Moodle vinculados a programas CMC, finalizaciones y porcentaje de finalización.
- Detalle por empresa: usuarios activos asociados, rol en la empresa, cursos CMC matriculados, cursos CMC completados y porcentaje de avance.
- Las métricas se limitan a cursos presentes en `local_cmc_lms_program_course`; cursos Moodle no vinculados a programas CMC quedan fuera del reporte.
