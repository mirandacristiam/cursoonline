<?php
// /cursoonline/api/perfil_estudiante.php
// API exclusiva para el perfil del estudiante

header('Content-Type: application/json; charset=utf-8');

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
require_once '../includes/csrf.php';

try {
iniciar_sesion_segura();

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Sesión no iniciada.']);
    exit();
}
if ((int)$_SESSION['id_rol'] !== ROL_ESTUDIANTE) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso no autorizado.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido.']);
    exit();
}

$pdo = obtenerConexion();
$id_usuario = (int)$_SESSION['id_usuario'];
$accion = isset($_POST['accion']) ? limpiar_entrada($_POST['accion']) : '';

function sp_exec($pdo, $sql, $params = []) {
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $r = $stmt->execute($params);
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    return $r;
}
function sp_scalar($pdo, $sql, $params = []) {
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $val = $stmt->fetchColumn();
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    return $val;
}

// ── ACTUALIZAR PERFIL ─────────────────────────────────
if ($accion === 'actualizar_perfil') {
    if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token CSRF inválido o expirado.']);
        exit();
    }

    $primer_nombre    = isset($_POST['primer_nombre'])    ? limpiar_entrada($_POST['primer_nombre'])    : '';
    $segundo_nombre   = isset($_POST['segundo_nombre'])   ? limpiar_entrada($_POST['segundo_nombre'])   : '';
    $primer_apellido  = isset($_POST['primer_apellido'])  ? limpiar_entrada($_POST['primer_apellido'])  : '';
    $segundo_apellido = isset($_POST['segundo_apellido']) ? limpiar_entrada($_POST['segundo_apellido']) : '';
    $telefono         = isset($_POST['telefono'])         ? limpiar_entrada($_POST['telefono'])         : '';
    $tipo_documento   = isset($_POST['tipo_documento'])   ? limpiar_entrada($_POST['tipo_documento'])   : '';
    $numero_documento = isset($_POST['numero_documento']) ? limpiar_entrada($_POST['numero_documento']) : '';
    $fecha_nacimiento = isset($_POST['fecha_nacimiento']) ? limpiar_entrada($_POST['fecha_nacimiento']) : '';
    $ciudad           = isset($_POST['ciudad'])           ? limpiar_entrada($_POST['ciudad'])           : '';
    $departamento     = isset($_POST['departamento'])     ? limpiar_entrada($_POST['departamento'])     : '';
    $pais             = isset($_POST['pais'])             ? limpiar_entrada($_POST['pais'])             : '';

    if (empty($primer_nombre) || empty($primer_apellido)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El primer nombre y primer apellido son campos requeridos.']);
        exit();
    }

    try {
        sp_exec($pdo, 'CALL sp_estudiante_actualizar_perfil(
            :id, :pn, :sn, :pa, :sa, :tel, :td, :nd, :fn, :ciu, :dep, :pai
        )', [
            ':id'  => $id_usuario,
            ':pn'  => $primer_nombre,
            ':sn'  => $segundo_nombre ?: null,
            ':pa'  => $primer_apellido,
            ':sa'  => $segundo_apellido ?: null,
            ':tel' => $telefono ?: null,
            ':td'  => $tipo_documento ?: null,
            ':nd'  => $numero_documento ?: null,
            ':fn'  => $fecha_nacimiento ?: null,
            ':ciu' => $ciudad ?: null,
            ':dep' => $departamento ?: null,
            ':pai' => $pais ?: null,
        ]);
        $_SESSION['nombre'] = $primer_nombre . ' ' . $primer_apellido;
        echo json_encode(['estado' => 'ok', 'mensaje' => 'Información de perfil actualizada con éxito.']);
    } catch (PDOException $e) {
        error_log("[EST_PERFIL ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al actualizar los datos.']);
    }
}

