-- /cursoonline/database/procedures_pagos.sql
-- ============================================================
-- Stored Procedures para el Flujo de Pago y Aprobación
-- EduTech Academy — Gestión de Transacciones por Administrador
-- ============================================================
-- INSTRUCCIONES: Ejecutar este archivo completo en MySQL/phpMyAdmin
-- ============================================================

USE `db_edutechacademy`;

-- ============================================================
-- 1. sp_solicitar_inscripcion
-- El estudiante solicita la inscripción al curso.
-- Se crea la transacción en estado 'pendiente' y la
-- inscripción en estado 'suspendida' (hasta que el admin apruebe).
-- ============================================================
DROP PROCEDURE IF EXISTS sp_solicitar_inscripcion;

DELIMITER //

CREATE PROCEDURE sp_solicitar_inscripcion(
    IN  p_id_usuario    INT UNSIGNED,
    IN  p_id_curso      INT UNSIGNED,
    IN  p_monto         DECIMAL(12,2),
    IN  p_id_medio_pago INT UNSIGNED,
    IN  p_referencia    VARCHAR(100),
    IN  p_comprobante   VARCHAR(255),
    OUT p_resultado     VARCHAR(200)
)
BEGIN
    DECLARE v_ya_inscrito      INT DEFAULT 0;
    DECLARE v_ya_pendiente     INT DEFAULT 0;
    DECLARE v_id_transaccion   INT UNSIGNED;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR: Fallo interno en la base de datos. Intente de nuevo.';
    END;

    -- Verificar si ya tiene inscripción activa o completada
    SELECT COUNT(*) INTO v_ya_inscrito
    FROM inscripciones
    WHERE id_usuario_fk      = p_id_usuario
      AND id_curso_fk        = p_id_curso
      AND estado_inscripcion IN ('activa', 'completada')
      AND estado_activo      = 1;

    IF v_ya_inscrito > 0 THEN
        SET p_resultado = 'ERROR: Ya estás inscrito en este curso.';
    ELSE
        -- Verificar si ya tiene un pago pendiente para este curso
        SELECT COUNT(*) INTO v_ya_pendiente
        FROM transacciones_pago
        WHERE id_usuario_fk     = p_id_usuario
          AND id_curso_fk       = p_id_curso
          AND estado_transaccion = 'pendiente'
          AND estado_activo      = 1;

        IF v_ya_pendiente > 0 THEN
            SET p_resultado = 'ERROR: Ya tienes una solicitud pendiente de aprobación para este curso. Por favor espera a que el administrador la procese.';
        ELSE
            START TRANSACTION;

            -- Insertar transacción en estado PENDIENTE
            INSERT INTO transacciones_pago (
                id_usuario_fk, id_curso_fk, id_medio_pago_fk,
                numero_referencia, monto_total, estado_transaccion,
                observaciones, estado_activo
            ) VALUES (
                p_id_usuario, p_id_curso, p_id_medio_pago,
                p_referencia, p_monto, 'pendiente',
                CONCAT('Comprobante: ', IFNULL(p_comprobante, 'No adjunto')), 1
            );

            SET v_id_transaccion = LAST_INSERT_ID();

            -- Insertar inscripción en estado SUSPENDIDA (esperando aprobación)
            -- Si ya existe una inscripción cancelada/suspendida, la actualizamos
            INSERT INTO inscripciones (
                id_usuario_fk, id_curso_fk, fecha_inscripcion,
                monto_pagado, estado_inscripcion, porcentaje_progreso, estado_activo
            ) VALUES (
                p_id_usuario, p_id_curso, NOW(),
                p_monto, 'suspendida', 0.00, 1
            )
            ON DUPLICATE KEY UPDATE
                monto_pagado      = p_monto,
                estado_inscripcion = 'suspendida',
                fecha_modificacion = NOW();

            COMMIT;
            SET p_resultado = CONCAT('OK:', v_id_transaccion);
        END IF;
    END IF;
END //

DELIMITER ;

-- ============================================================
-- 2. sp_aprobar_pago_admin
-- El administrador aprueba una transacción pendiente.
-- Activa la inscripción del estudiante, incrementa el contador
-- del curso y genera una notificación para el estudiante.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_aprobar_pago_admin;

DELIMITER //

