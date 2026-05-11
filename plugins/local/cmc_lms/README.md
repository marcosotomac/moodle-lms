# CMC LMS domain plugin

`local_cmc_lms` implementa la base de dominio propia para adaptar Moodle al análisis/diseño de CMC.

## Primer alcance

- Empresas cliente B2B para segmentar alumnos y reportes.
- Asociación de usuarios Moodle a empresas cliente como alumnos o supervisores.
- Programas/mallas formativas compuestas por cursos Moodle.
- Asociación ordenada Programa → Cursos.
- UI administrativa en Moodle para gestionar empresas, programas y cursos por programa.
- Funciones externas read-only listas para exponerse por `webservice_mcp`:
  - `local_cmc_lms_get_companies`
  - `local_cmc_lms_get_programs`
- Funciones externas write protegidas por capabilities:
  - `local_cmc_lms_create_company`
  - `local_cmc_lms_create_program`
  - `local_cmc_lms_add_program_course`
  - `local_cmc_lms_get_company_users`
  - `local_cmc_lms_add_company_user`

## Requerimientos cubiertos inicialmente

- Gestión de programas compuestos y cursos individuales.
- Asociación de usuarios a empresas cliente.
- Base para filtro B2B en reportes académicos.
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

También se puede acceder directamente en desarrollo:

```text
/local/cmc_lms/companies.php
/local/cmc_lms/programs.php
```

Desde la lista de empresas se puede entrar a **Users** para asociar usuarios Moodle a la empresa cliente.
