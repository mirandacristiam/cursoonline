<?php
// /cursoonline/config/constants.php
// ============================================================
// Constantes globales de la aplicación EduTech Academy
// ============================================================

// ============================================================
// INFORMACIÓN GENERAL DEL SITIO
// ============================================================
define('SITE_NAME',          'EduTech Academy');
define('SITE_TAGLINE',       'Aprende Ingeniería, Informática e Inteligencia Artificial');
define('SITE_DESCRIPTION',   'Plataforma de cursos online especializados en Ingeniería Informática, Sistemas e Inteligencia Artificial. Algoritmos Genéticos, Machine Learning, Deep Learning y más.');
define('SITE_KEYWORDS',      'cursos online, ingeniería informática, inteligencia artificial, machine learning, algoritmos genéticos, programación, sistemas, Colombia');
define('SITE_EMAIL',         'contacto@edutechacademy.com');
define('SITE_PHONE',         '+57 300 000 0000');
define('SITE_ADDRESS',       'Colombia');
define('SITE_AUTHOR',        'EduTech Academy');

// ============================================================
// RUTAS DEL SISTEMA
// ============================================================
define('BASE_URL',           'http://localhost/cursoonline/');
define('ADMIN_URL',          'http://localhost/admin/');
define('BASE_PATH',          realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
define('ASSETS_URL',         BASE_URL . 'assets/');
define('UPLOADS_PATH',       BASE_PATH . 'assets/images/uploads/');
define('UPLOADS_URL',        BASE_URL . 'assets/images/uploads/');

// ============================================================
// ROLES DEL SISTEMA
// ============================================================
define('ROL_ADMIN_TOTAL',    1);   // Administrador total del sistema
define('ROL_PROFESOR',       2);   // Profesor / Docente
define('ROL_ESTUDIANTE',     3);   // Estudiante / Alumno

// ============================================================
// CONFIGURACIÓN DE SEGURIDAD Y SESIONES
// ============================================================
define('SESSION_LIFETIME',       3600);   // Duración sesión: 1 hora (segundos)
define('MAX_INTENTOS_LOGIN',     5);      // Intentos máximos antes de bloqueo
define('TIEMPO_BLOQUEO_SEG',     900);    // Tiempo de bloqueo: 15 minutos
define('TOKEN_EXPIRACION_SEG',   3600);   // Expiración token de recuperación: 1 hora
define('CSRF_EXPIRACION_SEG',    7200);   // Expiración token CSRF: 2 horas

// ============================================================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================================================
define('REGISTROS_POR_PAGINA',   10);
define('MAX_REGISTROS_EXPORT',   1000);

// ============================================================
// CONFIGURACIÓN DE ARCHIVOS
// ============================================================
define('MAX_UPLOAD_SIZE_MB',     10);     // Tamaño máximo de subida en MB
define('TIPOS_IMAGEN_PERMITIDOS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('TIPOS_DOC_PERMITIDOS',    ['pdf', 'docx', 'xlsx', 'pptx', 'zip']);

// ============================================================
// VERSIÓN Y ENTORNO
// ============================================================
define('APP_VERSION',        '1.0.0');
define('APP_ENV',            'development');   // 'development' o 'production'
define('APP_DEBUG',          true);            // false en producción

// ============================================================
// ZONA HORARIA
// ============================================================
date_default_timezone_set('America/Bogota');

// ============================================================
// CONFIGURACIÓN DE MONEDA (Colombia)
// ============================================================
define('MONEDA_SIMBOLO',     '$');
define('MONEDA_CODIGO',      'COP');
define('MONEDA_NOMBRE',      'Peso Colombiano');

// ============================================================
// COLORES DE LA PALETA (para uso en PHP si es necesario)
// ============================================================
define('COLOR_PRIMARIO',     '#1A3C6E');   // Azul primario
define('COLOR_SECUNDARIO',   '#2563EB');   // Azul medio
define('COLOR_ACENTO',       '#60A5FA');   // Azul claro
define('COLOR_FONDO',        '#DBEAFE');   // Azul cielo (fondos)

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/config/constants.php
 * ============================================================
 * Define todas las constantes globales de la aplicación EduTech Academy:
 *
 *   - Información del sitio (nombre, descripción, SEO keywords, contacto)
 *   - Rutas del sistema (URL base, rutas de archivos, assets, uploads)
 *   - Identificadores de roles (admin_total, profesor, estudiante)
 *   - Configuración de seguridad (duración de sesión, intentos de login,
 *     tiempos de bloqueo, expiración de tokens CSRF y recuperación)
 *   - Configuración de paginación y exportación
 *   - Restricciones de subida de archivos (tamaño y tipos permitidos)
 *   - Versión de la app, entorno de ejecución y modo debug
 *   - Zona horaria de Colombia (America/Bogota)
 *   - Configuración de moneda (COP - Peso Colombiano)
 *   - Paleta de colores oficial del proyecto
 *
 * Última actualización: Fase 1 — Fundamentos y Base de Datos
 * ============================================================
 */
?>
