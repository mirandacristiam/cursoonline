<?php
// /cursoonline/admin/config/constants.php
// ============================================================
// Constantes del panel de administración — EduTech Academy
// NOTA: admin ahora está dentro de cursoonline/admin/
// ============================================================

// ============================================================
// INFORMACIÓN DEL SISTEMA ADMIN
// ============================================================
define('SITE_NAME',          'EduTech Academy');
define('ADMIN_PANEL_NAME',   'EduTech Academy — Panel Administrativo');
define('SITE_DESCRIPTION',   'Panel de control administrativo de EduTech Academy.');
define('SITE_EMAIL',         'admin@edutechacademy.com');
define('SITE_AUTHOR',        'EduTech Academy');

// ============================================================
// RUTAS DEL SISTEMA
// admin está en /cursoonline/admin/
// ============================================================
define('BASE_URL',           'http://localhost/cursoonline/');
define('ADMIN_URL',          'http://localhost/cursoonline/admin/');
define('ADMIN_BASE_PATH',    realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
define('ADMIN_ASSETS_URL',   ADMIN_URL . 'assets/');
define('ADMIN_UPLOADS_PATH', ADMIN_BASE_PATH . 'assets/images/uploads/');

// ============================================================
// ROLES DEL SISTEMA (igual que en cursoonline/config/constants.php)
// ============================================================
define('ROL_ADMIN_TOTAL',    1);
define('ROL_PROFESOR',       2);
define('ROL_ESTUDIANTE',     3);

// ============================================================
// CONFIGURACIÓN DE SEGURIDAD (más estricta para admin)
// ============================================================
define('SESSION_LIFETIME',       3600);
define('MAX_INTENTOS_LOGIN',     3);
define('TIEMPO_BLOQUEO_SEG',     1800);
define('TOKEN_EXPIRACION_SEG',   3600);
define('CSRF_EXPIRACION_SEG',    3600);

// ============================================================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================================================
define('REGISTROS_POR_PAGINA',   15);
define('MAX_REGISTROS_EXPORT',   5000);

// ============================================================
// VERSIÓN Y ENTORNO
// ============================================================
define('APP_VERSION',        '1.0.0');
define('APP_ENV',            'development');
define('APP_DEBUG',          true);

// ============================================================
// ZONA HORARIA Y MONEDA
// ============================================================
date_default_timezone_set('America/Bogota');
define('MONEDA_SIMBOLO',     '$');
define('MONEDA_CODIGO',      'COP');
define('MONEDA_NOMBRE',      'Peso Colombiano');

/*
 * RESUMEN: /cursoonline/admin/config/constants.php
 * Constantes para el panel admin, ahora dentro de cursoonline/admin/.
 * BASE_URL y ADMIN_URL apuntan a http://localhost/cursoonline/.
 */
?>
