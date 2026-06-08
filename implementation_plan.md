# Plan de Implementación — Plataforma de Cursos Online: EduTech Academy

## Descripción General del Proyecto

**EduTech Academy** es una plataforma web de venta y gestión de cursos online enfocada en **Ingeniería Informática, Sistemas e Inteligencia Artificial**. La plataforma permitirá la compra de cursos, seguimiento del progreso académico, evaluaciones automatizadas, gestión de notas por profesores y un panel administrativo completo con estadísticas y análisis.

---

## 🔁 Reglas del Equipo Confirmadas

| Regla | Estado |
|---|---|
| Archivos CSS, JS, PHP separados (nunca inline) | ✅ Confirmado |
| Ruta del archivo en comentario en línea 1 | ✅ Confirmado |
| Comentarios descriptivos por sección y función | ✅ Confirmado |
| Resumen del archivo al final de cada archivo | ✅ Confirmado |
| `.htaccess` siempre presente y actualizado | ✅ Confirmado |
| SEO completo en todas las páginas HTML/PHP | ✅ Confirmado |
| Validación doble: Frontend + Backend PHP | ✅ Confirmado |
| Seguridad: XSS, CSRF, SQL Injection, Sesiones | ✅ Confirmado |
| Base de datos: PKs con `_pk`, FKs con `_fk` | ✅ Confirmado |
| Nombres de campos sin abreviar | ✅ Confirmado |
| Campos `fecha_creacion`, `fecha_modificacion`, `modificado_por` en cada tabla | ✅ Confirmado |
| Soft delete (campo `estado_activo`) | ✅ Confirmado |
| Stored Procedures para procesos críticos | ✅ Confirmado |
| Paleta: Azul, Azul Claro, Blanco, Gris | ✅ Confirmado |
| AppServer / MySQL — Usuario: root, Pass: 123456789 | ✅ Confirmado |
| Contraseña de prueba para todos los roles: `abc12345` | ✅ Confirmado |

---

## User Review Required

> [!IMPORTANT]
> **Nombre del Proyecto**: Se propone el nombre **EduTech Academy** para el aplicativo. ¿Lo apruebas o prefieres otro nombre?

> [!IMPORTANT]
> **Nombre de la Base de Datos**: Se propone `db_edutechacademy`. ¿Lo apruebas?

> [!IMPORTANT]
> **Carpeta raíz del proyecto**: El proyecto vivirá en `C:\AppServ\www\cursoonline\`. La carpeta del admin estará en `C:\AppServ\www\admin\` (fuera de `cursoonline`). ¿Confirmas esta estructura?

> [!WARNING]
> **Pagos Online (PSE / Bancarios Colombia)**: La integración con PSE real requiere registro en una pasarela de pagos colombiana (ej. **PayU**, **ePayco**, **Wompi** o **MercadoPago Colombia**). Para la fase inicial del proyecto, ¿deseas que implementemos la integración con alguna de estas en particular, o comenzamos con una simulación del flujo de pago que luego se conecte?

> [!IMPORTANT]
> **Videos de los Cursos**: Para los videos explicativos de cada curso, ¿los videos estarán alojados en el servidor propio, o se integrarán desde plataformas externas como **YouTube** o **Vimeo**?

---

## Open Questions

> [!IMPORTANT]
> **Nombre del curso principal**: El curso de "Algoritmos Genéticos" ya está definido. ¿Tienes una lista de los demás cursos que deben aparecer inicialmente en el sistema, o el equipo puede proponer un catálogo inicial de cursos de IA e Informática?

> [!IMPORTANT]
> **Idioma del aplicativo**: ¿El aplicativo web será completamente en **Español**?

> [!NOTE]
> **Dominio / URL de producción**: Para la configuración SEO y CORS correcta, ¿ya tienes un dominio definido para producción, o por ahora trabajamos en entorno local?

---

## Arquitectura del Sistema

```
PÚBLICO (Internet)
      │
      ▼
[cursoonline/] ← Raíz del proyecto (Apache / AppServer)
      │
      ├── index.php               ← Landing Page pública
      ├── .htaccess               ← Seguridad y redirecciones
      │
      ├── /auth/                  ← Login, registro, recuperación de contraseña
      ├── /assets/
      │     ├── /css/             ← Hojas de estilo
      │     ├── /js/              ← Scripts del frontend
      │     └── /images/          ← Imágenes del sitio
      │
      ├── /api/                   ← APIs internas (PHP puro, protegidas)
      ├── /config/                ← Configuración de BD y constantes (protegida)
      ├── /includes/              ← Componentes reutilizables (header, footer, nav)
      │
      ├── /student/               ← Dashboard y módulos del Estudiante
      │     ├── dashboard.php
      │     ├── mis-cursos.php
      │     ├── evaluaciones.php
      │     └── perfil.php
      │
      ├── /teacher/               ← Dashboard y módulos del Profesor
      │     ├── dashboard.php
      │     ├── mis-grupos.php
      │     └── calificaciones.php
      │
      └── /cursos/                ← Páginas públicas de detalle de cursos
      ├── /admin/
      │
      ├── index.php               ← Dashboard Admin Total
      ├── .htaccess
      │
      ├── /auth/
      ├── /assets/ (css, js, images)
      ├── /api/
      ├── /config/
      ├── /includes/
      │
      ├── /cursos/                ← CRUD de Cursos
      ├── /usuarios/              ← CRUD de Usuarios y Roles
      ├── /pagos/                 ← Configuración de medios de pago
      ├── /reportes/              ← Reportes y estadísticas
      └── /seguridad/             ← Logs, auditoría, accesos no autorizados
