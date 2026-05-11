# CMC LMS domain plugin

`local_cmc_lms` implementa la base de dominio propia para adaptar Moodle al análisis/diseño de CMC.

## Primer alcance

- Empresas cliente B2B para segmentar alumnos y reportes.
- Programas/mallas formativas compuestas por cursos Moodle.
- Asociación ordenada Programa → Cursos.
- Funciones externas read-only listas para exponerse por `webservice_mcp`:
  - `local_cmc_lms_get_companies`
  - `local_cmc_lms_get_programs`

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
