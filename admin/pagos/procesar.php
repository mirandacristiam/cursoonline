<?php
// /cursoonline/admin/pagos/procesar.php
// ============================================================
// Procesador de Aprobación/Rechazo de Pagos — Admin
// EduTech Academy — SQL DIRECTO, no requiere SPs instalados
// Solo acepta POST. Redirige siempre a index.php
// ============================================================
// IMPORTANTE: Este archivo NO debe incluir el header HTML del admin
// porque necesita enviar headers HTTP de redirección sin salida HTML previa.
// ============================================================

// ── Inicialización directa sin header HTML ─────────────────────
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/csrf.php';

iniciar_sesion_segura();
requerir_rol(ROL_ADMIN_TOTAL);

$pdo        = obtenerConexion();
$id_usuario = (int)$_SESSION['id_usuario'];

// ── Solo acepta POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// ── Validar CSRF ───────────────────────────────────────────────
$token = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($pdo, $token)) {
    $_SESSION['admin_msg_err'] = 'Token de seguridad inválido. Intenta de nuevo.';
    header('Location: index.php');
    exit();
}

// ── Datos del formulario ───────────────────────────────────────
$id_transaccion = (int)($_POST['id_transaccion'] ?? 0);
$accion         = trim($_POST['accion'] ?? '');
$observaciones  = trim($_POST['observaciones'] ?? '');

if (!$id_transaccion || !in_array($accion, ['aprobar', 'rechazar', 'reaprobar', 'cancelar_def'])) {
    $_SESSION['admin_msg_err'] = 'Solicitud inválida. Verifica los datos e intenta de nuevo.';
    header('Location: index.php');
    exit();
}

// ── Obtener la transacción ─────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT t.id_transaccion_pk, t.id_usuario_fk, t.id_curso_fk,
               t.monto_total, t.estado_transaccion, c.titulo_curso
        FROM transacciones_pago t
        JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
        WHERE t.id_transaccion_pk = :id AND t.estado_activo = 1
    ");
    $stmt->execute([':id' => $id_transaccion]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Error al leer transacción #' . $id_transaccion . ': ' . $e->getMessage());
    $_SESSION['admin_msg_err'] = 'Error al leer la transacción. Por favor intenta de nuevo.';
    header('Location: index.php');
    exit();
}

if (!$tx) {
    $_SESSION['admin_msg_err'] = 'Transacción #' . $id_transaccion . ' no encontrada.';
    header('Location: index.php');
    exit();
}

$estados_permitidos = ($accion === 'reaprobar' || $accion === 'cancelar_def')
    ? ['rechazada']
    : ['pendiente'];

if (!in_array($tx['estado_transaccion'], $estados_permitidos)) {
    $_SESSION['admin_msg_err'] = 'Esta transacción no puede procesarse con la acción "' . $accion . '" (estado actual: ' . $tx['estado_transaccion'] . ').';
    header('Location: index.php');
    exit();
}