```

---

## Roles de Usuario

| Rol | Descripción | Dashboard |
|---|---|---|
| `admin_total` | Dueño del sistema. Acceso desde `/admin/` | Estadísticas globales, CRUD de todo, seguridad |
| `profesor` | Sube calificaciones y gestiona grupos | Sus grupos, notas, estadísticas académicas |
| `estudiante` | Compra y accede a cursos | Mis cursos, progreso, notas, evaluaciones |

**Usuarios de prueba (contraseña: `abc12345` para todos):**
| Usuario | Email | Rol |
|---|---|---|
| Admin Total | admin@edutechacademy.com | admin_total |
| Profesor Demo | profesor@edutechacademy.com | profesor |
| Estudiante Demo | estudiante@edutechacademy.com | estudiante |

---

## Módulos del Aplicativo

### 🌐 Módulo Público (No requiere login)
- **Landing Page (index.php)**: Banner principal, catálogo de cursos, testimonios, CTA de registro
- **Detalle del Curso**: Descripción, competencias, temario, horas, imágenes, video, ejemplos de código, precio
- **Formulario de Contacto / Dudas**
- **Login / Registro / Recuperación de contraseña**

### 🎓 Módulo Estudiante
- **Dashboard**: Cursos activos, progreso, promedio de notas, inversión total, accesos rápidos
- **Mis Cursos**: Lista de cursos comprados con progreso
- **Ver Clases**: Acceso al contenido del curso (videos, materiales)
- **Evaluaciones**: Habilitadas por número de clases vistas
- **Mis Notas**: Calificaciones cargadas por el profesor
- **Historial de Pagos**
- **Perfil / Configuración**: Datos personales, cambio de contraseña
- **Notificaciones**

### 👨‍🏫 Módulo Profesor
- **Dashboard**: Total de estudiantes, cursos asignados, notas pendientes
- **Mis Grupos**: Estudiantes por curso
- **Cargar Calificaciones**: Por estudiante y por actividad
- **Perfil / Configuración**
- **Notificaciones**

### 🔐 Módulo Admin Total (desde `/admin/`)
- **Dashboard Global**: KPIs (cursos totales, ventas, usuarios activos, ingresos)
- **Gestión de Cursos**: CRUD completo de cursos
- **Gestión de Usuarios**: Todos los roles, activar/desactivar
- **Gestión de Pagos**: Configurar medios de pago, ver transacciones
- **Reportes**: Ventas, rendimiento académico, por curso
- **Módulo de Seguridad**: Logs de acceso, intentos fallidos, IPs bloqueadas, auditoría
- **Notificaciones**: Enviar notificaciones a roles específicos

---

## Diseño de Base de Datos — Tablas Principales

### Tablas de Seguridad y Auditoría
- `log_accesos` — Registra cada login/logout con IP, timestamp, resultado
- `log_errores_sistema` — Errores PHP capturados
- `log_intentos_fallidos` — Intentos de login fallidos (para bloqueo por fuerza bruta)
- `log_actividad_usuario` — Acciones críticas realizadas por usuarios
- `ips_bloqueadas` — IPs bloqueadas por comportamiento sospechoso
- `tokens_csrf` — Tokens CSRF activos por sesión

### Tablas de Negocio
- `usuarios` — Todos los usuarios del sistema (todos los roles)
- `roles` — Catálogo de roles
- `cursos` — Información de cada curso
- `modulos_curso` — Módulos o unidades dentro de un curso
- `clases_curso` — Clases individuales dentro de cada módulo
- `inscripciones` — Relación estudiante ↔ curso comprado
- `progreso_clases` — Qué clases ha visto cada estudiante
- `evaluaciones` — Evaluaciones disponibles por curso
- `preguntas_evaluacion` — Preguntas de cada evaluación
- `respuestas_evaluacion` — Respuestas del estudiante
- `calificaciones` — Notas cargadas por el profesor
- `transacciones_pago` — Historial de pagos
- `medios_pago` — Configuración de métodos de pago
- `notificaciones` — Notificaciones del sistema
- `configuracion_sistema` — Parámetros globales del sistema
- `materiales_curso` — Archivos y recursos de cada clase

---

## Stack Tecnológico Propuesto por el Equipo

### Frontend
| Tecnología | Rol |
|---|---|
| **Bootstrap 5.3** | Framework CSS base — diseño responsivo |
| **Font Awesome 6** | Iconografía |
| **Google Fonts (Inter + Poppins)** | Tipografía moderna y profesional |
| **Apache ECharts** | Gráficas y dashboards analíticos |
| **Highlight.js** | Resaltado de código en detalle de cursos |
| **SweetAlert2** | Alertas y confirmaciones premium |
| **Animate.css + AOS.js** | Animaciones de entrada suaves |
| **DataTables.js** | Tablas interactivas con búsqueda y paginación |

### Backend
| Tecnología | Rol |
|---|---|
| **PHP 8.x** | Lógica del servidor |
| **MySQL 8.x** | Base de datos relacional |
| **PDO** | Capa de abstracción segura para BD (previene SQL Injection) |
| **Stored Procedures** | Procesos críticos de negocio |

### Seguridad
| Medida | Implementación |
|---|---|
| CSRF Tokens | Generados en cada formulario, validados en PHP |
| XSS Protection | `htmlspecialchars()` en toda salida de datos |
| SQL Injection | PDO con prepared statements SIEMPRE |
| Brute Force | Bloqueo tras 5 intentos fallidos |
| Sesiones seguras | `session_start()`, `session_regenerate_id()` |
| Contraseñas | `password_hash()` / `password_verify()` |
| Headers HTTP | `X-Frame-Options`, `X-Content-Type-Options`, `CSP` |

---

## Paleta de Colores Oficial

| Nombre | Hex | Uso |
|---|---|---|
| Azul Primario | `#1A3C6E` | Encabezados, botones CTA, navbar |
| Azul Medio | `#2563EB` | Botones secundarios, links activos |
| Azul Claro | `#60A5FA` | Acentos, badges, highlights |
| Azul Cielo | `#DBEAFE` | Fondos de sección, cards |
| Blanco | `#FFFFFF` | Fondo principal |
| Gris Claro | `#F1F5F9` | Fondos alternos |
| Gris Medio | `#64748B` | Texto secundario |
| Gris Oscuro | `#1E293B` | Texto principal |

