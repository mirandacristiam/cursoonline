<?php
// /cursoonline/admin/pagos/procesar.php
// ============================================================
// Procesador de Aprobación/Rechazo de Pagos — Admin
// EduTech Academy — SQL DIRECTO, no requiere SPs instalados
// Solo acepta POST. Redirige siempre a index.php
// ============================================================

require_once __DIR__ . '/../includes/header.php';

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// ── Validar CSRF ──────────────────────────────────────────────
$token = $_POST['csrf_token'] ?? '';
if (!validar_token_csrf($pdo, $token)) {
    $_SESSION['admin_msg_err'] = 'Token de seguridad inválido. Intenta de nuevo.';
    header('Location: index.php');
    exit();
}

// ── Datos del formulario ──────────────────────────────────────
$id_transaccion = (int)($_POST['id_transaccion'] ?? 0);
$accion         = trim($_POST['accion'] ?? '');
$observaciones  = trim($_POST['observaciones'] ?? '');

if (!$id_transaccion || !in_array($accion, ['aprobar', 'rechazar'])) {
    $_SESSION['admin_msg_err'] = 'Solicitud inválida. Verifica los datos e intenta de nuevo.';
    header('Location: index.php');
    exit();
}

// ── Obtener la transacción ────────────────────────────────────
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

if ($tx['estado_transaccion'] !== 'pendiente') {
    $_SESSION['admin_msg_err'] = 'Esta transacción ya fue procesada (estado actual: ' . $tx['estado_transaccion'] . ').';
    header('Location: index.php');
    exit();
}

// ── Ejecutar la acción con SQL directo ────────────────────────
try {
    $pdo->beginTransaction();

    if ($accion === 'aprobar') {

        // 1. Marcar transacción como aprobada
        $pdo->prepare("
            UPDATE transacciones_pago
            SET estado_transaccion = 'aprobada',
                observaciones      = :obs,
                modificado_por     = :admin
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
                modificado_por     = :admin
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
            SET numero_estudiantes = numero_estudiantes + 1
            WHERE id_curso_pk = :id
        ")->execute([':id' => $tx['id_curso_fk']]);

        // 4. Notificación para el estudiante
        $pdo->prepare("
            INSERT INTO notificaciones
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
                 id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES
                ('✅ Inscripción Aprobada',
                 :msg, 'exito', :admin, 'student/mis-cursos.php', 1)
        ")->execute([
            ':msg'   => '¡Tu pago para el curso "' . $tx['titulo_curso'] . '" fue aprobado! Ya puedes acceder a tu contenido.',
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

        $pdo->commit();
        $_SESSION['admin_msg_ok'] = '✅ Inscripción #' . $id_transaccion . ' aprobada. El estudiante ya tiene acceso al curso "' . $tx['titulo_curso'] . '".';
        header('Location: index.php?estado=aprobada');

    } else { // rechazar

        // 1. Marcar transacción como rechazada
        $pdo->prepare("
            UPDATE transacciones_pago
            SET estado_transaccion = 'rechazada',
                observaciones      = :obs,
                modificado_por     = :admin
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
                modificado_por     = :admin
            WHERE id_usuario_fk       = :usr
              AND id_curso_fk         = :cur
              AND estado_inscripcion  = 'suspendida'
        ")->execute([
            ':admin' => $id_usuario,
            ':usr'   => $tx['id_usuario_fk'],
            ':cur'   => $tx['id_curso_fk'],
        ]);

        // 3. Notificación de rechazo al estudiante
        $motivo_msg = $observaciones ? 'Motivo: ' . $observaciones . '.' : 'Contáctanos para más información.';
        $pdo->prepare("
            INSERT INTO notificaciones
                (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
                 id_usuario_emisor_fk, url_accion, estado_activo)
            VALUES
                ('❌ Solicitud Rechazada', :msg, 'alerta', :admin, 'student/notificaciones.php', 1)
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

        $pdo->commit();
        $_SESSION['admin_msg_ok'] = '🚫 Solicitud #' . $id_transaccion . ' rechazada. El estudiante ha sido notificado.';
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