// ── Ejecutar la acción con SQL directo ─────────────────────────
try {
    $pdo->beginTransaction();

    if ($accion === 'aprobar') {

        // 1. Marcar transacción como aprobada
        $pdo->prepare("
            UPDATE transacciones_pago
            SET estado_transaccion = 'aprobada',
                observaciones      = :obs,
                modificado_por     = :admin,
                fecha_modificacion  = NOW()
            WHERE id_transaccion_pk = :id
        ")->execute([
            ':obs'   => $observaciones ?: 'Aprobado por administrador.',
            ':admin' => $id_usuario,
            ':id'    => $id_transaccion,
        ]);

        // 2. Activar inscripción del estudiante
        $pdo->prepare("
            UPDATE inscripciones
            SET estado_inscripcion = 'activa',
                monto_pagado       = :monto,
                modificado_por     = :admin,
                fecha_modificacion = NOW()
            WHERE id_usuario_fk = :usr
              AND id_curso_fk   = :cur
        ")->execute([
            ':monto' => $tx['monto_total'],
            ':admin' => $id_usuario,
            ':usr'   => $tx['id_usuario_fk'],
            ':cur'   => $tx['id_curso_fk'],
        ]);

        // 3. Incrementar contador de estudiantes en el curso
        $pdo->prepare("
            UPDATE cursos
            SET numero_estudiantes = numero_estudiantes + 1,
                modificado_por     = :admin
            WHERE id_curso_pk = :id
        ")->execute([
            ':admin' => $id_usuario,
            ':id'    => $tx['id_curso_fk'],
        ]);

        // 4. Notificación para el estudiante
        $pdo->prepare("
            INSERT INTO notificaciones
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
                 id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES
                ('Inscripcion Aprobada',
                 :msg, 'exito', :admin, 'student/mis-cursos.php', 1)
        ")->execute([
            ':msg'   => 'Tu pago para el curso "' . $tx['titulo_curso'] . '" fue aprobado. Ya puedes acceder a tu contenido.',
            ':admin' => $id_usuario,
        ]);
        $id_notif = (int)$pdo->lastInsertId();

        // 5. Asignar notificación al estudiante
        if ($id_notif > 0) {
            $pdo->prepare("
                INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
                VALUES (:notif, :usr, 0, 1)
            ")->execute([':notif' => $id_notif, ':usr' => $tx['id_usuario_fk']]);
        }

        // 6. Registrar en log de auditoría
        $pdo->prepare("
            INSERT INTO log_actividad_usuario
                (id_usuario_fk, tipo_accion, descripcion_accion, tabla_afectada, id_registro_afectado, direccion_ip)
            VALUES (:admin, 'APROBAR_PAGO', :desc, 'transacciones_pago', :tx_id, :ip)
        ")->execute([
            ':admin' => $id_usuario,
            ':desc'  => 'Pago aprobado para el curso "' . $tx['titulo_curso'] . '" (Transacción #' . $id_transaccion . ')',
            ':tx_id' => $id_transaccion,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        $pdo->commit();
        $_SESSION['admin_msg_ok'] = 'Inscripcion #' . $id_transaccion . ' aprobada. El estudiante ya tiene acceso al curso "' . $tx['titulo_curso'] . '".';
        header('Location: index.php?estado=aprobada');

    } elseif ($accion === 'reaprobar') {

        // Re-aprobar una transacción previamente rechazada
        $pdo->prepare("
            UPDATE transacciones_pago
            SET estado_transaccion = 'aprobada',
                observaciones      = :obs,
                modificado_por     = :admin,
                fecha_modificacion  = NOW()
            WHERE id_transaccion_pk = :id
        ")->execute([
            ':obs'   => $observaciones ?: 'Re-aprobado por administrador.',
            ':admin' => $id_usuario,
            ':id'    => $id_transaccion,
        ]);

        // Reactivar inscripción
        $pdo->prepare("
            UPDATE inscripciones
            SET estado_inscripcion = 'activa',
                monto_pagado       = :monto,
                modificado_por     = :admin,
                fecha_modificacion = NOW()
            WHERE id_usuario_fk = :usr
              AND id_curso_fk   = :cur
        ")->execute([
            ':monto' => $tx['monto_total'],
            ':admin' => $id_usuario,
            ':usr'   => $tx['id_usuario_fk'],
            ':cur'   => $tx['id_curso_fk'],
        ]);

        // Incrementar contador de estudiantes si no contaba
        $pdo->prepare("
            UPDATE cursos
            SET numero_estudiantes = numero_estudiantes + 1,
                modificado_por     = :admin
            WHERE id_curso_pk = :id
        ")->execute([
            ':admin' => $id_usuario,
            ':id'    => $tx['id_curso_fk'],
        ]);

        // Notificación al estudiante
        $pdo->prepare("
            INSERT INTO notificaciones
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
                 id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES
                ('Inscripcion Re-Aprobada',
                 :msg, 'exito', :admin, 'student/mis-cursos.php', 1)
        ")->execute([
            ':msg'   => 'Tu solicitud para "' . $tx['titulo_curso'] . '" ha sido aprobada. Ya puedes acceder al contenido.',
            ':admin' => $id_usuario,
        ]);
        $id_notif = (int)$pdo->lastInsertId();
        if ($id_notif > 0) {
            $pdo->prepare("
                INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
                VALUES (:notif, :usr, 0, 1)
            ")->execute([':notif' => $id_notif, ':usr' => $tx['id_usuario_fk']]);
        }

        // Log
        $pdo->prepare("
            INSERT INTO log_actividad_usuario
                (id_usuario_fk, tipo_accion, descripcion_accion, tabla_afectada, id_registro_afectado, direccion_ip)
            VALUES (:admin, 'REAPROBAR_PAGO', :desc, 'transacciones_pago', :tx_id, :ip)
        ")->execute([
            ':admin' => $id_usuario,
            ':desc'  => 'Pago re-aprobado para el curso "' . $tx['titulo_curso'] . '" (Transacción #' . $id_transaccion . ')',
            ':tx_id' => $id_transaccion,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        $pdo->commit();
        $_SESSION['admin_msg_ok'] = 'Transacción #' . $id_transaccion . ' re-aprobada. El estudiante recuperó el acceso a "' . $tx['titulo_curso'] . '".';
        header('Location: index.php?estado=aprobada');

    } elseif ($accion === 'cancelar_def') {

        // Cancelar definitivamente una transacción rechazada
        $pdo->prepare("
            UPDATE transacciones_pago
            SET estado_transaccion = 'cancelada',
                observaciones      = :obs,
                modificado_por     = :admin,
                fecha_modificacion = NOW()
            WHERE id_transaccion_pk = :id
        ")->execute([
            ':obs'   => $observaciones ?: 'Cancelado definitivamente por administrador.',
            ':admin' => $id_usuario,
            ':id'    => $id_transaccion,
        ]);

        // Asegurar que la inscripción también quede cancelada
        $pdo->prepare("
            UPDATE inscripciones
            SET estado_inscripcion = 'cancelada',
                modificado_por     = :admin,
                fecha_modificacion = NOW()
            WHERE id_usuario_fk = :usr
              AND id_curso_fk   = :cur
        ")->execute([
            ':admin' => $id_usuario,
            ':usr'   => $tx['id_usuario_fk'],
            ':cur'   => $tx['id_curso_fk'],
        ]);

        // Notificación al estudiante
        $pdo->prepare("
            INSERT INTO notificaciones
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
                 id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES
                ('Solicitud Cancelada',
                 :msg, 'alerta', :admin, 'student/notificaciones.php', 1)
        ")->execute([
            ':msg'   => 'Tu solicitud para "' . $tx['titulo_curso'] . '" ha sido cancelada definitivamente.',
            ':admin' => $id_usuario,
        ]);
        $id_notif = (int)$pdo->lastInsertId();
        if ($id_notif > 0) {
            $pdo->prepare("
                INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
                VALUES (:notif, :usr, 0, 1)
            ")->execute([':notif' => $id_notif, ':usr' => $tx['id_usuario_fk']]);
        }

        // Log
        $pdo->prepare("
            INSERT INTO log_actividad_usuario
                (id_usuario_fk, tipo_accion, descripcion_accion, tabla_afectada, id_registro_afectado, direccion_ip)
            VALUES (:admin, 'CANCELAR_PAGO', :desc, 'transacciones_pago', :tx_id, :ip)
        ")->execute([
            ':admin' => $id_usuario,
            ':desc'  => 'Transacción cancelada definitivamente para el curso "' . $tx['titulo_curso'] . '" (#' . $id_transaccion . ')',
            ':tx_id' => $id_transaccion,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        $pdo->commit();
        $_SESSION['admin_msg_ok'] = 'Transacción #' . $id_transaccion . ' cancelada definitivamente.';
        header('Location: index.php?estado=cancelada');

    } else { // rechazar

        // 1. Marcar transacción como rechazada
        $pdo->prepare("
            UPDATE transacciones_pago
            SET estado_transaccion = 'rechazada',
                observaciones      = :obs,
                modificado_por     = :admin,
                fecha_modificacion  = NOW()
            WHERE id_transaccion_pk = :id
        ")->execute([
            ':obs'   => $observaciones ?: 'Rechazado por administrador.',
            ':admin' => $id_usuario,
            ':id'    => $id_transaccion,
        ]);

        // 2. Cancelar inscripción suspendida
        $pdo->prepare("
            UPDATE inscripciones
            SET estado_inscripcion = 'cancelada',
                modificado_por     = :admin,
                fecha_modificacion = NOW()
            WHERE id_usuario_fk       = :usr
              AND id_curso_fk         = :cur
              AND estado_inscripcion  = 'suspendida'
        ")->execute([
            ':admin' => $id_usuario,
            ':usr'   => $tx['id_usuario_fk'],
            ':cur'   => $tx['id_curso_fk'],
        ]);

        // 3. Notificación de rechazo al estudiante
        $motivo_msg = $observaciones ? 'Motivo: ' . $observaciones . '.' : 'Contactanos para mas informacion.';
        $pdo->prepare("
            INSERT INTO notificaciones
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
                 id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES
                ('Solicitud Rechazada', :msg, 'alerta', :admin, 'student/notificaciones.php', 1)
        ")->execute([
            ':msg'   => 'Tu solicitud para "' . $tx['titulo_curso'] . '" fue rechazada. ' . $motivo_msg,
            ':admin' => $id_usuario,
        ]);
        $id_notif = (int)$pdo->lastInsertId();

        if ($id_notif > 0) {
            $pdo->prepare("
                INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
                VALUES (:notif, :usr, 0, 1)
            ")->execute([':notif' => $id_notif, ':usr' => $tx['id_usuario_fk']]);
        }

        // 4. Registrar en log de auditoría
        $pdo->prepare("
            INSERT INTO log_actividad_usuario
                (id_usuario_fk, tipo_accion, descripcion_accion, tabla_afectada, id_registro_afectado, direccion_ip)
            VALUES (:admin, 'RECHAZAR_PAGO', :desc, 'transacciones_pago', :tx_id, :ip)
        ")->execute([
            ':admin' => $id_usuario,
            ':desc'  => 'Pago rechazado para el curso "' . $tx['titulo_curso'] . '" (Transacción #' . $id_transaccion . ')',
            ':tx_id' => $id_transaccion,
            ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        $pdo->commit();
        $_SESSION['admin_msg_ok'] = 'Solicitud #' . $id_transaccion . ' rechazada. El estudiante ha sido notificado.';
        header('Location: index.php?estado=rechazada');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error admin procesar pago TX#' . $id_transaccion . ': ' . $e->getMessage());
    $_SESSION['admin_msg_err'] = 'Error interno al procesar el pago. Por favor intenta de nuevo.';
    header('Location: index.php');
}

exit();
?>
