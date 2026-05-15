# CMC LMS domain plugin

`local_cmc_lms` implementa la base de dominio propia para adaptar Moodle al análisis/diseño de CMC.

## Primer alcance

- Empresas cliente B2B para segmentar alumnos y reportes.
- Asociación de usuarios Moodle a empresas cliente con roles CMC (`student`, `client_supervisor`, docentes y coordinadores). El valor histórico `supervisor` se acepta y se muestra como supervisor cliente.
- Programas/mallas formativas compuestas por cursos Moodle.
- Asociación ordenada Programa → Cursos.
- Metadatos estrictos 5.1/5.2 para programas: versión, modalidad (`async`, `sync`, `blended`), resumen de cambios, vigencia, horas planificadas y estado activo.
- Metadatos estrictos 5.1/5.2 para contenidos vinculados: rol/etiqueta, formato, reutilización, horas, agenda, proveedor/URL de sesión en vivo, creación vía API Zoom/Google Meet y bandera de asistencia.
- Biblioteca CMC de contenidos reutilizables con versiones ISO inmutables, referencia normativa, URL fuente y vinculación auditable a múltiples cursos/programas.
- Base mínima de asistencia por vínculo Programa → Curso y usuario (`present`, `absent`, `late`, `excused`) con UI administrativa para registrar asistencia de alumnos matriculados.
- Integración real con proveedores síncronos: Zoom Server-to-Server OAuth y Google Meet REST API para crear sesiones y guardar URLs generadas.
- Fundación estricta 5.3 de roles/acceso CMC: constantes de negocio, capabilities, y asignaciones coordinador/docente por programa sin reemplazar roles Moodle de curso.
- Cobertura estricta 5.4 para evaluaciones/control de aprendizaje mediante mapeo CMC a cuestionarios Moodle existentes, umbrales de aprobación y reporte histórico de intentos/calificaciones.
- UI administrativa en Moodle para gestionar empresas, programas y cursos por programa.
- Reporte B2B administrativo con resumen por empresa y avance por usuario.
- Cobertura estricta 5.5 para certificados CMC personalizados: emisión manual/automática por finalización Moodle, PDF descargable con QR de verificación, código único, token público, historial de generación y estado emitido/revocado.
- Cobertura estricta 5.6 para experiencia del alumno: panel CMC con cursos activos, avance defensivo, certificados descargables/verificables, notificaciones persistidas/enviadas vía mensajería Moodle y responsive validado para pantallas CMC.
- Cobertura estricta 5.7 para reportes académicos mínimos: inscritos por curso, tasa de finalización, resumen de resultados de evaluación, certificados emitidos/revocados y actividad por empresa cliente.
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
  - `local_cmc_lms_provision_user_in_program`
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
- Acceso scoped por rol CMC: coordinadores/docentes asignados ven sus programas y pueden registrar asistencia de sus sesiones; supervisores cliente ven sus empresas, reportes de empresa y paneles de alumnos asociados; alumnos ven su propio panel.
- Provisioning automático: `local_cmc_lms_provision_user_in_program` crea o reutiliza un usuario Moodle, lo asocia a empresa cliente y lo matricula en todos los cursos vinculados al programa CMC usando enrolment manual y rol Moodle configurable.

La creación manual de usuarios sigue disponible en Moodle core. La creación automática CMC se expone como función externa protegida por `local/cmc_lms:managecompanies` y `local/cmc_lms:manageprograms`; si no se envía password inicial, se genera una contraseña temporal y se fuerza cambio de contraseña.

## Cobertura estricta 5.5 — Certificados CMC

Los certificados priorizan trazabilidad, personalización y verificación pública sin agregar dependencias externas:

- Tabla `local_cmc_lms_cert` con usuario Moodle, curso Moodle, empresa/programa CMC opcionales, código legible único, token de verificación, emisor, fecha de emisión, título personalizado, horas, fecha de finalización, estado de generación PDF y metadatos de revocación.
- Emisión idempotente para el mismo usuario+curso+empresa+programa mientras el certificado siga en estado `issued`.
- Emisión automática mediante observer de `\core\event\course_completed` cuando el curso pertenece a una malla CMC (`local_cmc_lms_program_course`). Si el alumno tiene asociaciones activas a empresas CMC, la emisión queda asociada a esas empresas; si no, se emite con empresa nula y programa asociado.
- Administración capability-gated bajo `local/cmc_lms:viewcertificates` y `local/cmc_lms:issuecertificates`.
- Verificación pública sin login en `/local/cmc_lms/verify_certificate.php?t=TOKEN_O_CODIGO`.
- Descarga autenticada en `/local/cmc_lms/certificate_download.php?certid=ID`: managers con `local/cmc_lms:viewcertificates` pueden descargar cualquiera; el dueño puede descargar sus certificados emitidos. Certificados revocados no se descargan como válidos.
- PDF generado con Moodle core `pdflib.php`/TCPDF e imagen QR vía `write2DBarcode()`. El QR contiene la URL pública de verificación.
- Wordmark local reemplazable en `pix/cmc-logo.svg`. Si TCPDF no puede renderizar el SVG, se usa texto CMC como fallback.