---

## Catálogo Inicial de Cursos Propuesto por el Equipo

| # | Curso | Área | Horas |
|---|---|---|---|
| 1 | **Algoritmos Genéticos y Computación Evolutiva** | IA | 120h |
| 2 | **Machine Learning con Python desde Cero** | IA | 160h |
| 3 | **Deep Learning y Redes Neuronales con TensorFlow** | IA | 180h |
| 4 | **Programación en Python para Ingeniería** | Informática | 100h |
| 5 | **Desarrollo Web con PHP y MySQL** | Sistemas | 140h |
| 6 | **Estructuras de Datos y Algoritmos** | Informática | 120h |
| 7 | **Bases de Datos Avanzadas con MySQL** | Sistemas | 90h |
| 8 | **Seguridad Informática y Ciberseguridad** | Sistemas | 110h |

---

## Plan de Desarrollo por Fases

### ✅ Fase 1 — Fundamentos y Base de Datos
1. Crear estructura de carpetas completa
2. Crear base de datos con todas las tablas
3. Insertar datos de prueba (usuarios, cursos iniciales)
4. Archivo de configuración y conexión a BD
5. `.htaccess` base del proyecto

### ✅ Fase 2 — Autenticación y Seguridad Base
1. Sistema de Login / Registro / Recuperación de contraseña
2. Sistema de sesiones y control de roles
3. Tokens CSRF
4. Protección contra fuerza bruta

### ✅ Fase 3 — Landing Page Pública
1. index.php con banner, catálogo y CTA
2. Página de detalle de curso (con código resaltado, video, temario)
3. Formulario de contacto
4. SEO completo en todas las páginas

### ✅ Fase 4 — Dashboard Estudiante
1. Dashboard con estadísticas
2. Módulo "Mis Cursos" y progreso
3. Módulo de Evaluaciones
4. Mis Calificaciones
5. Perfil y configuración
6. Notificaciones

### ✅ Fase 5 — Dashboard Profesor
1. Dashboard con estadísticas de sus grupos
2. Gestión de estudiantes por curso
3. Módulo de calificaciones

### ✅ Fase 6 — Panel Admin Total
1. Dashboard global con ECharts
2. CRUD de Cursos
3. CRUD de Usuarios
4. Configuración de pagos
5. Reportes
6. Módulo de Seguridad (logs, IPs, auditoría)

### ✅ Fase 7 — Pagos
1. Flujo de pago simulado (base)
2. Integración con pasarela (PayU/ePayco según decisión)

---

## Verificación del Plan

### Verificación Técnica
- Prueba de conexión a BD desde AppServer
- Validación de formularios en frontend y backend
- Prueba de roles y control de acceso
- Revisión de headers de seguridad

### Verificación Visual
- Revisión de diseño responsivo en desktop y móvil
- Revisión de animaciones y experiencia de usuario
- Validación de la paleta de colores aplicada

---

> **Esperando aprobación del Ingeniero Director para iniciar la Fase 1.**
