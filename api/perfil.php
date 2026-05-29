<?php
// /cursoonline/api/perfil.php
// ============================================================
// API para gestionar la actualización del perfil y contraseña
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
require_once '../includes/csrf.php';

iniciar_sesion_segura();

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Sesión no iniciada.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido.']);
    exit();
}

$pdo = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];
$accion = isset($_POST['accion']) ? limpiar_entrada($_POST['accion']) : '';

// --- ACCIÓN: ACTUALIZAR PERFIL ---
if ($accion === 'actualizar_perfil') {
    if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '', 'perfil')) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token CSRF inválido o expirado.']);
        exit();
    }

    $nombres   = isset($_POST['nombres'])   ? limpiar_entrada($_POST['nombres'])   : '';
    $apellidos = isset($_POST['apellidos']) ? limpiar_entrada($_POST['apellidos']) : '';
    $telefono  = isset($_POST['telefono'])  ? limpiar_entrada($_POST['telefono'])  : '';
    $ciudad    = isset($_POST['ciudad'])    ? limpiar_entrada($_POST['ciudad'])    : '';
    $documento = isset($_POST['documento']) ? limpiar_entrada($_POST['documento']) : '';

    if (empty($nombres) || empty($apellidos)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Los nombres y apellidos son campos requeridos.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET primer_nombre = :nombres,
                primer_apellido = :apellidos,
                numero_telefono = :telefono,
                ciudad_residencia = :ciudad,
                numero_documento_identidad = :documento,
                fecha_modificacion = NOW(),
                modificado_por = :modificado_por
            WHERE id_usuario_pk = :id_usuario AND estado_activo = 1
        ");
        
        $stmt->execute([
            ':nombres'        => $nombres,
            ':apellidos'      => $apellidos,
            ':telefono'       => $telefono,
            ':ciudad'         => $ciudad,
            ':documento'      => $documento,
            ':modificado_por' => $id_usuario,
            ':id_usuario'     => $id_usuario
        ]);

        // Actualizar nombres en sesión
        $_SESSION['nombre_completo'] = $nombres . ' ' . $apellidos;

        invalidar_token_csrf($pdo, $_POST['csrf_token'] ?? '');

        echo json_encode(['estado' => 'ok', 'mensaje' => '¡Información de perfil actualizada con éxito!']);

    } catch (PDOException $e) {
        error_log("[PERFIL ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al actualizar los datos en el servidor.']);
    }
}

// --- ACCIÓN: CAMBIAR CONTRASEÑA ---
elseif ($accion === 'cambiar_password') {
    if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '', 'password')) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token CSRF inválido o expirado.']);
        exit();
    }

    $clave_actual = isset($_POST['clave_actual']) ? $_POST['clave_actual'] : '';
    $nueva_clave  = isset($_POST['nueva_clave'])  ? $_POST['nueva_clave']  : '';

    if (empty($clave_actual) || empty($nueva_clave)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Por favor complete todos los campos de contraseña.']);
        exit();
    }

    if (strlen($nueva_clave) < 8) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'La nueva contraseña debe tener mínimo 8 caracteres.']);
        exit();
    }

    try {
        // Obtener la contraseña actual en la BD
        $stmt_hash = $pdo->prepare("SELECT contrasena_hash FROM usuarios WHERE id_usuario_pk = :id AND estado_activo = 1");
        $stmt_hash->execute([':id' => $id_usuario]);
        $hash = $stmt_hash->fetchColumn();

        if (!$hash || !password_verify($clave_actual, $hash)) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'La contraseña actual ingresada es incorrecta.']);
            exit();
        }

        // Hashear nueva contraseña
        $nuevo_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);

        // Actualizar en base de datos
        $stmt_upd = $pdo->prepare("
            UPDATE usuarios 
            SET contrasena_hash = :hash,
                fecha_modificacion = NOW(),
                modificado_por = :modificado_por
            WHERE id_usuario_pk = :id AND estado_activo = 1
        ");
        $stmt_upd->execute([
            ':hash'           => $nuevo_hash,
            ':modificado_por' => $id_usuario,
            ':id'             => $id_usuario
        ]);

        invalidar_token_csrf($pdo, $_POST['csrf_token'] ?? '');

        echo json_encode(['estado' => 'ok', 'mensaje' => '¡Contraseña cambiada con éxito!']);

    } catch (PDOException $e) {
        error_log("[PASS ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al cambiar la contraseña.']);
    }
} 

else {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no soportada.']);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/api/perfil.php
 * ============================================================
 * API para actualizar de forma segura el perfil del usuario logueado.
 * ============================================================
 */
?>
