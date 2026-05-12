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

## Corte 3 — Estructura, modalidad y versionamiento académico

Objetivo: cerrar la base estricta de LMS 5.1 y 5.2 sin duplicar el modelo nativo de Moodle.

Incluye:

- Programas CMC con versión, modalidad (`async`, `sync`, `blended`), notas de cambio, fecha efectiva y horas planificadas.
- Relación Programa → Curso enriquecida con etiqueta de contenido, formato (`video`, `document`, `external`, `lesson`, `quiz`, `other`), notas de reutilización, horas, fechas programadas, proveedor/enlace de sesión en vivo y bandera de asistencia.
- Fundación de asistencia en `local_cmc_lms_attendance` para registrar presentes/ausentes/tarde/justificados por usuario y curso dentro de programa.
- APIs MCP compatibles hacia atrás que aceptan campos nuevos opcionales y devuelven la metadata enriquecida.
- UI administrativa para capturar versión/modalidad/programación sin editar Moodle core.

Decisión del corte: Moodle core sigue siendo la fuente de verdad para cursos, secciones, actividades, recursos, lecciones, quizzes y completitud. El plugin CMC agrega la capa de negocio: mallas, versión, modalidad, planificación, reutilización y trazabilidad B2B.

Requerimientos atendidos:

- LMS 5.1: programas, cursos, metadata de módulos/lecciones vía curso Moodle, formatos de contenido, reutilización documentada y versionamiento.
- LMS 5.2: modalidad asincrónica/sincrónica/mixta, programación, enlaces Zoom/Meet/Teams/BBB/otros y base de asistencia.

## Corte 4 — Roles académicos y acceso CMC

Objetivo: cerrar la base estricta de LMS 5.3 sin reemplazar el modelo nativo de usuarios, autenticación, matrículas y roles de curso de Moodle.

Incluye:

- Modelo centralizado de roles CMC: `coordinator`, `teacher_internal`, `teacher_external`, `student` y `client_supervisor`.
- Compatibilidad hacia atrás para el valor histórico `supervisor`, normalizado a `client_supervisor` en nuevas escrituras/visualizaciones.
- Asociación usuario↔empresa ampliada para alumnos, supervisores empresa, docentes internos/externos y coordinadores.
- Tabla `local_cmc_lms_program_role` para asignar coordinadores/docentes a programas CMC sin duplicar los roles de curso Moodle.
- UI administrativa y tool MCP `local_cmc_lms_assign_program_role` para asignaciones de roles por programa.
- Capabilities CMC para gestión de contenido académico, roles de programa, docencia, panel alumno y reportes por empresa.

Decisión del corte: Moodle core sigue manejando creación manual/automática de usuarios, autenticación, roles de curso y permisos dentro del curso. El plugin CMC agrega la capa B2B/programa necesaria para segmentación, reportes, dashboards y flujos académicos propios.

Requerimientos atendidos:

- LMS 5.3: roles mínimos del dominio, asociación a empresas/programas y base de accesos diferenciados.

## Corte 5 — Evaluaciones CMC sobre Moodle Quiz

Objetivo: cubrir LMS 5.4 de forma estricta sin duplicar el motor de evaluaciones de Moodle.

Incluye:

- Tabla `local_cmc_lms_eval_rule` para vincular programa CMC opcional, curso Moodle, instancia Quiz y course module.
- Reglas de negocio CMC con alcance `module`/`course`, nota o porcentaje de aprobación, intentos máximos, tiempo límite y bandera activa.
- Repositorio para listar cuestionarios Moodle disponibles, crear/actualizar reglas idempotentemente y agregar resultados históricos desde `quiz_attempts`/`quiz_grades`.
- UI administrativa `Evaluations` protegida por `local/cmc_lms:manageevaluations` y `local/cmc_lms:viewevaluationreports`.
- Tool MCP/read-only `local_cmc_lms_get_evaluation_rules` para listar reglas y resultados agregados de una regla.

Decisión del corte: Moodle Quiz sigue manejando preguntas MCQ/verdadero-falso, intentos, temporización y cálculo de nota final. CMC solo agrega mapeo, umbrales y reporting para control de aprendizaje.

Requerimientos atendidos:

- LMS 5.4: evaluaciones/control del aprendizaje, criterios de aprobación, intentos, tiempos e historial usando Moodle Quiz como fuente de verdad y CMC como capa de trazabilidad/reporting.

## Corte 6 — Certificados automáticos PDF/QR

Objetivo: cerrar LMS 5.5 con certificados personalizados, descargables y verificables, emitidos automáticamente desde completitud Moodle.

Incluye:

- Metadata de certificado para título, horas del curso, fecha de completitud, marca de generación PDF y fecha de generación.
- Observador Moodle `\core\event\course_completed` para emitir certificados automáticamente cuando un curso completado pertenece a una malla CMC.
- Emisión por programa y empresa activa cuando hay asociación B2B; emisión por programa sin empresa cuando no hay asociación activa.
- Generador PDF con Moodle core `pdflib.php`/TCPDF, sin dependencias externas.
- QR renderizado en PDF con `write2DBarcode` apuntando a la URL pública de verificación.
- Endpoint `certificate_download.php` para descarga del PDF por administradores o por el alumno propietario.
- Wordmark local reemplazable `pix/cmc-logo.svg` como fallback hasta contar con el logo oficial CMC.

