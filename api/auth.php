<?php
// /cursoonline/api/auth.php
// ============================================================
// API de Autenticación — EduTech Academy
// Maneja Login, Registro y Cierre de Sesión.
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

// --- Obtener la acción a realizar ---
$accion = isset($_POST['accion']) ? limpiar_entrada($_POST['accion']) : '';

// -----------------------------------------------------------------------------
// ACCIÓN: LOGIN
// -----------------------------------------------------------------------------
if ($accion === 'login') {
    $correo   = isset($_POST['correo']) ? limpiar_entrada($_POST['correo']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $token    = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $ip       = obtener_ip_cliente();

    // 1. Validar Token CSRF
    if (!validar_token_csrf($pdo, $token)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token de seguridad inválido o expirado. Intente recargar la página.']);
        exit();
    }

    // 2. Verificar si la IP está bloqueada de forma permanente
    if (verificar_ip_bloqueada($pdo, $ip)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso denegado desde esta dirección IP.']);
        exit();
    }

    // 3. Verificar bloqueo temporal por fuerza bruta
    if (verificar_bloqueo_temporal($pdo, $correo, $ip)) {
        http_response_code(429);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Demasiados intentos fallidos. Espere 15 minutos e intente nuevamente.']);
        exit();
    }

    // 4. Validar campos requeridos
    if (empty($correo) || empty($password)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Todos los campos son obligatorios.']);
        exit();
    }

    // 5. Buscar usuario en la base de datos
    try {
        $stmt = $pdo->prepare("
            SELECT id_usuario_pk, primer_nombre, primer_apellido, correo_electronico, contrasena_hash, id_rol_fk, estado_activo
            FROM usuarios 
            WHERE correo_electronico = :correo
        ");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // 6. Verificar si existe y la contraseña es correcta
        if ($usuario && password_verify($password, $usuario['contrasena_hash'])) {
            
            // Verificar si la cuenta está inactiva
            if ((int)$usuario['estado_activo'] === 0) {
                http_response_code(403);
                echo json_encode(['estado' => 'error', 'mensaje' => 'Su cuenta se encuentra inactiva. Contacte al administrador.']);
                exit();
            }

            // Login Exitoso
            limpiar_intentos_fallidos($pdo, $correo, $ip);
            
            // Registrar log de acceso exitoso
            $stmt_log = $pdo->prepare("INSERT INTO log_accesos (id_usuario_fk, correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, ?, 'login_exitoso', ?, ?, 'publico')");
            $stmt_log->execute([$usuario['id_usuario_pk'], $correo, $ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido']);

            // Actualizar último acceso
            $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario_pk = ?")->execute([$usuario['id_usuario_pk']]);

            // Guardar datos en la sesión
            session_regenerate_id(true); // Prevenir fixation
            $_SESSION['id_usuario'] = (int)$usuario['id_usuario_pk'];
            $_SESSION['id_rol']     = (int)$usuario['id_rol_fk'];
            $_SESSION['nombre']     = $usuario['primer_nombre'] . ' ' . $usuario['primer_apellido'];
            $_SESSION['correo']     = $usuario['correo_electronico'];

            // Determinar la URL de redirección según el rol
            $url_redirect = BASE_URL;
            if ($_SESSION['id_rol'] === ROL_ADMIN_TOTAL) {
                $url_redirect = ADMIN_URL . "index.php";
            } elseif ($_SESSION['id_rol'] === ROL_PROFESOR) {
                $url_redirect = BASE_URL . "teacher/dashboard.php";
            } elseif ($_SESSION['id_rol'] === ROL_ESTUDIANTE) {
                $url_redirect = BASE_URL . "student/dashboard.php";
            }

            echo json_encode([
                'estado'   => 'ok',
                'mensaje'  => 'Ingreso exitoso. Redirigiendo...',
                'redirect' => $url_redirect
            ]);
            exit();

        } else {
            // Login Fallido
            $bloqueado = registrar_intento_fallido($pdo, $correo, $ip, MAX_INTENTOS_LOGIN, TIEMPO_BLOQUEO_SEG);
            
            // Registrar log
            $stmt_log = $pdo->prepare("INSERT INTO log_accesos (correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, 'login_fallido', ?, ?, 'publico')");
            $stmt_log->execute([$correo, $ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido']);

            $msg = 'Correo electrónico o contraseña incorrectos.';
            if ($bloqueado) {
                $msg = 'Ha superado el número máximo de intentos. Su cuenta/IP ha sido bloqueada temporalmente por 15 minutos.';
            }

            http_response_code(401);
            echo json_encode(['estado' => 'error', 'mensaje' => $msg]);
            exit();
        }
    } catch (PDOException $e) {
        error_log("[AUTH ERROR] Error en el proceso de login: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno del servidor.']);
        exit();
    }
}

// -----------------------------------------------------------------------------
// ACCIÓN: LOGOUT
// -----------------------------------------------------------------------------
elseif ($accion === 'logout') {
    if (isset($_SESSION['id_usuario'])) {
        try {
            $ip = obtener_ip_cliente();
            $stmt_log = $pdo->prepare("INSERT INTO log_accesos (id_usuario_fk, correo_electronico_intento, tipo_accion, direccion_ip, agente_navegador, panel_acceso) VALUES (?, ?, 'logout', ?, ?, 'publico')");
            $stmt_log->execute([$_SESSION['id_usuario'], $_SESSION['correo'], $ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido']);
        } catch (PDOException $e) { }
    }
    
    destruir_sesion();
    
    // Si viene por AJAX (API), devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['estado' => 'ok', 'redirect' => BASE_URL . 'index.php']);
    } else {
        // Si no, redirigir directo
        header("Location: " . BASE_URL . "index.php");
    }
    exit();
}

// -----------------------------------------------------------------------------
// ACCIÓN: REGISTRO (Solo para Estudiantes)
// -----------------------------------------------------------------------------
elseif ($accion === 'registro') {
    // Validar CSRF
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validar_token_csrf($pdo, $token)) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token de seguridad inválido.']);
        exit();
    }

    $nombres   = isset($_POST['nombres']) ? limpiar_entrada($_POST['nombres']) : '';
    $apellidos = isset($_POST['apellidos']) ? limpiar_entrada($_POST['apellidos']) : '';
    $correo    = isset($_POST['correo']) ? strtolower(limpiar_entrada($_POST['correo'])) : '';
    $password  = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    if (empty($nombres) || empty($apellidos) || empty($correo) || empty($password)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Todos los campos marcados con * son obligatorios.']);
        exit();
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El formato del correo electrónico no es válido.']);
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

    // Dividir nombres y apellidos (lógica básica)
    $partes_nombres = explode(' ', $nombres, 2);
    $primer_nombre  = $partes_nombres[0];
    $segundo_nombre = isset($partes_nombres[1]) ? $partes_nombres[1] : null;

    $partes_apellidos = explode(' ', $apellidos, 2);
    $primer_apellido  = $partes_apellidos[0];
    $segundo_apellido = isset($partes_apellidos[1]) ? $partes_apellidos[1] : null;

    try {
        // Verificar si el correo ya existe
        $stmt_check = $pdo->prepare("SELECT id_usuario_pk FROM usuarios WHERE correo_electronico = ?");
        $stmt_check->execute([$correo]);
        if ($stmt_check->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'El correo electrónico ya se encuentra registrado.']);
            exit();
        }

        // Crear el usuario (Rol 3 = Estudiante siempre en registro público)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $pdo->beginTransaction();
        
        $stmt_ins = $pdo->prepare("
            INSERT INTO usuarios 
            (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, correo_electronico, contrasena_hash, id_rol_fk) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_ins->execute([
            $primer_nombre, 
            $segundo_nombre, 
            $primer_apellido, 
            $segundo_apellido, 
            $correo, 
            $hash, 
            ROL_ESTUDIANTE
        ]);
        
        $id_nuevo = $pdo->lastInsertId();
        
        // Log de registro
        $stmt_log = $pdo->prepare("INSERT INTO log_actividad_usuario (id_usuario_fk, tipo_accion, descripcion_accion, direccion_ip) VALUES (?, 'NUEVO_REGISTRO', 'Estudiante registrado vía web', ?)");
        $stmt_log->execute([$id_nuevo, obtener_ip_cliente()]);
        
        $pdo->commit();

        echo json_encode([
            'estado'   => 'ok',
            'mensaje'  => 'Registro completado exitosamente. Ya puede iniciar sesión.',
            'redirect' => BASE_URL . 'auth/login.php'
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("[AUTH ERROR] Error en registro: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al registrar el usuario.']);
    }
}

// -----------------------------------------------------------------------------
// ACCIÓN NO RECONOCIDA
// -----------------------------------------------------------------------------
else {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no válida.']);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/api/auth.php
 * ============================================================
 * API principal de Autenticación para el Frontend.
 * Maneja 3 acciones ('accion'):
 *   1. login: Verifica CSRF, IPs bloqueadas, Fuerza Bruta. Compara 
 *      password_verify(). Inicia sesión y redirige según ROL.
 *   2. logout: Registra log de salida y destruye la sesión segura.
 *   3. registro: Solo para estudiantes. Verifica CSRF, contraseñas,
 *      crea hash y guarda en BD.
 *
 * Devuelve siempre JSON para ser procesado por AJAX/Fetch.
 *
 * Última actualización: Fase 2 — Autenticación y Seguridad
 * ============================================================
 */
?>
