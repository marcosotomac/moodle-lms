# Documento de Análisis y Diseño: Sistema de Gestión de Aprendizaje (LMS)

## 1. Introducción
Este documento define los requerimientos funcionales y técnicos específicos para el desarrollo e implementación del **Módulo de Aprendizaje (LMS)** para la empresa CMC & SOLUCIONES EN GESTIÓN HUMANA E.I.R.L. Este sistema busca digitalizar, estructurar y escalar el servicio de formación corporativa a nivel nacional e internacional.

## 2. Requerimientos Funcionales (LMS)

### 2.1 Gestión de Cursos y Contenidos Formativos
El sistema debe permitir una estructuración jerárquica y flexible del conocimiento:
*   **Estructura Jerárquica:** Creación de programas compuestos (mallas formativas, ej. Normas ISO) que agrupan cursos individuales.
*   **Organización Interna:** Cada curso debe poder dividirse en Módulos, y estos a su vez en Lecciones.
*   **Formatos Soportados:**
    *   Video (Capacidad de streaming nativo o contenido embebido de plataformas como YouTube/Vimeo).
    *   Documentos descargables (PDF, Word, PPT).
    *   Recursos externos (enlaces web, referencias a normativas).
*   **Gestión de Activos:**
    *   Reutilización de contenidos (una lección o módulo puede compartirse entre varios cursos o programas).
    *   Versionamiento de contenidos para facilitar la actualización de materiales (fundamental para normas ISO).

### 2.2 Gestión de Modalidades de Formación
El LMS debe adaptarse a diferentes estrategias pedagógicas:
*   **Formación Asíncrona:** Cursos autogestionados (100% online) donde el alumno avanza a su propio ritmo.
*   **Formación Síncrona:**
    *   Integración nativa o por API con plataformas de videoconferencia (Zoom / Google Meet).
    *   Gestión de sesiones en vivo y programación de fechas/horarios.
    *   Registro automático o manual de asistencia a sesiones en vivo.
*   **Formación Mixta (Blended):** Combinación de contenido autogestionado con sesiones en vivo.

### 2.3 Gestión de Usuarios y Roles
El sistema debe poseer un estricto Control de Acceso Basado en Roles (RBAC):
*   **Roles Mínimos:**
    1.  **Administrador del sistema:** Control total de la plataforma y configuraciones.
    2.  **Coordinador académico:** Gestión de mallas, asignación de docentes, supervisión de métricas.
    3.  **Docente (Interno y Externo):** Gestión de sus cursos asignados, calificaciones y sesiones síncronas.
    4.  **Alumno:** Acceso al contenido, evaluaciones y certificaciones.
    5.  *(Opcional)* **Cliente empresa (B2B):** Rol supervisor para ver el progreso exclusivo de los colaboradores de su empresa.
*   **Operaciones:**
    *   Creación manual (por administradores) y automática (vía integración).
    *   Asociación de alumnos a cursos específicos, programas completos o empresas clientes (para modelo B2B).

### 2.4 Evaluaciones y Control de Aprendizaje
Herramientas integradas para medir el conocimiento adquirido:
*   **Tipos de Preguntas:** Creación de evaluaciones con preguntas de Opción Múltiple y Verdadero/Falso.
*   **Niveles de Evaluación:** Capacidad de asignar exámenes al final de un Módulo o al final del Curso completo.
*   **Reglas de Aprobación configurables:**
    *   Definición de puntaje mínimo aprobatorio.
    *   Límite de intentos permitidos.
    *   Tiempo máximo para completar la evaluación.
*   **Trazabilidad:** Registro histórico detallado de los resultados e intentos de cada alumno.

### 2.5 Certificación Digital
Generación de credenciales tras la culminación exitosa:
*   **Generación Automática:** Emisión del certificado al cumplir los criterios de aprobación.
*   **Personalización:** Diseño personalizado con el logo de CMC e inclusión dinámica de datos (Nombre del alumno, nombre del curso, cantidad de horas, fecha de emisión).
*   **Accesibilidad:** Certificados descargables en formato PDF desde el panel del alumno.
*   **Validación:** Cada certificado debe poseer un código único o mecanismo digital (ej. código QR) para validar su autenticidad.
*   **Repositorio:** Registro histórico centralizado de todos los certificados emitidos.

### 2.6 Seguimiento y Experiencia del Alumno (Portal)
Interfaz amigable enfocada en la retención del estudiante:
*   **Dashboard del Alumno:**
    *   Vista rápida de cursos activos y porcentaje de progreso.
    *   Acceso directo a certificados obtenidos.
*   **Sistema de Notificaciones (Automáticas):**
    *   Correos/alertas de bienvenida al inicio de un curso.
    *   Recordatorios de inactividad o fechas de sesiones síncronas.
    *   Felicitaciones y aviso de certificación disponible.

### 2.7 Reportes Académicos y Analítica
Generación de reportes operativos mínimos para el área académica:
*   Listado de alumnos inscritos segmentados por curso.
*   Indicadores de retención: Tasa de finalización frente a tasa de abandono.
*   Resultados de evaluaciones por cohorte o alumno individual.
*   Listado de certificados emitidos en un periodo de tiempo.
*   **Filtro B2B:** Capacidad de visualizar la actividad, progreso y resultados agrupados por *Empresa Cliente*.

## 3. Modelo de Dominio Conceptual (LMS)
Entidades principales identificadas para la estructura de datos:
*   `Usuario` (Vinculado a un rol específico).
*   `Empresa` (Entidad agrupadora para modelo B2B).
*   `Programa` (Malla de cursos).
*   `Curso`.
*   `Módulo` (Agrupador dentro de un curso).
*   `Lección` / `Recurso Multimedia`.
*   `Matrícula` (Asociación Usuario-Curso o Usuario-Programa).
*   `Evaluación` / `Pregunta` / `Intento_Evaluación`.
*   `Sesión_Síncrona` (Incluye URL de reunión y lista de asistencia).
*   `Certificado_Emitido`.

## 4. Requerimientos No Funcionales Destacados (LMS)
*   **UI/UX:** Diseño estrictamente *Responsive* para asegurar que el contenido formativo se consuma adecuadamente en dispositivos móviles, tablets y computadoras de escritorio.
*   **Escalabilidad:** Arquitectura Cloud y diseño modular que permita el crecimiento progresivo de usuarios nacionales e internacionales sin necesidad de refactorizar el código base.
*   **Mantenibilidad:** Facilidad de administración con interfaces intuitivas y configuración guiada, minimizando la dependencia técnica para la operación diaria.