Límite operativo: la emisión automática depende de que la finalización de curso de Moodle esté configurada y dispare `\core\event\course_completed`. Sin completion tracking activo, la emisión queda disponible por vía manual/API de repositorio pero no automática.

## Cobertura estricta 5.6 — Panel alumno y notificaciones

La experiencia de alumno CMC se implementa como capa de lectura y notificación sin duplicar la navegación ni las actividades Moodle:

- Página autenticada `/local/cmc_lms/student.php` para el propio alumno. Managers o usuarios con `local/cmc_lms:viewstudentpanel` pueden agregar `?userid=ID` para ver otro alumno.
- Cursos activos: solo matrículas activas en cursos vinculados a programas CMC activos. Muestra curso, programa, empresa activa si existe, estado de matrícula, estado de finalización y porcentaje de avance.
- Avance defensivo: si Moodle marca el curso como completado, CMC muestra 100%; si no, usa módulos con completion tracking y cuenta finalizaciones de `course_modules_completion`; si no hay módulos trazables, muestra 0%.
- Certificados: lista certificados emitidos del alumno con descarga PDF autenticada y URL pública de verificación.
- Notificaciones: tabla `local_cmc_lms_notification` para `course_start`, `inactivity_reminder` y `certificate_available`, con log idempotente y entrega opcional por `message_send()` mediante providers en `db/messages.php`.
- Automatismos: observer de `\core\event\user_enrolment_created` para inicio de curso CMC, observer existente de `\core\event\course_completed` extendido para avisar certificado disponible, y tarea programada diaria `\local_cmc_lms\task\send_inactivity_reminders`.
- Responsive validado: Moodle theme conserva la responsividad del layout general; `styles.css` del plugin refuerza tablas, formularios, botones y contenido largo para móviles/tablets. La matriz de validación está en `docs/RESPONSIVE_VALIDATION.md`.

Límite operativo: el recordatorio de inactividad usa un umbral simple documentado de 7 días sin `user_lastaccess` reciente en el curso y es idempotente por usuario+curso+programa+tipo para evitar spam diario.

## Cobertura estricta 5.7 — Reportes académicos mínimos

La página `/local/cmc_lms/reports.php` queda como reporte académico administrativo mínimo, protegido por `local/cmc_lms:viewreports`:

- Actividad por empresa cliente: conserva el resumen B2B y el detalle por usuario/empresa ya existente.
- Inscritos por curso y tasa de finalización: una fila por vínculo Programa CMC → Curso Moodle, con alumnos matriculados, completados, activos/incompletos y porcentaje.
- Resumen de evaluaciones: una fila por regla CMC activa sobre Moodle Quiz, con intentos, participantes, aprobados, porcentaje de aprobación y nota final promedio.
- Certificados: últimos certificados emitidos o revocados, con alumno, curso, programa, empresa, estado, emisión y revocación.

Límites deliberados: los reportes se acotan a cursos presentes en `local_cmc_lms_program_course`. Moodle Quiz sigue siendo la autoridad para intentos/notas; si las tablas de Quiz no están disponibles, el resumen de evaluación queda vacío de forma defensiva. La capability `local/cmc_lms:viewcompanyreports` queda como base futura para vistas acotadas a cliente, sin alterar esta página administrativa.

## Cobertura estricta 5.1/5.2 — Slice 1

Este plugin **no duplica** el modelo académico nativo de Moodle. Cursos, secciones, actividades, recursos, lecciones, cuestionarios y finalizaciones siguen siendo responsabilidad de Moodle core y sus plugins estándar. El dominio CMC agrega la capa B2B/programática que Moodle core no conoce:

- `local_cmc_lms_program`: versionado de programa, modalidad, notas de cambio, vigencia y horas planificadas.
- `local_cmc_lms_content_item`: biblioteca de contenidos reutilizables CMC con tipo (`video`, `document`, `external`, `lesson`, `quiz`, `other`), código, URL fuente, referencia ISO, descripción y estado activo.
- `local_cmc_lms_content_version`: historial inmutable por contenido con versión, notas de cambio, vigencia, estado (`draft`, `published`, `obsolete`), creador y fecha de creación. Una corrección normativa ISO se registra como nueva versión, no como edición destructiva.
- `local_cmc_lms_program_course`: metadatos de rol/formato del contenido, vínculo opcional a una versión reutilizable CMC, trazabilidad de reutilización, horas planificadas, agenda, proveedor/URL de sesión sincrónica y activación de asistencia.
- `local_cmc_lms_program_course`: además persiste `liveexternalid`, `liveintegrationstatus` y `liveintegrationerror` para auditar creación vía API.
- `local_cmc_lms_attendance`: asistencia por vínculo programa-curso y usuario, con página administrativa para registrar presentes, ausentes, tarde o justificados sobre alumnos matriculados activos.

La cobertura 5.1/5.2 queda dividida correctamente: Moodle core entrega el contenido real, actividades, lecciones, cuestionarios y recursos; CMC agrega biblioteca reutilizable, versionamiento ISO inmutable y trazabilidad de qué versión se usó en cada programa/curso.

