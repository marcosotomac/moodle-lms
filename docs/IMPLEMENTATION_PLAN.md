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

Requerimientos atendidos:

- LMS 2.1: programas compuestos, cursos y organización de mallas.
- LMS 2.3: asociación de alumnos a empresas cliente.
- LMS 2.7: base técnica para reportes por empresa cliente.
- TDR 7: base para sincronización LMS–CRM por cliente/empresa.

## Próximos cortes sugeridos

1. Asignación automática de alumnos/cursos tras cierre de venta CRM.
2. Dashboard/reportes B2B.
3. Certificados con código único/QR.
