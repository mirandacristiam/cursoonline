<?php
// /cursoonline/auth/logout.php
// ============================================================
// Cierre de Sesión Seguro — EduTech Academy
// ============================================================

// No se requieren otras dependencias para garantizar que el logout
// siempre funcione, incluso si hay errores de BD o de otros archivos.

// 1. Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Intentar registrar el logout en BD (completamente silencioso)
if (!empty($_SESSION['id_usuario'])) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo_logout = obtenerConexion();
        $ip_logout  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Log solo si la tabla existe (evitar fatal en instalaciones nuevas)
        $pdo_logout->prepare("
            INSERT IGNORE INTO log_accesos
                (id_usuario_fk, correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador)
            VALUES (?, ?, 'logout', ?, ?)
        ")->execute([
            $_SESSION['id_usuario'],
            $_SESSION['correo'] ?? '',
            $ip_logout,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    } catch (Throwable $e) {
        // Silencioso: el logout no debe fallar por un error de log
    }
}

// 3. Destruir la sesión completamente
$_SESSION = [];

// Borrar la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 86400,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// 4. Redirigir al inicio con flag de logout exitoso
header('Location: ../index.php?logout=1');
exit();
