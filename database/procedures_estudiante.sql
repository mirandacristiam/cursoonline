-- ============================================================
-- Stored Procedures — Gestión de Inscripciones (Estudiante)
-- EduTech Academy
-- Ejecutar en: db_edutechacademy
-- ============================================================

-- ============================================================
-- 1. ALTER TABLE: Agregar columna visible_estudiante
-- ============================================================
ALTER TABLE `inscripciones`
ADD COLUMN `visible_estudiante` TINYINT(1) NOT NULL DEFAULT 1
COMMENT '0=oculta para el estudiante, 1=visible'
AFTER `estado_activo`;

-- ============================================================
-- 2. sp_ocultar_inscripcion_estudiante
-- Descripción: Marca una inscripción cancelada como oculta
-- para el estudiante (visible_estudiante = 0).
-- El administrador aún puede verla.
-- ============================================================
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_ocultar_inscripcion_estudiante`(
    IN p_id_inscripcion INT,
    IN p_id_usuario INT,
    OUT p_mensaje VARCHAR(255),
    OUT p_codigo INT
)
BEGIN
    DECLARE v_estado VARCHAR(20);
    DECLARE v_id_user INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo = 0;
        SET p_mensaje = 'Error al ocultar la inscripción.';
    END;

    SELECT estado_inscripcion, id_usuario_fk INTO v_estado, v_id_user
    FROM inscripciones
    WHERE id_inscripcion_pk = p_id_inscripcion AND estado_activo = 1;

    IF v_id_user IS NULL THEN
        SET p_codigo = 0;
        SET p_mensaje = 'Inscripción no encontrada.';
    ELSEIF v_id_user != p_id_usuario THEN
        SET p_codigo = 0;
        SET p_mensaje = 'La inscripción no pertenece al usuario.';
    ELSEIF v_estado != 'cancelada' THEN
        SET p_codigo = 0;
        SET p_mensaje = 'Solo se pueden ocultar inscripciones canceladas.';
    ELSE
        START TRANSACTION;

        UPDATE inscripciones
        SET visible_estudiante = 0,
            fecha_modificacion = NOW()
        WHERE id_inscripcion_pk = p_id_inscripcion;

        INSERT INTO log_actividad_usuario
            (id_usuario_fk, tipo_accion, descripcion_accion,
             tabla_afectada, id_registro_afectado, direccion_ip)
        VALUES (p_id_usuario, 'OCULTAR_INSCRIPCION',
                CONCAT('Ocultó la inscripción #', p_id_inscripcion, ' de su listado'),
                'inscripciones', p_id_inscripcion, '');

        COMMIT;
        SET p_codigo = 1;
        SET p_mensaje = 'Inscripción oculta de tu listado.';
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- 3. sp_renovar_inscripcion
-- Descripción: Permite al estudiante reinscribirse en un curso
-- que previamente canceló. Crea una nueva transacción pendiente
-- y reactiva/crea la inscripción como suspendida.
-- ============================================================
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_renovar_inscripcion`(
    IN p_id_usuario INT,
    IN p_id_curso INT,
    IN p_id_medio_pago INT,
    IN p_monto_total DECIMAL(12,2),
    IN p_comprobante VARCHAR(255),
    IN p_ip VARCHAR(45),
    OUT p_id_transaccion INT,
    OUT p_mensaje VARCHAR(255),
    OUT p_codigo INT
)
BEGIN
    DECLARE v_estado VARCHAR(20);
    DECLARE v_referencia VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo = 0;
        SET p_mensaje = 'Error al renovar la inscripción.';
    END;

    -- Verificar si hay inscripción activa o completada que lo impida
    SELECT estado_inscripcion INTO v_estado
    FROM inscripciones
    WHERE id_usuario_fk = p_id_usuario
      AND id_curso_fk = p_id_curso
      AND estado_activo = 1
      AND estado_inscripcion IN ('activa','completada')
    LIMIT 1;

    IF v_estado IS NOT NULL THEN
        SET p_codigo = 0;
        SET p_mensaje = 'Ya tienes una inscripción activa o completada en este curso.';
    ELSE
        START TRANSACTION;

        SET v_referencia = CONCAT('EDU-', UPPER(LEFT(MD5(CONCAT(RAND(), NOW())), 8)), '-', DATE_FORMAT(NOW(), '%y%m%d'));

        INSERT INTO transacciones_pago
            (id_usuario_fk, id_curso_fk, id_medio_pago_fk,
             numero_referencia, monto_total, estado_transaccion,
             observaciones, ip_origen_transaccion, estado_activo)
        VALUES
            (p_id_usuario, p_id_curso, p_id_medio_pago,
             v_referencia, p_monto_total, 'pendiente',
             p_comprobante, p_ip, 1);

        SET p_id_transaccion = LAST_INSERT_ID();

        INSERT INTO inscripciones
            (id_usuario_fk, id_curso_fk, fecha_inscripcion,
             monto_pagado, estado_inscripcion, porcentaje_progreso,
             estado_activo, visible_estudiante)
        VALUES
            (p_id_usuario, p_id_curso, NOW(),
             p_monto_total, 'suspendida', 0.00,
             1, 1)
        ON DUPLICATE KEY UPDATE
            monto_pagado        = p_monto_total,
            estado_inscripcion  = 'suspendida',
            porcentaje_progreso = 0.00,
            estado_activo       = 1,
            visible_estudiante  = 1,
            fecha_modificacion  = NOW();

        INSERT INTO log_actividad_usuario
            (id_usuario_fk, tipo_accion, descripcion_accion,
             tabla_afectada, id_registro_afectado, direccion_ip)
        VALUES (p_id_usuario, 'SOLICITAR_INSCRIPCION',
                CONCAT('Renovó inscripción en curso #', p_id_curso, ' — Transacción #', p_id_transaccion),
                'transacciones_pago', p_id_transaccion, p_ip);

        COMMIT;
        SET p_codigo = 1;
        SET p_mensaje = 'Solicitud de inscripción enviada.';
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- 4. sp_cancelar_inscripcion_estudiante
-- Descripción: Cancela una inscripción. Si está suspendida
-- (pre-aprobación), también cancela la transacción asociada.
-- Si está activa/completada (post-aprobación), no reembolsa.
-- ============================================================
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_cancelar_inscripcion_estudiante`(
    IN p_id_inscripcion INT,
    IN p_id_usuario INT,
    OUT p_mensaje VARCHAR(255),
    OUT p_codigo INT
)
BEGIN
    DECLARE v_estado VARCHAR(20);
    DECLARE v_id_user INT;
    DECLARE v_id_curso INT;
    DECLARE v_titulo_curso VARCHAR(255);
    DECLARE v_id_tx INT;
    DECLARE v_accion_label VARCHAR(100);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo = 0;
        SET p_mensaje = 'Error al cancelar la inscripción.';
    END;

    SELECT i.estado_inscripcion, i.id_usuario_fk, i.id_curso_fk,
           c.titulo_curso, t.id_transaccion_pk
    INTO v_estado, v_id_user, v_id_curso,
         v_titulo_curso, v_id_tx
    FROM inscripciones i
    JOIN cursos c ON c.id_curso_pk = i.id_curso_fk
    LEFT JOIN transacciones_pago t ON t.id_curso_fk = i.id_curso_fk
        AND t.id_usuario_fk = i.id_usuario_fk
        AND t.estado_transaccion = 'pendiente'
        AND t.estado_activo = 1
    WHERE i.id_inscripcion_pk = p_id_inscripcion
      AND i.estado_activo = 1
    ORDER BY t.fecha_creacion DESC
    LIMIT 1;

    IF v_id_user IS NULL THEN
        SET p_codigo = 0;
        SET p_mensaje = 'Inscripción no encontrada.';
    ELSEIF v_id_user != p_id_usuario THEN
        SET p_codigo = 0;
        SET p_mensaje = 'La inscripción no pertenece al usuario.';
    ELSEIF v_estado NOT IN ('suspendida', 'activa', 'completada') THEN
        SET p_codigo = 0;
        SET p_mensaje = CONCAT('No se puede cancelar (estado: ', v_estado, ').');
    ELSE
        START TRANSACTION;

        UPDATE inscripciones
        SET estado_inscripcion = 'cancelada',
            fecha_modificacion = NOW()
        WHERE id_inscripcion_pk = p_id_inscripcion;

        IF v_estado = 'suspendida' THEN
            IF v_id_tx IS NOT NULL THEN
                UPDATE transacciones_pago
                SET estado_transaccion = 'cancelada',
                    observaciones = 'Cancelado por el estudiante antes de la aprobación.',
                    fecha_modificacion = NOW()
                WHERE id_transaccion_pk = v_id_tx;
            END IF;
            SET p_mensaje = CONCAT('Solicitud para "', v_titulo_curso, '" cancelada. No hubo cobro.');
        ELSE
            SET p_mensaje = CONCAT('Curso "', v_titulo_curso, '" cancelado. El pago no es reembolsable.');
        END IF;

        -- Notificación al estudiante
        INSERT INTO notificaciones
            (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
             id_usuario_emisor_fk, url_accion, estado_activo)
        VALUES ('Inscripción Cancelada',
                CONCAT('Cancelaste tu inscripción en "', v_titulo_curso, '".'),
                'alerta', p_id_usuario, 'student/mis-cursos.php', 1);
        SET @id_notif = LAST_INSERT_ID();
        IF @id_notif > 0 THEN
            INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
            VALUES (@id_notif, p_id_usuario, 0, 1);
        END IF;

        -- Notificación a admins
        SET v_accion_label = IF(v_estado = 'suspendida', 'canceló su solicitud de inscripción', 'canceló su inscripción');
        INSERT INTO notificaciones
            (titulo_notificacion, mensaje_notificacion, tipo_notificacion,
             id_usuario_emisor_fk, url_accion, estado_activo)
        VALUES ('Estudiante canceló inscripción',
                CONCAT('Estudiante #', p_id_usuario, ' ', v_accion_label, ' en "', v_titulo_curso, '".'),
                'alerta', p_id_usuario, 'admin/pagos/index.php', 1);
        SET @id_notif_adm = LAST_INSERT_ID();
        IF @id_notif_adm > 0 THEN
            INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo)
            SELECT @id_notif_adm, id_usuario_pk, 0, 1
            FROM usuarios
            WHERE id_rol_fk = 1 AND estado_activo = 1;
        END IF;

        INSERT INTO log_actividad_usuario
            (id_usuario_fk, tipo_accion, descripcion_accion,
             tabla_afectada, id_registro_afectado, direccion_ip)
        VALUES (p_id_usuario, 'CANCELAR_INSCRIPCION',
                CONCAT('Canceló inscripción #', p_id_inscripcion, ' en "', v_titulo_curso, '"'),
                'inscripciones', p_id_inscripcion, '');

        COMMIT;
        SET p_codigo = 1;
    END IF;
END$$
DELIMITER ;