CREATE PROCEDURE sp_aprobar_pago_admin(
    IN  p_id_transaccion  INT UNSIGNED,
    IN  p_id_admin        INT UNSIGNED,
    IN  p_observaciones   VARCHAR(500),
    OUT p_resultado       VARCHAR(200)
)
BEGIN
    DECLARE v_id_usuario    INT UNSIGNED;
    DECLARE v_id_curso      INT UNSIGNED;
    DECLARE v_monto         DECIMAL(12,2);
    DECLARE v_estado_actual VARCHAR(30);
    DECLARE v_titulo_curso  VARCHAR(255);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR: Fallo interno al aprobar el pago.';
    END;

    -- Obtener datos de la transacción
    SELECT t.id_usuario_fk, t.id_curso_fk, t.monto_total, t.estado_transaccion, c.titulo_curso
    INTO v_id_usuario, v_id_curso, v_monto, v_estado_actual, v_titulo_curso
    FROM transacciones_pago t
    JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
    WHERE t.id_transaccion_pk = p_id_transaccion AND t.estado_activo = 1;

    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'ERROR: Transacción no encontrada.';
    ELSEIF v_estado_actual != 'pendiente' THEN
        SET p_resultado = CONCAT('ERROR: Esta transacción ya fue procesada (estado: ', v_estado_actual, ').');
    ELSE
        START TRANSACTION;

        -- Aprobar la transacción
        UPDATE transacciones_pago
        SET estado_transaccion  = 'aprobada',
            observaciones       = IFNULL(p_observaciones, 'Aprobado por administrador'),
            modificado_por      = p_id_admin,
            fecha_modificacion  = NOW()
        WHERE id_transaccion_pk = p_id_transaccion;

        -- Activar la inscripción del estudiante
        UPDATE inscripciones
        SET estado_inscripcion  = 'activa',
            monto_pagado        = v_monto,
            modificado_por      = p_id_admin,
            fecha_modificacion  = NOW()
        WHERE id_usuario_fk    = v_id_usuario
          AND id_curso_fk      = v_id_curso;

        -- Incrementar contador de estudiantes en el curso
        UPDATE cursos
        SET numero_estudiantes = numero_estudiantes + 1,
            modificado_por     = p_id_admin
        WHERE id_curso_pk = v_id_curso;

        -- Crear notificación para el estudiante
        INSERT INTO notificaciones (
            titulo_notificacion, mensaje_notificacion, tipo_notificacion,
            id_usuario_emisor_fk, url_accion, estado_activo
        ) VALUES (
            '✅ Inscripción Aprobada',
            CONCAT('Tu pago para el curso "', v_titulo_curso, '" fue aprobado. ¡Ya puedes acceder a tu contenido!'),
            'exito',
            p_id_admin,
            'student/mis-cursos.php',
            1
        );

        -- Asignar notificación al estudiante
        INSERT INTO notificaciones_usuario (
            id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo
        ) VALUES (
            LAST_INSERT_ID(), v_id_usuario, 0, 1
        );

        COMMIT;
        SET p_resultado = 'OK: Inscripción aprobada y estudiante notificado.';
    END IF;
END //

DELIMITER ;

-- ============================================================
-- 3. sp_rechazar_pago_admin
-- El administrador rechaza una transacción pendiente.
-- Cancela la inscripción suspendida y notifica al estudiante.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_rechazar_pago_admin;

DELIMITER //

CREATE PROCEDURE sp_rechazar_pago_admin(
    IN  p_id_transaccion  INT UNSIGNED,
    IN  p_id_admin        INT UNSIGNED,
    IN  p_motivo          VARCHAR(500),
    OUT p_resultado       VARCHAR(200)
)
BEGIN
    DECLARE v_id_usuario    INT UNSIGNED;
    DECLARE v_id_curso      INT UNSIGNED;
    DECLARE v_estado_actual VARCHAR(30);
    DECLARE v_titulo_curso  VARCHAR(255);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR: Fallo interno al rechazar el pago.';
    END;

    SELECT t.id_usuario_fk, t.id_curso_fk, t.estado_transaccion, c.titulo_curso
    INTO v_id_usuario, v_id_curso, v_estado_actual, v_titulo_curso
    FROM transacciones_pago t
    JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
    WHERE t.id_transaccion_pk = p_id_transaccion AND t.estado_activo = 1;

    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'ERROR: Transacción no encontrada.';
    ELSEIF v_estado_actual != 'pendiente' THEN
        SET p_resultado = CONCAT('ERROR: Esta transacción ya fue procesada (estado: ', v_estado_actual, ').');
    ELSE
        START TRANSACTION;

        -- Rechazar la transacción
        UPDATE transacciones_pago
        SET estado_transaccion  = 'rechazada',
            observaciones       = IFNULL(p_motivo, 'Rechazado por administrador'),
            modificado_por      = p_id_admin,
            fecha_modificacion  = NOW()
        WHERE id_transaccion_pk = p_id_transaccion;

        -- Cancelar la inscripción suspendida
        UPDATE inscripciones
        SET estado_inscripcion  = 'cancelada',
            modificado_por      = p_id_admin,
            fecha_modificacion  = NOW()
        WHERE id_usuario_fk    = v_id_usuario
          AND id_curso_fk      = v_id_curso
          AND estado_inscripcion = 'suspendida';

        -- Notificar al estudiante
        INSERT INTO notificaciones (
            titulo_notificacion, mensaje_notificacion, tipo_notificacion,
            id_usuario_emisor_fk, url_accion, estado_activo
        ) VALUES (
            '❌ Solicitud de Inscripción Rechazada',
            CONCAT('Tu solicitud de pago para "', v_titulo_curso, '" fue rechazada. Motivo: ', IFNULL(p_motivo, 'No especificado'), '. Contáctanos para más información.'),
            'alerta',
            p_id_admin,
            'student/notificaciones.php',
            1
        );

        INSERT INTO notificaciones_usuario (
            id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo
        ) VALUES (
            LAST_INSERT_ID(), v_id_usuario, 0, 1
        );

        COMMIT;
        SET p_resultado = 'OK: Solicitud rechazada y estudiante notificado.';
    END IF;
END //

DELIMITER ;

/*
 * ============================================================
 * RESUMEN: procedures_pagos.sql
 * ============================================================
 * Flujo de pago con aprobación del administrador:
 *
 * 1. sp_solicitar_inscripcion
 *    - Crea transacción 'pendiente' + inscripción 'suspendida'
 *    - Verifica duplicados (ya inscrito / ya tiene pendiente)
 *    - Retorna OK:<id_transaccion> o ERROR:<mensaje>
 *
 * 2. sp_aprobar_pago_admin
 *    - Aprueba la transacción → 'aprobada'
 *    - Activa la inscripción → 'activa'
 *    - Incrementa numero_estudiantes del curso
 *    - Envía notificación al estudiante
 *    - Solo funciona si estado actual es 'pendiente'
 *
 * 3. sp_rechazar_pago_admin
 *    - Rechaza la transacción → 'rechazada'
 *    - Cancela inscripción suspendida → 'cancelada'
 *    - Envía notificación con motivo al estudiante
 *    - Solo funciona si estado actual es 'pendiente'
 * ============================================================
 */