// ── SUBIR FOTO ───────────────────────────────────────
elseif ($accion === 'subir_foto') {
    if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token CSRF inválido o expirado.']);
        exit();
    }

    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $codigo = $_FILES['foto']['error'] ?? -1;
        $mensajes = [
            UPLOAD_ERR_INI_SIZE  => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario.',
            UPLOAD_ERR_PARTIAL   => 'El archivo se subió parcialmente.',
            UPLOAD_ERR_NO_FILE   => 'No se seleccionó ningún archivo.',
        ];
        echo json_encode(['estado' => 'error', 'mensaje' => $mensajes[$codigo] ?? 'Error al subir el archivo.']);
        exit();
    }

    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $tipos_permitidos)) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF o WebP.']);
        exit();
    }

    if ($archivo['size'] > MAX_UPLOAD_SIZE_MB * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'El archivo excede el tamaño máximo de ' . MAX_UPLOAD_SIZE_MB . ' MB.']);
        exit();
    }

    $nombre_unico = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
    $ruta_destino = STUDENT_FOTO_PATH . $nombre_unico;

    if (!is_dir(STUDENT_FOTO_PATH)) {
        mkdir(STUDENT_FOTO_PATH, 0755, true);
    }

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error al guardar la imagen en el servidor.']);
        exit();
    }

    try {
        $foto_anterior = sp_scalar($pdo, 'CALL sp_estudiante_obtener_foto(:id)', [':id' => $id_usuario]);

        if ($foto_anterior && file_exists(STUDENT_FOTO_PATH . basename($foto_anterior))) {
            unlink(STUDENT_FOTO_PATH . basename($foto_anterior));
        }

        $ruta_relativa = STUDENT_FOTO_URL . $nombre_unico;

        sp_exec($pdo, 'CALL sp_estudiante_subir_foto(:id, :foto)', [
            ':id'   => $id_usuario,
            ':foto' => $ruta_relativa,
        ]);

        echo json_encode([
            'estado' => 'ok',
            'mensaje' => 'Foto de perfil actualizada con éxito.',
            'foto_url' => $ruta_relativa,
        ]);
    } catch (PDOException $e) {
        error_log("[EST_FOTO ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al guardar la foto.']);
    }
}

// ── ELIMINAR FOTO ────────────────────────────────────
elseif ($accion === 'eliminar_foto') {
    if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token CSRF inválido o expirado.']);
        exit();
    }

    try {
        $foto_anterior = sp_scalar($pdo, 'CALL sp_estudiante_obtener_foto(:id)', [':id' => $id_usuario]);

        if ($foto_anterior && file_exists(STUDENT_FOTO_PATH . basename($foto_anterior))) {
            unlink(STUDENT_FOTO_PATH . basename($foto_anterior));
        }

        sp_exec($pdo, 'CALL sp_estudiante_eliminar_foto(:id)', [':id' => $id_usuario]);

        echo json_encode(['estado' => 'ok', 'mensaje' => 'Foto de perfil eliminada.']);
    } catch (PDOException $e) {
        error_log("[EST_FOTO DELETE ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al eliminar la foto.']);
    }
}

// ── CAMBIAR CONTRASEÑA ───────────────────────────────
elseif ($accion === 'cambiar_password') {
    if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Token CSRF inválido o expirado.']);
        exit();
    }

    $clave_actual = $_POST['clave_actual'] ?? '';
    $nueva_clave  = $_POST['nueva_clave'] ?? '';

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
        $hash = sp_scalar($pdo, 'CALL sp_estudiante_obtener_hash(:id)', [':id' => $id_usuario]);

        if (!$hash || !password_verify($clave_actual, $hash)) {
            http_response_code(400);
            echo json_encode(['estado' => 'error', 'mensaje' => 'La contraseña actual ingresada es incorrecta.']);
            exit();
        }

        $nuevo_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);

        sp_exec($pdo, 'CALL sp_estudiante_cambiar_password(:id, :hash)', [
            ':id'   => $id_usuario,
            ':hash' => $nuevo_hash,
        ]);

        echo json_encode(['estado' => 'ok', 'mensaje' => 'Contraseña cambiada con éxito.']);
    } catch (PDOException $e) {
        error_log("[EST_PASS ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno al cambiar la contraseña.']);
    }
}

else {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no soportada.']);
}
} catch (Throwable $e) {
    error_log("[EST_PERFIL FATAL] " . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno del servidor: ' . $e->getMessage()]);
}
