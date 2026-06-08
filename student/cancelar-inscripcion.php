<?php
// /cursoonline/student/cancelar-inscripcion.php
// ============================================================
// Cancelación de Inscripción — EduTech Academy
// Handle: cancel inscription (pre or post approval)
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
    $_SESSION['student_msg_err'] = 'Token de seguridad inválido. Recarga la página.';
    header('Location: mis-cursos.php');
    exit();
}

if (!$id_inscripcion) {
    $_SESSION['student_msg_err'] = 'Inscripción inválida.';
    header('Location: mis-cursos.php');
    exit();
}

// Obtener inscripción y verificar que pertenezca al usuario
$stmt = $pdo->prepare("
    SELECT i.id_inscripcion_pk, i.estado_inscripcion, i.id_curso_fk,
           c.titulo_curso,
           t.id_transaccion_pk, t.estado_transaccion
    FROM inscripciones i
    JOIN cursos c ON c.id_curso_pk = i.id_curso_fk
    LEFT JOIN transacciones_pago t ON t.id_curso_fk = i.id_curso_fk
        AND t.id_usuario_fk = i.id_usuario_fk
        AND t.estado_activo = 1
    WHERE i.id_inscripcion_pk = :id
      AND i.id_usuario_fk = :usr
      AND i.estado_activo = 1
    ORDER BY t.fecha_creacion DESC
    LIMIT 1
");
$stmt->execute([':id' => $id_inscripcion, ':usr' => $id_usuario]);
$inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscripcion) {
    $_SESSION['student_msg_err'] = 'Inscripción no encontrada.';
    header('Location: mis-cursos.php');
    exit();
}

try {
    $pdo->beginTransaction();

    $estado_ins = $inscripcion['estado_inscripcion'];

    if ($estado_ins === 'suspendida') {
        // ── PRE-APROBACIÓN: cancelar todo (inscripción + transacción) sin cobro ──
        $pdo->prepare("
            UPDATE inscripciones
            SET estado_inscripcion = 'cancelada',
                modificado_por     = :usr,
                fecha_modificacion = NOW()
            WHERE id_inscripcion_pk = :id
        ")->execute([':usr' => $id_usuario, ':id' => $id_inscripcion]);

        if (!empty($inscripcion['id_transaccion_pk'])) {
            $pdo->prepare("
                UPDATE transacciones_pago
                SET estado_transaccion = 'cancelada',
                    observaciones      = 'Cancelado por el estudiante antes de la aprobación.',
                    fecha_modificacion = NOW()
                WHERE id_transaccion_pk = :id
            ")->execute([':id' => $inscripcion['id_transaccion_pk']]);
        }

        $_SESSION['student_msg_ok'] = 'Solicitud de inscripción para "' . $inscripcion['titulo_curso'] . '" cancelada. No se realizó ningún cobro.';

    } elseif ($estado_ins === 'activa' || $estado_ins === 'completada') {
        // ── POST-APROBACIÓN: cancelar inscripción, NO se devuelve el pago ──
        $pdo->prepare("
            UPDATE inscripciones
            SET estado_inscripcion = 'cancelada',
                modificado_por     = :usr,
                fecha_modificacion = NOW()
            WHERE id_inscripcion_pk = :id
        ")->execute([':usr' => $id_usuario, ':id' => $id_inscripcion]);

        // IMPORTANTE: la transacción queda como 'aprobada' — sin reembolso

        $_SESSION['student_msg_ok'] = 'Curso "' . $inscripcion['titulo_curso'] . '" cancelado. El pago ya procesado no es reembolsable.';

    } else {
        $_SESSION['student_msg_err'] = 'Esta inscripción no puede cancelarse (estado: ' . $estado_ins . ').';
        $pdo->rollBack();
        header('Location: mis-cursos.php');
        exit();
    }

    // ── Notificación de cancelación para el estudiante ──
    $pdo->prepare("
        INSERT INTO notificaciones (titulo_notificacion, mensaje_notificacion, tipo_notificacion, id_usuario_emisor_fk, url_accion, estado_activo)
        VALUES ('Inscripción Cancelada', :msg, 'alerta', :usr, 'student/mis-cursos.php', 1)
    ")->execute([
        ':msg' => 'Has cancelado tu inscripción en "' . $inscripcion['titulo_curso'] . '".',
        ':usr' => $id_usuario,
    ]);
    $id_notif = (int)$pdo->lastInsertId();
    if ($id_notif > 0) {
        $pdo->prepare("
            INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
            VALUES (:notif, :usr, 0, 1)
        ")->execute([':notif' => $id_notif, ':usr' => $id_usuario]);
    }

    // ── Notificación al administrador ──
    $stmt_admins = $pdo->query("SELECT id_usuario_pk FROM usuarios WHERE id_rol_fk = " . ROL_ADMIN_TOTAL . " AND estado_activo = 1");
    $admins = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($admins)) {
        $accion_label = ($estado_ins === 'suspendida') ? 'canceló su solicitud de inscripción' : 'canceló su inscripción';
        $pdo->prepare("
            INSERT INTO notificaciones (titulo_notificacion, mensaje_notificacion, tipo_notificacion, id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES ('Estudiante canceló inscripción', :msg, 'alerta', :usr, 'admin/pagos/index.php', 1)
        ")->execute([
            ':msg' => 'El estudiante #' . $id_usuario . ' ' . $accion_label . ' en "' . $inscripcion['titulo_curso'] . '".',
            ':usr' => $id_usuario,
        ]);
        $id_notif_admin = (int)$pdo->lastInsertId();
        if ($id_notif_admin > 0) {
            foreach ($admins as $admin_id) {
                $pdo->prepare("
                    INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
                    VALUES (:notif, :usr, 0, 1)
                ")->execute([':notif' => $id_notif_admin, ':usr' => $admin_id]);
            }
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error cancelar inscripcion #' . $id_inscripcion . ': ' . $e->getMessage());
    $_SESSION['student_msg_err'] = 'Error al cancelar la inscripción. Intenta de nuevo.';
}

header('Location: mis-cursos.php');
exit();