## Plan de implementación LMS 5.1/5.2

1. ✅ Slice 1: metadatos/versionado de programas, modalidad, agenda y enlaces sincrónicos para contenidos, más tabla/repositorio de asistencia inicial.
2. ✅ Slice 2: UI mínima de asistencia para sesiones programa-curso con alumnos matriculados activos e historial.
3. ✅ Slice 3: integración real Zoom/Google Meet API para crear sesiones en vivo desde la vinculación programa-curso.
4. ✅ Slice 4: biblioteca CMC de contenidos reutilizables y versionamiento ISO inmutable enlazable a múltiples programas/cursos.
5. 🔲 Próximo slice: indicadores de cobertura por programa usando actividades, lecciones y cuestionarios de Moodle core.

### Integraciones Zoom / Google Meet

La configuración se realiza en **Site administration → Plugins → CMC LMS domain → Integrations**.

- Zoom usa Server-to-Server OAuth y crea reuniones programadas bajo el usuario configurado.
- Google Meet usa Google Meet REST API `spaces.create` con OAuth JWT de cuenta de servicio y delegación de dominio cuando corresponda.
- Al vincular un curso a un programa, el coordinador selecciona `Zoom` o `Google Meet` y marca **Create live session through provider API**. Si la API responde correctamente, CMC guarda la URL generada en `liveurl`; si falla, conserva el vínculo y registra el error en metadata de integración.

Ver `docs/LIVE_SESSION_INTEGRATIONS.md` para setup y seguridad.

### `local_cmc_lms_enrol_user_in_program`

Asocia idempotentemente un usuario Moodle a una empresa cliente y lo matricula mediante enrolment manual en todos los cursos Moodle vinculados a un programa CMC.

- Parámetros: `companyid`, `userid`, `programid`, `companyrole` opcional (`student`), `roleshortname` opcional (`student`).
- Requiere contexto sistema y ambas capabilities: `local/cmc_lms:managecompanies` y `local/cmc_lms:manageprograms`.
- Valida existencia de empresa, programa, usuario activo/no eliminado y rol Moodle por shortname.
- Retorna `companyassociationid`, ids de entrada y `enrolments[]` con `courseid`, `shortname` y `status` (`enrolled` o `already_enrolled`).

### `local_cmc_lms_provision_user_in_program`

Crea automáticamente un usuario Moodle o reutiliza uno existente por `username`/`email`, lo asocia a empresa cliente y lo matricula en todos los cursos Moodle del programa CMC.

- Parámetros: `companyid`, `programid`, `email`, `firstname`, `lastname`, `username` opcional, `password` opcional, `companyrole` opcional (`student`), `roleshortname` opcional (`student`).
- Requiere contexto sistema y ambas capabilities: `local/cmc_lms:managecompanies` y `local/cmc_lms:manageprograms`.
- Si no existe usuario, crea cuenta `manual`, confirmada, con password entregada o temporal generada; cuando la password es generada, fuerza cambio en el primer acceso.
- Si existe usuario activo por username/email, lo reutiliza sin duplicar cuentas.
- Retorna `userid`, `userstatus` (`created`/`existing`), `companyassociationid`, ids de entrada y `enrolments[]`.

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
- `Site administration → Plugins → CMC LMS domain → Attendance`
- `Site administration → Plugins → CMC LMS domain → Certificates`
- `Site administration → Plugins → CMC LMS domain → Evaluations`
- `Site administration → Plugins → CMC LMS domain → CMC student panel` (solo usuarios con capability para ver otros paneles)
- `Site administration → Plugins → CMC LMS domain → B2B reports`

También se puede acceder directamente en desarrollo:

```text
/local/cmc_lms/companies.php
/local/cmc_lms/programs.php
/local/cmc_lms/program_roles.php
/local/cmc_lms/attendance.php
/local/cmc_lms/certificates.php
/local/cmc_lms/certificate_download.php?certid=ID
/local/cmc_lms/evaluations.php
/local/cmc_lms/student.php
/local/cmc_lms/student.php?userid=ID
/local/cmc_lms/reports.php
/local/cmc_lms/verify_certificate.php?t=TOKEN_O_CODIGO
```

Desde la lista de empresas se puede entrar a **Users** para asociar usuarios Moodle a la empresa cliente.

## Reportes B2B

La página **B2B reports** requiere la capability `local/cmc_lms:viewreports` y muestra reportes académicos CMC mínimos.

- Resumen por empresa: usuarios asociados activos, alumnos, supervisores cliente, matrículas en cursos Moodle vinculados a programas CMC, finalizaciones y porcentaje de finalización.
- Detalle por empresa: usuarios activos asociados, rol en la empresa, cursos CMC matriculados, cursos CMC completados y porcentaje de avance.
- Inscritos/finalización por curso CMC, resumen de evaluaciones Quiz y certificados emitidos/revocados.
- Las métricas se limitan a cursos presentes en `local_cmc_lms_program_course`; cursos Moodle no vinculados a programas CMC quedan fuera del reporte.
