<?php
// /admin/api/auth.php
// ============================================================
// API de Autenticación — Panel de Administración
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
// Incluimos las librerías del proyecto principal, ya que comparten la misma BD
// y lógica base, pero usamos constantes propias del admin.
require_once '../../cursoonline/includes/security.php';
require_once '../../cursoonline/includes/session.php';
require_once '../../cursoonline/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

iniciar_sesion_segura();
$pdo = obtenerConexion(); // Ojo: Obtiene la conexión desde /admin/config/database.php

$accion = isset($_POST['accion']) ? limpiar_entrada($_POST['accion']) : '';

if ($accion === 'login_admin') {
    $correo   = isset($_POST['correo']) ? limpiar_entrada($_POST['correo']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $token    = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $ip       = obtener_ip_cliente();

    // 1. Validar Token CSRF
    if (!validar_token_csrf($pdo, $token)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token de seguridad inválido o expirado.']);
        exit();
    }

    // 2. Control anti-fuerza bruta y bloqueos
    if (verificar_ip_bloqueada($pdo, $ip)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso denegado desde esta dirección IP al panel de administración.']);
        exit();
    }

    if (verificar_bloqueo_temporal($pdo, $correo, $ip)) {
        http_response_code(429);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Demasiados intentos fallidos. Espere 15 minutos e intente nuevamente.']);
        exit();
    }

    if (empty($correo) || empty($password)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Todos los campos son obligatorios.']);
        exit();
    }

    try {
        // Buscar el usuario
        $stmt = $pdo->prepare("
            SELECT id_usuario_pk, primer_nombre, primer_apellido, correo_electronico, contrasena_hash, id_rol_fk, estado_activo
            FROM usuarios 
            WHERE correo_electronico = :correo
        ");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // 3. Verificar si el usuario existe y la contraseña es correcta
        if ($usuario && password_verify($password, $usuario['contrasena_hash'])) {
            
            // 4. VERIFICACIÓN CRÍTICA: Solo el ROL_ADMIN_TOTAL puede ingresar aquí
            // Si el rol no es 1, bloquear el acceso.
            if ((int)$usuario['id_rol_fk'] !== ROL_ADMIN_TOTAL) {
                // Registrar este intento como sospechoso (alguien intentando entrar al admin sin permisos)
                $pdo->prepare("INSERT INTO log_accesos (id_usuario_fk, correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, ?, 'intento_acceso_denegado', ?, ?, 'admin')")
                    ->execute([$usuario['id_usuario_pk'], $correo, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
                
                http_response_code(403);
                echo json_encode(['estado' => 'error', 'mensaje' => 'No tiene los privilegios necesarios para acceder a este panel.']);
                exit();
            }

            if ((int)$usuario['estado_activo'] === 0) {
                http_response_code(403);
                echo json_encode(['estado' => 'error', 'mensaje' => 'Su cuenta administrativa se encuentra inactiva.']);
                exit();
            }

            // Login Exitoso
            limpiar_intentos_fallidos($pdo, $correo, $ip);
            
            // Log de acceso
            $stmt_log = $pdo->prepare("INSERT INTO log_accesos (id_usuario_fk, correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, ?, 'login_exitoso', ?, ?, 'admin')");
            $stmt_log->execute([$usuario['id_usuario_pk'], $correo, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);

            $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario_pk = ?")->execute([$usuario['id_usuario_pk']]);

            // Variables de sesión seguras
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int)$usuario['id_usuario_pk'];
            $_SESSION['id_rol']     = (int)$usuario['id_rol_fk'];
            $_SESSION['nombre']     = $usuario['primer_nombre'] . ' ' . $usuario['primer_apellido'];
            $_SESSION['correo']     = $usuario['correo_electronico'];

            echo json_encode([
                'estado'   => 'ok',
                'mensaje'  => 'Ingreso exitoso al Panel de Administración. Redirigiendo...',
                'redirect' => ADMIN_URL . 'index.php'
            ]);
            exit();

        } else {
            // Login Fallido
            $bloqueado = registrar_intento_fallido($pdo, $correo, $ip, 5, 900); // 5 intentos, 15 min
            
            $stmt_log = $pdo->prepare("INSERT INTO log_accesos (correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, 'login_fallido', ?, ?, 'admin')");
            $stmt_log->execute([$correo, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);

            $msg = 'Credenciales administrativas incorrectas.';
            if ($bloqueado) {
                $msg = 'Ha superado el número máximo de intentos. Su acceso ha sido bloqueado temporalmente por seguridad.';
            }

            http_response_code(401);
            echo json_encode(['estado' => 'error', 'mensaje' => $msg]);
            exit();
        }
    } catch (PDOException $e) {
        error_log("[ADMIN AUTH ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno del servidor.']);
        exit();
    }
}
elseif ($accion === 'logout_admin') {
    if (isset($_SESSION['id_usuario'])) {
        try {
            $ip = obtener_ip_cliente();
            $pdo->prepare("INSERT INTO log_accesos (id_usuario_fk, correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, ?, 'logout', ?, ?, 'admin')")
                ->execute([$_SESSION['id_usuario'], $_SESSION['correo'], $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
        } catch (PDOException $e) { }
    }
    
    destruir_sesion();
    header("Location: " . ADMIN_URL . "auth/login.php");
    exit();
}
else {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no válida en el panel administrativo.']);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /admin/api/auth.php
 * ============================================================
 * API para procesar el login de administradores, validando
 * su rol estricto.
 *
 * Última actualización: Fase 2
 * ============================================================
 */
?>
