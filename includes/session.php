<?php
// /cursoonline/includes/session.php
// ============================================================
// Gestor seguro de sesiones — EduTech Academy
// ============================================================

/**
 * Inicia la sesión PHP con configuraciones de seguridad estrictas.
 * Debe llamarse al inicio de todos los archivos que requieran sesión.
 */
function iniciar_sesion_segura() {
    // Si la sesión ya está iniciada, no hacer nada
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // --- Configuraciones de seguridad para la cookie de sesión ---
    // httponly = true: Impide que JavaScript acceda a la cookie (Protección XSS)
    // samesite = Strict: Impide el envío de la cookie en peticiones cruzadas (Protección CSRF)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Sólo enviar por HTTPS si estamos en un servidor seguro
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Evitar que el ID de sesión se pase por URL
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_trans_sid', 0);
    
    session_start();
    
    // --- Prevención de Session Fixation y expiración ---
    if (!isset($_SESSION['ultima_actividad'])) {
        // Primera vez que se inicia la sesión
        $_SESSION['ultima_actividad'] = time();
    } else {
        // Verificar si la sesión ha expirado
        $inactividad = time() - $_SESSION['ultima_actividad'];
        if ($inactividad > SESSION_LIFETIME) {
            destruir_sesion();
            header("Location: " . BASE_URL . "auth/login.php?error=expirada");
            exit();
        }
        
        // Regenerar ID de sesión cada 15 minutos para prevenir secuestro
        if (!isset($_SESSION['ultimo_regen']) || (time() - $_SESSION['ultimo_regen'] > 900)) {
            session_regenerate_id(true);
            $_SESSION['ultimo_regen'] = time();
        }
        
        // Actualizar el timestamp de actividad
        $_SESSION['ultima_actividad'] = time();
    }
}

/**
 * Destruye la sesión actual por completo y borra la cookie del navegador.
 */
function destruir_sesion() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $_SESSION = array(); // Vaciar variables de sesión
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Verifica si hay un usuario logueado. 
 * Si no lo hay, redirige al login.
 */
function verificar_login_requerido() {
    iniciar_sesion_segura();
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: " . BASE_URL . "auth/login.php?error=acceso_denegado");
        exit();
    }
}

/**
 * Verifica si el usuario actual tiene el rol especificado.
 * Si no lo tiene, redirige a una página de error o a su dashboard correspondiente.
 *
 * @param int $rol_requerido El ID del rol que se necesita.
 */
function requerir_rol($rol_requerido) {
    verificar_login_requerido();
    
    if ($_SESSION['id_rol'] !== $rol_requerido) {
        // Si no tiene el rol, llevarlo a la raíz para que el sistema 
        // lo redirija a su dashboard real o a página de acceso denegado
        header("Location: " . BASE_URL . "error/403.php");
        exit();
    }
}

/**
 * Redirige al usuario a su dashboard correspondiente según su rol.
 */
function redirigir_segun_rol() {
    if (!isset($_SESSION['id_rol'])) return;
    
    switch ($_SESSION['id_rol']) {
        case ROL_ADMIN_TOTAL:
            header("Location: " . ADMIN_URL . "index.php");
            break;
        case ROL_PROFESOR:
            header("Location: " . BASE_URL . "teacher/dashboard.php");
            break;
        case ROL_ESTUDIANTE:
            header("Location: " . BASE_URL . "student/dashboard.php");
            break;
        default:
            header("Location: " . BASE_URL . "index.php");
            break;
    }
    exit();
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/includes/session.php
 * ============================================================
 * Gestor de sesiones seguro.
 * - iniciar_sesion_segura(): Configura cookies con httponly y samesite=strict.
 *   Implementa prevención de Session Fixation regenerando el ID cada 15 min.
 *   Implementa expiración de sesión por inactividad.
 * - destruir_sesion(): Borra todas las variables y la cookie.
 * - verificar_login_requerido(): Middleware para páginas protegidas.
 * - requerir_rol(): Middleware para proteger carpetas por rol.
 * - redirigir_segun_rol(): Enrutador post-login.
 *
 * Última actualización: Fase 2 — Autenticación y Seguridad
 * ============================================================
 */
?>
