<?php
// /cursoonline/student/ocultar-inscripcion.php
// ============================================================
// Ocultar inscripción cancelada del listado del estudiante
// ============================================================

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

iniciar_sesion_segura();
requerir_rol(ROL_ESTUDIANTE);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mis-cursos.php');
    exit();
}

$pdo        = obtenerConexion();
$id_usuario = (int)$_SESSION['id_usuario'];

$token          = $_POST['csrf_token'] ?? '';
$id_inscripcion = (int)($_POST['id_inscripcion'] ?? 0);

if (!validar_token_csrf($pdo, $token)) {
    $_SESSION['student_msg_err'] = 'Token de seguridad inválido.';
    header('Location: mis-cursos.php');
    exit();
}

if (!$id_inscripcion) {
    $_SESSION['student_msg_err'] = 'Inscripción inválida.';
    header('Location: mis-cursos.php');
    exit();
}

try {
    // Intentar usar SP; si no existe, usar SQL directo
    $stmt_chk = $pdo->query("SELECT 1 FROM information_schema.ROUTINES WHERE ROUTINE_NAME = 'sp_ocultar_inscripcion_estudiante' AND ROUTINE_SCHEMA = DATABASE()");
    $sp_exists = (bool)$stmt_chk->fetchColumn();

    if ($sp_exists) {
        $stmt = $pdo->prepare("CALL sp_ocultar_inscripcion_estudiante(:id_ins, :id_usr, @msg, @cod)");
        $stmt->execute([':id_ins' => $id_inscripcion, ':id_usr' => $id_usuario]);
        $result = $pdo->query("SELECT @msg AS mensaje, @cod AS codigo")->fetch(PDO::FETCH_ASSOC);

        if ($result['codigo']) {
            $_SESSION['student_msg_ok'] = $result['mensaje'];
        } else {
            $_SESSION['student_msg_err'] = $result['mensaje'];
        }
    } else {
        // SQL directo como fallback
        $stmt = $pdo->prepare("
            SELECT estado_inscripcion, id_usuario_fk
            FROM inscripciones
            WHERE id_inscripcion_pk = :id AND estado_activo = 1
        ");
        $stmt->execute([':id' => $id_inscripcion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $_SESSION['student_msg_err'] = 'Inscripción no encontrada.';
        } elseif ((int)$row['id_usuario_fk'] !== $id_usuario) {
            $_SESSION['student_msg_err'] = 'No tienes permiso para ocultar esta inscripción.';
        } elseif ($row['estado_inscripcion'] !== 'cancelada') {
            $_SESSION['student_msg_err'] = 'Solo se pueden ocultar inscripciones canceladas.';
        } else {
            $pdo->prepare("
                UPDATE inscripciones
                SET visible_estudiante = 0, fecha_modificacion = NOW()
                WHERE id_inscripcion_pk = :id
            ")->execute([':id' => $id_inscripcion]);
            $_SESSION['student_msg_ok'] = 'Inscripción oculta de tu listado.';
        }
    }
} catch (Exception $e) {
    error_log('Error ocultar inscripcion: ' . $e->getMessage());
    $_SESSION['student_msg_err'] = 'Error al ocultar la inscripción.';
}

header('Location: mis-cursos.php');
exit();
