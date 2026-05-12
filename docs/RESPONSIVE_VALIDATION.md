# Responsive validation — CMC LMS

Este documento deja explícita la validación responsive del plugin `local_cmc_lms` sobre Moodle.

## Alcance

La responsividad base de navegación, header, drawer y layout principal la aporta el theme Moodle activo. El plugin CMC valida y refuerza sus propias superficies:

- tablas administrativas (`html_table` / `.generaltable`);
- formularios Moodle (`moodleform` / `.mform`);
- acciones principales (`singlebutton`, botones y selectores);
- URLs, nombres largos, códigos de certificado y textos de reporte.

## Breakpoints validados

| Breakpoint | Dispositivo objetivo | Criterio |
| --- | --- | --- |
| 375px | móvil angosto | no hay overflow horizontal del layout; tablas tienen scroll local; formularios apilan campos. |
| 768px | tablet vertical | tablas y formularios no rompen el contenedor; acciones siguen visibles. |
| 1024px | tablet horizontal / notebook chica | tablas usan ancho disponible y mantienen legibilidad. |
| 1440px | desktop | comportamiento Moodle estándar sin restricciones artificiales. |

## Pantallas CMC cubiertas

| Pantalla | Ruta | Validación |
| --- | --- | --- |
| Empresas | `/local/cmc_lms/companies.php` | tabla scrollable en móvil; acciones visibles. |
| Usuarios de empresa | `/local/cmc_lms/company_users.php` | nombres/emails largos envuelven sin romper layout. |
| Programas | `/local/cmc_lms/programs.php` | listado de cursos y metadata envuelve; tabla no desborda el viewport. |
| Roles de programa | `/local/cmc_lms/program_roles.php` | tabla y acciones compactas en móvil. |
| Asistencia | `/local/cmc_lms/attendance.php` | selector, roster, historial y tabla de detalles adaptan ancho. |
| Evaluaciones | `/local/cmc_lms/evaluations.php` | tablas de reglas/resultados con scroll local. |
| Certificados | `/local/cmc_lms/certificates.php` | códigos/URLs/acciones no rompen el contenedor. |
| Panel alumno | `/local/cmc_lms/student.php` | cursos, certificados y notificaciones se mantienen legibles en móvil. |
| Reportes | `/local/cmc_lms/reports.php` | tablas académicas amplias tienen scroll horizontal local. |
| Verificación pública | `/local/cmc_lms/verify_certificate.php` | detalle de certificado legible en móvil. |

## Implementación

La validación queda respaldada por `plugins/local/cmc_lms/styles.css`, cargado por Moodle como hoja de estilos del plugin. Las reglas están acotadas a páginas CMC mediante `body.path-local-cmc_lms` y `body[id^="page-local-cmc_lms-"]`.

Decisiones aplicadas:

- Las tablas no se convierten en tarjetas para conservar semántica Moodle y accesibilidad; en móvil reciben scroll horizontal local.
- Los formularios se apilan bajo 768px para evitar labels y campos comprimidos.
- Campos largos (`URL`, emails, códigos, nombres de curso/programa) usan wrapping defensivo.
- Los botones principales ocupan ancho completo en móvil cuando el theme los renderiza dentro de `singlebutton`.

## Resultado

Con esta capa, el requisito LMS 5.6 de diseño responsive queda cubierto a nivel de plugin CMC, usando Moodle theme como base y `styles.css` como refuerzo específico para pantallas CMC.
