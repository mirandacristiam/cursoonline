<?php
// /cursoonline/api/password.php
// ============================================================
// API de Recuperación de Contraseña — EduTech Academy
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

iniciar_sesion_segura();
$pdo = obtenerConexion();

$accion = isset($_POST['accion']) ? limpiar_entrada($_POST['accion']) : '';

// -----------------------------------------------------------------------------
// ACCIÓN: SOLICITAR RECUPERACIÓN DE CONTRASEÑA
// -----------------------------------------------------------------------------
if ($accion === 'solicitar_recuperacion') {
    $token_csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validar_token_csrf($pdo, $token_csrf)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token de seguridad inválido.']);
        exit();
    }

    $correo = isset($_POST['correo']) ? strtolower(limpiar_entrada($_POST['correo'])) : '';
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Correo electrónico no válido.']);
        exit();
    }

    try {
        // Verificar si el correo existe
        $stmt = $pdo->prepare("SELECT id_usuario_pk, estado_activo FROM usuarios WHERE correo_electronico = :correo");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // Por seguridad, siempre devolvemos "éxito" aunque el correo no exista,
        // para evitar la enumeración de usuarios (Account Enumeration)
        $respuesta_exitosa = [
            'estado'  => 'ok',
            'mensaje' => 'Si el correo existe en nuestro sistema, recibirá un enlace para restablecer su contraseña.'
        ];

        if ($usuario && (int)$usuario['estado_activo'] === 1) {
            // Generar token criptográficamente seguro
            $token_recuperacion = bin2hex(random_bytes(32));
            
            // Inactivar tokens anteriores de este usuario
            $pdo->prepare("UPDATE tokens_recuperacion_clave SET estado_usado = 1 WHERE id_usuario_fk = ?")->execute([$usuario['id_usuario_pk']]);
            
            // Guardar nuevo token (Expira en 2 horas = 7200 segundos)
            $stmt_ins = $pdo->prepare("
                INSERT INTO tokens_recuperacion_clave (id_usuario_fk, token_recuperacion, fecha_expiracion) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))
            ");
            $stmt_ins->execute([$usuario['id_usuario_pk'], $token_recuperacion]);
            
            // Enlace de restablecimiento
            $enlace = BASE_URL . "auth/restablecer-contrasena.php?token=" . $token_recuperacion;
            
            // =========================================================
            // AQUÍ IRÍA LA LÓGICA DE ENVÍO DE CORREO (PHPMailer, etc.)
            // =========================================================
            
            // Log de actividad
            $pdo->prepare("INSERT INTO log_actividad_usuario (id_usuario_fk, tipo_accion, descripcion_accion, direccion_ip) VALUES (?, 'RECUPERACION_SOLICITADA', 'Solicitud de recuperación de contraseña', ?)")
                ->execute([$usuario['id_usuario_pk'], obtener_ip_cliente()]);

            // Solo para entorno local/desarrollo, enviamos el enlace en el JSON
            // EN PRODUCCIÓN ESTO DEBE ELIMINARSE POR SEGURIDAD
            $respuesta_exitosa['enlace_simulado'] = $enlace;
        }

        echo json_encode($respuesta_exitosa);
        exit();

    } catch (PDOException $e) {
        error_log("[PASS ERROR] Error en recuperación: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno del servidor.']);
        exit();
    }
}

// -----------------------------------------------------------------------------
// ACCIÓN: RESTABLECER CONTRASEÑA
// -----------------------------------------------------------------------------
elseif ($accion === 'restablecer_contrasena') {
    $token_csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validar_token_csrf($pdo, $token_csrf)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token de seguridad inválido.']);
        exit();
    }

    $token_rec = isset($_POST['token_recuperacion']) ? limpiar_entrada($_POST['token_recuperacion']) : '';
    $password  = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    if (empty($token_rec) || empty($password) || empty($password_confirm)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Todos los campos son obligatorios.']);
        exit();
    }

    if ($password !== $password_confirm) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Las contraseñas no coinciden.']);
        exit();
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
        exit();
    }

    try {
        // Verificar validez del token de recuperación
        $stmt = $pdo->prepare("
            SELECT id_token_pk, id_usuario_fk 
            FROM tokens_recuperacion_clave 
            WHERE token_recuperacion = :token 
              AND estado_usado = 0 
              AND fecha_expiracion > NOW()
        ");
        $stmt->execute([':token' => $token_rec]);
        $registro_token = $stmt->fetch();

        if (!$registro_token) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'El enlace de recuperación es inválido o ha expirado. Solicite uno nuevo.']);
            exit();
        }

        // Actualizar contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $pdo->beginTransaction();
        
        $pdo->prepare("UPDATE usuarios SET contrasena_hash = ? WHERE id_usuario_pk = ?")->execute([$hash, $registro_token['id_usuario_fk']]);
        
        // Invalidar el token usado
        $pdo->prepare("UPDATE tokens_recuperacion_clave SET estado_usado = 1 WHERE id_token_pk = ?")->execute([$registro_token['id_token_pk']]);
        
        // Log de actividad
        $pdo->prepare("INSERT INTO log_actividad_usuario (id_usuario_fk, tipo_accion, descripcion_accion, direccion_ip) VALUES (?, 'PASSWORD_CAMBIADO', 'Contraseña restablecida vía token', ?)")
            ->execute([$registro_token['id_usuario_fk'], obtener_ip_cliente()]);
            
        $pdo->commit();

        echo json_encode([
            'estado'   => 'ok',
            'mensaje'  => 'Contraseña actualizada correctamente. Redirigiendo...',
            'redirect' => BASE_URL . 'auth/login.php'
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("[PASS ERROR] Error al restablecer: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al actualizar la contraseña.']);
    }
}

// -----------------------------------------------------------------------------
// ACCIÓN NO RECONOCIDA
// -----------------------------------------------------------------------------
else {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no válida.']);
}
?>
