# CMC LMS domain plugin

`local_cmc_lms` implementa la base de dominio propia para adaptar Moodle al análisis/diseño de CMC.

## Primer alcance

- Empresas cliente B2B para segmentar alumnos y reportes.
- Asociación de usuarios Moodle a empresas cliente como alumnos o supervisores.
- Programas/mallas formativas compuestas por cursos Moodle.
- Asociación ordenada Programa → Cursos.
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

## Certificados CMC

El primer corte de certificados prioriza trazabilidad y verificación pública sin agregar dependencias externas:

- Tabla `local_cmc_lms_cert` con usuario Moodle, curso Moodle, empresa/programa CMC opcionales, código legible único, token de verificación, emisor, fecha de emisión y metadatos de revocación.
- Emisión idempotente para el mismo usuario+curso+empresa+programa mientras el certificado siga en estado `issued`.
- Administración capability-gated bajo `local/cmc_lms:viewcertificates` y `local/cmc_lms:issuecertificates`.
- Verificación pública sin login en `/local/cmc_lms/verify_certificate.php?t=TOKEN_O_CODIGO`.
- Payload QR: la URL pública de verificación. Este corte no genera imagen QR para evitar dependencias externas; se muestra la URL/payload listo para codificar.

### `local_cmc_lms_enrol_user_in_program`

Asocia idempotentemente un usuario Moodle a una empresa cliente y lo matricula mediante enrolment manual en todos los cursos Moodle vinculados a un programa CMC.

- Parámetros: `companyid`, `userid`, `programid`, `companyrole` opcional (`student`), `roleshortname` opcional (`student`).
- Requiere contexto sistema y ambas capabilities: `local/cmc_lms:managecompanies` y `local/cmc_lms:manageprograms`.
- Valida existencia de empresa, programa, usuario activo/no eliminado y rol Moodle por shortname.
- Retorna `companyassociationid`, ids de entrada y `enrolments[]` con `courseid`, `shortname` y `status` (`enrolled` o `already_enrolled`).

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
- `Site administration → Plugins → CMC LMS domain → Certificates`
- `Site administration → Plugins → CMC LMS domain → B2B reports`

También se puede acceder directamente en desarrollo:

```text
/local/cmc_lms/companies.php
/local/cmc_lms/programs.php
/local/cmc_lms/certificates.php
/local/cmc_lms/reports.php
/local/cmc_lms/verify_certificate.php?t=TOKEN_O_CODIGO
```

Desde la lista de empresas se puede entrar a **Users** para asociar usuarios Moodle a la empresa cliente.

## Reportes B2B

La página **B2B reports** requiere la capability `local/cmc_lms:viewreports` y muestra únicamente empresas activas.

- Resumen por empresa: usuarios asociados activos, alumnos, supervisores cliente, matrículas en cursos Moodle vinculados a programas CMC, finalizaciones y porcentaje de finalización.
- Detalle por empresa: usuarios activos asociados, rol en la empresa, cursos CMC matriculados, cursos CMC completados y porcentaje de avance.
- Las métricas se limitan a cursos presentes en `local_cmc_lms_program_course`; cursos Moodle no vinculados a programas CMC quedan fuera del reporte.
