-- /cursoonline/database/procedures.sql
-- ============================================================
-- Procedimientos Almacenados — EduTech Academy
-- Motor: MySQL 8.x | Charset: utf8mb4
-- ============================================================

USE `db_edutechacademy`;

-- ------------------------------------------------------------
-- 1. sp_inscribir_estudiante
-- Inscribe un estudiante en un curso, registra la transacción y 
-- actualiza el número de estudiantes del curso de forma transaccional.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_inscribir_estudiante;

DELIMITER //

CREATE PROCEDURE sp_inscribir_estudiante(
    IN p_id_usuario INT UNSIGNED,
    IN p_id_curso INT UNSIGNED,
    IN p_monto DECIMAL(12,2),
    IN p_id_medio_pago INT UNSIGNED,
    IN p_referencia VARCHAR(100),
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_ya_inscrito INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 'ERROR: Transacción fallida en el servidor de base de datos.';
    END;

    START TRANSACTION;
    
    -- Verificar si ya está inscrito
    SELECT COUNT(*) INTO v_ya_inscrito 
    FROM inscripciones 
    WHERE id_usuario_fk = p_id_usuario AND id_curso_fk = p_id_curso AND estado_activo = 1;
    
    IF v_ya_inscrito > 0 THEN
        SET p_resultado = 'ERROR: El estudiante ya está inscrito en este curso.';
        ROLLBACK;
    ELSE
        -- Insertar transacción de pago aprobada
        INSERT INTO transacciones_pago (
            id_usuario_fk, id_curso_fk, id_medio_pago_fk, numero_referencia, 
            monto_total, estado_transaccion, fecha_transaccion, observaciones, estado_activo
        ) VALUES (
            p_id_usuario, p_id_curso, p_id_medio_pago, p_referencia, 
            p_monto, 'aprobada', NOW(), 'Inscripción automática por procedimiento', 1
        );
        
        -- Insertar inscripción activa
        INSERT INTO inscripciones (
            id_usuario_fk, id_curso_fk, fecha_inscripcion, monto_pagado, 
            estado_inscripcion, porcentaje_progreso, estado_activo
        ) VALUES (
            p_id_usuario, p_id_curso, NOW(), p_monto, 
            'activa', 0.00, 1
        );
        
        -- Actualizar el contador desnormalizado de estudiantes en el curso
        UPDATE cursos 
        SET numero_estudiantes = numero_estudiantes + 1 
        WHERE id_curso_pk = p_id_curso;
        
        COMMIT;
        SET p_resultado = 'OK: Inscripción exitosa.';
    END IF;
END //

DELIMITER ;

-- ------------------------------------------------------------
-- 2. sp_registrar_intento_evaluacion
-- Habilita e inicia un intento de evaluación, controlando los 
-- límites de intentos y que se cumpla con el progreso de clases visto.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_intento_evaluacion;

DELIMITER //

CREATE PROCEDURE sp_registrar_intento_evaluacion(
    IN p_id_inscripcion INT UNSIGNED,
    IN p_id_evaluacion INT UNSIGNED,
    OUT p_id_intento INT UNSIGNED,
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_intentos_permitidos INT DEFAULT 1;
    DECLARE v_intentos_realizados INT DEFAULT 0;
    DECLARE v_id_curso INT UNSIGNED;
    DECLARE v_clases_requeridas INT UNSIGNED;
    DECLARE v_clases_vistas INT UNSIGNED;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_intento = 0;
        SET p_resultado = 'ERROR: Excepción de base de datos al registrar el intento.';
    END;

    -- Obtener curso y requerimientos de la evaluación
    SELECT id_curso_fk, intentos_permitidos, numero_clases_requeridas 
    INTO v_id_curso, v_intentos_permitidos, v_clases_requeridas
    FROM evaluaciones 
    WHERE id_evaluacion_pk = p_id_evaluacion AND estado_activo = 1;
    
    -- Contar intentos del estudiante para esta evaluación
    SELECT COUNT(*) INTO v_intentos_realizados
    FROM intentos_evaluacion
    WHERE id_inscripcion_fk = p_id_inscripcion AND id_evaluacion_fk = p_id_evaluacion AND estado_activo = 1;
    
    -- Contar clases vistas/completadas del estudiante para este curso
    SELECT COUNT(*) INTO v_clases_vistas
    FROM progreso_clases pc
    JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
    JOIN modulos_curso mc ON cc.id_modulo_fk = mc.id_modulo_pk
    WHERE pc.id_inscripcion_fk = p_id_inscripcion 
      AND mc.id_curso_fk = v_id_curso 
      AND pc.estado_completada = 1 
      AND pc.estado_activo = 1
      AND cc.estado_activo = 1;
      
    START TRANSACTION;
    
    IF v_intentos_realizados >= v_intentos_permitidos THEN
        SET p_id_intento = 0;
        SET p_resultado = 'ERROR: Has superado el límite de intentos permitidos para esta evaluación.';
        ROLLBACK;
    ELSEIF v_clases_vistas < v_clases_requeridas THEN
        SET p_id_intento = 0;
        SET p_resultado = 'ERROR: No cumples con el número de clases requeridas para habilitar esta evaluación.';
        ROLLBACK;
    ELSE
        -- Insertar el intento en estado 'en_progreso'
        INSERT INTO intentos_evaluacion (
            id_inscripcion_fk, id_evaluacion_fk, numero_intento, 
            fecha_inicio, estado_intento, estado_activo
        ) VALUES (
            p_id_inscripcion, p_id_evaluacion, v_intentos_realizados + 1, 
            NOW(), 'en_progreso', 1
        );
        
        SET p_id_intento = LAST_INSERT_ID();
        SET p_resultado = 'OK: Intento registrado con éxito.';
        COMMIT;
    END IF;
END //

DELIMITER ;

-- ------------------------------------------------------------
-- 3. sp_actualizar_progreso_estudiante
-- Registra el progreso de reproducción de una clase y recalcula el 
-- avance del curso actualizándolo en la inscripción.
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_actualizar_progreso_estudiante;

DELIMITER //

CREATE PROCEDURE sp_actualizar_progreso_estudiante(
    IN p_id_inscripcion INT UNSIGNED,
    IN p_id_clase INT UNSIGNED,
    IN p_porcentaje INT UNSIGNED,
    OUT p_progreso_final DECIMAL(5,2)
)
BEGIN
    DECLARE v_id_curso INT UNSIGNED;
    DECLARE v_total_clases INT UNSIGNED DEFAULT 0;
    DECLARE v_clases_completadas INT UNSIGNED DEFAULT 0;
    DECLARE v_completada TINYINT(1) DEFAULT 0;
    DECLARE v_existe_progreso INT DEFAULT 0;
    DECLARE v_id_progreso INT UNSIGNED;

    -- Obtener el ID del curso de la inscripción
    SELECT id_curso_fk INTO v_id_curso FROM inscripciones WHERE id_inscripcion_pk = p_id_inscripcion;
    
    -- Contar el total de clases que tiene este curso
    SELECT COUNT(*) INTO v_total_clases 
    FROM clases_curso cc
    JOIN modulos_curso mc ON cc.id_modulo_fk = mc.id_modulo_pk
    WHERE mc.id_curso_fk = v_id_curso AND cc.estado_activo = 1 AND mc.estado_activo = 1;
    
    -- Determinar si la clase debe marcarse como completada (ej. 80% o más reproducido)
    IF p_porcentaje >= 80 THEN
        SET v_completada = 1;
    END IF;
    
    -- Verificar si ya hay registro de progreso para esta clase
    SELECT COUNT(*), MAX(id_progreso_pk) INTO v_existe_progreso, v_id_progreso
    FROM progreso_clases 
    WHERE id_inscripcion_fk = p_id_inscripcion AND id_clase_fk = p_id_clase AND estado_activo = 1;
    
    START TRANSACTION;
    
    IF v_existe_progreso > 0 AND v_id_progreso IS NOT NULL THEN
        UPDATE progreso_clases 
        SET porcentaje_completado = GREATEST(porcentaje_completado, p_porcentaje),
            estado_completada = GREATEST(estado_completada, v_completada),
            fecha_ultima_vista = NOW()
        WHERE id_progreso_pk = v_id_progreso;
    ELSE
        INSERT INTO progreso_clases (
            id_inscripcion_fk, id_clase_fk, porcentaje_completado, estado_completada, 
            fecha_primera_vista, fecha_ultima_vista, estado_activo
        ) VALUES (
            p_id_inscripcion, p_id_clase, p_porcentaje, v_completada, 
            NOW(), NOW(), 1
        );
    END IF;
    
    -- Contar cuántas clases de este curso han sido completadas por el estudiante
    SELECT COUNT(*) INTO v_clases_completadas
    FROM progreso_clases pc
    JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
    JOIN modulos_curso mc ON cc.id_modulo_fk = mc.id_modulo_pk
    WHERE pc.id_inscripcion_fk = p_id_inscripcion 
      AND mc.id_curso_fk = v_id_curso 
      AND pc.estado_completada = 1 
      AND pc.estado_activo = 1
      AND cc.estado_activo = 1;
      
    -- Calcular progreso final (porcentaje)
    IF v_total_clases > 0 THEN
        SET p_progreso_final = (v_clases_completadas / v_total_clases) * 100.00;
    ELSE
        SET p_progreso_final = 0.00;
    END IF;
    
    -- Actualizar el progreso en la inscripción
    UPDATE inscripciones 
    SET porcentaje_progreso = p_progreso_final
    WHERE id_inscripcion_pk = p_id_inscripcion;
    
    COMMIT;
END //

DELIMITER ;

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/database/procedures.sql
 * ============================================================
 * Procedimientos almacenados para la lógica de base de datos crítica:
 *   - sp_inscribir_estudiante: Matrícula, pago y desnormalización de conteos.
 *   - sp_registrar_intento_evaluacion: Validaciones de progreso e intentos de evaluación.
 *   - sp_actualizar_progreso_estudiante: Seguimiento de reproducción y recalculo del % de avance.
 * ============================================================
 */
