<?php
// /cursoonline/api/calificar.php
// ============================================================
// API para que el Profesor cargue y guarde calificaciones
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
require_once '../includes/csrf.php';

iniciar_sesion_segura();

// Verificar que sea un profesor autorizado
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] !== ROL_PROFESOR) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso denegado. Exclusivo para profesores.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método de petición no permitido.']);
    exit();
}

$pdo = obtenerConexion();
$id_profesor = $_SESSION['id_usuario'];

// --- VALIDACIÓN DE CSRF ---
if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '', 'calificar')) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Petición no autorizada (CSRF expirado o inválido).']);
    exit();
}

// --- DATOS DE NOTA ---
$id_inscripcion = isset($_POST['id_inscripcion']) ? (int)$_POST['id_inscripcion'] : 0;
$id_actividad   = isset($_POST['id_actividad'])   ? (int)$_POST['id_actividad']   : 0;
$nota           = isset($_POST['nota'])            ? (float)$_POST['nota']         : -1.0;
$observaciones  = isset($_POST['observaciones'])   ? limpiar_entrada($_POST['observaciones']) : '';

if ($id_inscripcion <= 0 || $id_actividad <= 0 || $nota < 0.0 || $nota > 100.0) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Por favor ingrese una calificación válida entre 0 y 100 y seleccione los campos correspondientes.']);
    exit();
}

try {
    // Verificar que el profesor sea el asignado a este curso/inscripción
    $stmt_check = $pdo->prepare("
        SELECT i.id_inscripcion_pk 
        FROM inscripciones i
        JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
        WHERE i.id_inscripcion_pk = :id_ins 
          AND c.id_profesor_fk = :id_prof 
          AND i.estado_activo = 1
    ");
    $stmt_check->execute([
        ':id_ins'  => $id_inscripcion,
        ':id_prof' => $id_profesor
    ]);

    if ($stmt_check->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['estado' => 'error', 'mensaje' => 'No tienes autorización para calificar en este curso.']);
        exit();
    }

    // Insertar o actualizar (UPSERT) la nota
    $stmt_upsert = $pdo->prepare("
        INSERT INTO calificaciones 
            (id_inscripcion_fk, id_actividad_fk, id_profesor_fk, nota_obtenida, observaciones_profesor, fecha_calificacion, estado_activo)
        VALUES 
            (:id_ins, :id_act, :id_prof, :nota, :obs, NOW(), 1)
        ON DUPLICATE KEY UPDATE 
            nota_obtenida = :nota_upd,
            observaciones_profesor = :obs_upd,
            fecha_calificacion = NOW(),
            id_profesor_fk = :id_prof_upd
    ");

    $stmt_upsert->execute([
        ':id_ins'      => $id_inscripcion,
        ':id_act'      => $id_actividad,
        ':id_prof'     => $id_profesor,
        ':nota'        => $nota,
        ':obs'         => $observaciones,
        ':nota_upd'    => $nota,
        ':obs_upd'     => $observaciones,
        ':id_prof_upd' => $id_profesor
    ]);

    // Opcional: Generar notificación para el estudiante indicándole que ha sido calificado
    // Obtener id del estudiante
    $stmt_est = $pdo->prepare("SELECT id_usuario_fk FROM inscripciones WHERE id_inscripcion_pk = :id_ins");
    $stmt_est->execute([':id_ins' => $id_inscripcion]);
    $id_estudiante = $stmt_est->fetchColumn();

    if ($id_estudiante) {
        // Obtener nombre de la actividad
        $stmt_act_name = $pdo->prepare("SELECT nombre_actividad FROM actividades_calificacion WHERE id_actividad_pk = :id_act");
        $stmt_act_name->execute([':id_act' => $id_actividad]);
        $nombre_actividad = $stmt_act_name->fetchColumn();

        $stmt_notif = $pdo->prepare("
            INSERT INTO notificaciones 
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion, url_accion, estado_activo) 
            VALUES 
                ('Nueva calificación cargada', :msg_notif, 'calificacion', 'student/mis-notas.php', 1)
        ");
        $stmt_notif->execute([
            ':msg_notif' => "Tu profesor ha cargado una nota de " . number_format($nota, 1) . " para la actividad: " . $nombre_actividad
        ]);
        $id_notificacion = $pdo->lastInsertId();

        $stmt_notif_user = $pdo->prepare("
            INSERT INTO notificaciones_usuario 
                (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo) 
            VALUES 
                (:id_notif, :id_estudiante, 0, 1)
        ");
        $stmt_notif_user->execute([
            ':id_notif'      => $id_notificacion,
            ':id_estudiante' => $id_estudiante
        ]);
    }

    invalidar_token_csrf($pdo, $_POST['csrf_token'] ?? '');

    echo json_encode(['estado' => 'ok', 'mensaje' => '¡Calificación cargada y guardada con éxito! El estudiante recibirá una notificación.']);

} catch (PDOException $e) {
    error_log("[GRADING ERROR] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error de servidor al guardar la calificación.']);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/api/calificar.php
 * ============================================================
 * API para que el docente pueda subir calificaciones de forma
 * segura, auditada y con notificaciones estudiantiles inmediatas.
 * ============================================================
 */
?>