Decisión del corte: Moodle core sigue siendo la fuente de verdad para completitud. La emisión automática depende de que los cursos tengan criterios de finalización configurados correctamente; si la aprobación de un Quiz forma parte de la completitud del curso, el certificado queda condicionado por esa aprobación.

Requerimientos atendidos:

- LMS 5.5: certificados automáticos, personalizados con datos del alumno/curso/horas/fecha, descargables en PDF, con código único, QR de validación y registro histórico.

## Corte 7 — Panel alumno y notificaciones CMC

Objetivo: cubrir LMS 5.6 con una experiencia de alumno trazable que reúna avance, certificados y comunicaciones automáticas sin reemplazar Moodle core.

Incluye:

- Repositorio de lectura `student_repository` para cursos CMC activos del alumno, empresa/programa asociado, matrícula, finalización y progreso defensivo.
- Página `/local/cmc_lms/student.php` para el propio alumno; consulta de terceros limitada a managers o usuarios con `local/cmc_lms:viewstudentpanel`.
- Tabla `local_cmc_lms_notification` para persistir `course_start`, `inactivity_reminder` y `certificate_available` con estado de envío.
- Servicio `notification_service` idempotente, integrado con Moodle `message_send()` y providers declarados en `db/messages.php`.
- Observer de `\core\event\user_enrolment_created` para notificar inicio de curso CMC.
- Extensión del observer de completitud para notificar certificado disponible cuando se emite automáticamente.
- Tarea programada diaria de recordatorios por inactividad con umbral simple de 7 días sin acceso reciente al curso, idempotente por usuario+curso+programa+tipo.

Decisión del corte: Moodle sigue siendo fuente de verdad para matrícula, acceso al curso, finalización, módulos y mensajería. CMC agrega un panel consolidado y un log auditable de comunicaciones propias.

Requerimientos atendidos:

- LMS 5.6: panel del estudiante con cursos activos, progreso, certificados y notificaciones automáticas.

## Corte 8 — Reportes académicos mínimos 5.7

Objetivo: cubrir LMS 5.7 con reportes académicos administrativos mínimos, sin duplicar motores nativos de Moodle.

Incluye:

- Repositorio `report_repository` extendido con filas de inscritos/finalización por curso CMC, resumen agregado de evaluaciones Moodle Quiz y certificados emitidos/revocados.
- Página `/local/cmc_lms/reports.php` ampliada para mostrar actividad por empresa cliente, inscritos por curso, tasa de finalización, evaluaciones, certificados y detalle B2B existente.
- Pruebas focalizadas para filas de matrícula/finalización por curso y reporte de certificados.
- Versionado del plugin a `0.5.7` sin cambios de esquema.

Decisión del corte: el reporte se acota estrictamente a cursos vinculados mediante `local_cmc_lms_program_course`. Moodle core conserva la autoridad sobre matrículas, completitud, Quiz y certificados PDF; CMC agrega agregación académica y trazabilidad B2B. `local/cmc_lms:viewreports` sigue protegiendo la página administrativa; `local/cmc_lms:viewcompanyreports` queda como base futura para una vista scoped de cliente.

Requerimientos atendidos:

- LMS 5.7: reportes académicos mínimos de alumnos inscritos por curso, tasa de finalización, resultados de evaluación, certificados emitidos/revocados y actividad por empresa cliente.

## Corte 9 — Registro administrativo de asistencia 5.2

Objetivo: convertir la base de asistencia 5.2 en una pantalla usable para coordinadores, sin reemplazar matrículas ni sesiones de Moodle core.

Incluye:

- Página `/local/cmc_lms/attendance.php` protegida por `local/cmc_lms:manageattendance`.
- Selector de vínculos Programa CMC → Curso Moodle con asistencia habilitada.
- Roster generado desde matrículas Moodle activas del curso seleccionado.
- Registro masivo de estados `present`, `absent`, `late` y `excused` con fecha/hora aplicable.
- Historial de asistencia por sesión programa-curso.

Decisión del corte: CMC registra la evidencia de asistencia del contexto B2B/programa, pero Moodle core conserva la autoridad sobre usuarios, matrículas y cursos. La integración API nativa Zoom/Meet sigue fuera de alcance; por ahora se conserva metadata `liveprovider`/`liveurl` y registro manual.

Requerimientos atendidos:

- LMS 5.2: registro operativo de asistencia en sesiones en vivo planificadas.

## Próximos cortes sugeridos

1. Filtros por periodo/programa/empresa y exportación CSV/XLSX de reportes 5.7.
2. Reportes agregados de asistencia y reglas avanzadas de recordatorios configurables por programa.
