-- ============================================================
-- /cursoonline/database/procedures_admin_usuarios.sql
-- Procedimientos almacenados para gestión admin de usuarios
-- EduTech Academy — Motor: MySQL 8.x
-- ============================================================

-- -----------------------------------------------------------
-- 1. sp_admin_listar_usuarios
-- Lista usuarios paginada con filtros por búsqueda, rol y estado
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_listar_usuarios;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_usuarios(
    IN p_busqueda   VARCHAR(255),
    IN p_id_rol     INT,
    IN p_estado     VARCHAR(20),
    IN p_pagina     INT,
    IN p_por_pagina INT,
    OUT p_total     INT
)
BEGIN
    DECLARE v_off INT DEFAULT 0;
    SET v_off = (p_pagina - 1) * p_por_pagina;

    SELECT SQL_CALC_FOUND_ROWS
        u.id_usuario_pk, u.primer_nombre, u.segundo_nombre,
        u.primer_apellido, u.segundo_apellido,
        u.correo_electronico, u.numero_telefono, u.foto_perfil,
        u.id_rol_fk, r.nombre_rol,
        u.estado_activo, u.fecha_creacion, u.ultimo_acceso,
        (SELECT COUNT(*) FROM inscripciones WHERE id_usuario_fk = u.id_usuario_pk AND estado_activo = 1) AS total_cursos
    FROM usuarios u
    INNER JOIN roles r ON r.id_rol_pk = u.id_rol_fk
    WHERE (p_busqueda = '' OR u.primer_nombre LIKE CONCAT('%', p_busqueda, '%')
           OR u.primer_apellido LIKE CONCAT('%', p_busqueda, '%')
           OR u.correo_electronico LIKE CONCAT('%', p_busqueda, '%'))
      AND (p_id_rol = 0 OR u.id_rol_fk = p_id_rol)
      AND (p_estado = 'todos' OR (p_estado = 'activo' AND u.estado_activo = 1) OR (p_estado = 'inactivo' AND u.estado_activo = 0))
    ORDER BY u.fecha_creacion DESC
    LIMIT v_off, p_por_pagina;
    SELECT FOUND_ROWS() INTO p_total;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 2. sp_admin_obtener_usuario
-- Obtiene datos completos de un usuario
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_obtener_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_obtener_usuario(IN p_id_usuario INT)
BEGIN
    SELECT u.*, r.nombre_rol
    FROM usuarios u
    INNER JOIN roles r ON r.id_rol_pk = u.id_rol_fk
    WHERE u.id_usuario_pk = p_id_usuario;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 3. sp_admin_guardar_usuario
-- Inserta o actualiza un usuario (sin cambiar contraseña si es NULL)
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_guardar_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_guardar_usuario(
    IN p_id_usuario            INT,
    IN p_primer_nombre         VARCHAR(100),
    IN p_segundo_nombre        VARCHAR(100),
    IN p_primer_apellido       VARCHAR(100),
    IN p_segundo_apellido      VARCHAR(100),
    IN p_correo_electronico    VARCHAR(255),
    IN p_contrasena_hash       VARCHAR(255),
    IN p_numero_telefono       VARCHAR(20),
    IN p_foto_perfil           VARCHAR(255),
    IN p_id_rol_fk             INT,
    IN p_tipo_documento        VARCHAR(20),
    IN p_numero_documento      VARCHAR(30),
    IN p_fecha_nacimiento      DATE,
    IN p_ciudad                VARCHAR(100),
    IN p_departamento          VARCHAR(100),
    IN p_pais                  VARCHAR(100),
    IN p_estado_activo         TINYINT,
    IN p_modificado_por        INT,
    OUT p_id_nuevo             INT
)
BEGIN
    IF p_id_usuario > 0 THEN
        UPDATE usuarios SET
            primer_nombre              = p_primer_nombre,
            segundo_nombre             = p_segundo_nombre,
            primer_apellido            = p_primer_apellido,
            segundo_apellido           = p_segundo_apellido,
            correo_electronico         = p_correo_electronico,
            numero_telefono            = p_numero_telefono,
            foto_perfil                = p_foto_perfil,
            id_rol_fk                  = p_id_rol_fk,
            tipo_documento_identidad   = p_tipo_documento,
            numero_documento_identidad = p_numero_documento,
            fecha_nacimiento           = p_fecha_nacimiento,
            ciudad_residencia          = p_ciudad,
            departamento_residencia    = p_departamento,
            pais_residencia            = p_pais,
            estado_activo              = p_estado_activo,
            modificado_por             = p_modificado_por,
            fecha_modificacion         = NOW()
        WHERE id_usuario_pk = p_id_usuario;

        IF p_contrasena_hash IS NOT NULL AND p_contrasena_hash != '' THEN
            UPDATE usuarios SET contrasena_hash = p_contrasena_hash
            WHERE id_usuario_pk = p_id_usuario;
        END IF;

        SET p_id_nuevo = p_id_usuario;
    ELSE
        INSERT INTO usuarios (
            primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
            correo_electronico, contrasena_hash, numero_telefono, foto_perfil,
            id_rol_fk, tipo_documento_identidad, numero_documento_identidad,
            fecha_nacimiento, ciudad_residencia, departamento_residencia, pais_residencia,
            estado_activo, modificado_por
        ) VALUES (
            p_primer_nombre, p_segundo_nombre, p_primer_apellido, p_segundo_apellido,
            p_correo_electronico, p_contrasena_hash, p_numero_telefono, p_foto_perfil,
            p_id_rol_fk, p_tipo_documento, p_numero_documento,
            p_fecha_nacimiento, p_ciudad, p_departamento, p_pais,
            p_estado_activo, p_modificado_por
        );
        SET p_id_nuevo = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 4. sp_admin_cambiar_estado_usuario (activar/desactivar)
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_cambiar_estado_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_cambiar_estado_usuario(
    IN p_id_usuario        INT,
    IN p_nuevo_estado      TINYINT,
    IN p_modificado_por    INT
)
BEGIN
    UPDATE usuarios
    SET estado_activo     = p_nuevo_estado,
        modificado_por    = p_modificado_por,
        fecha_modificacion = NOW()
    WHERE id_usuario_pk = p_id_usuario;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 5. sp_admin_eliminar_usuario (soft delete)
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_eliminar_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_eliminar_usuario(
    IN p_id_usuario     INT,
    IN p_eliminado_por  INT
)
BEGIN
    UPDATE usuarios
    SET estado_activo     = 0,
        modificado_por    = p_eliminado_por,
        fecha_modificacion = NOW()
    WHERE id_usuario_pk = p_id_usuario;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 6. sp_admin_estadisticas_usuario
-- Estadísticas de un usuario: inscripciones, progreso, pagos
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_estadisticas_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_estadisticas_usuario(IN p_id_usuario INT)
BEGIN
    SELECT
        (SELECT COUNT(*) FROM inscripciones WHERE id_usuario_fk = p_id_usuario AND estado_activo = 1) AS total_inscripciones,
        (SELECT COUNT(*) FROM inscripciones WHERE id_usuario_fk = p_id_usuario AND estado_inscripcion = 'activa' AND estado_activo = 1) AS inscripciones_activas,
        (SELECT COUNT(*) FROM inscripciones WHERE id_usuario_fk = p_id_usuario AND estado_inscripcion = 'completada' AND estado_activo = 1) AS inscripciones_completadas,
        (SELECT COUNT(*) FROM inscripciones WHERE id_usuario_fk = p_id_usuario AND estado_inscripcion = 'cancelada' AND estado_activo = 1) AS inscripciones_canceladas,
        (SELECT COALESCE(SUM(monto_pagado), 0) FROM inscripciones WHERE id_usuario_fk = p_id_usuario AND estado_activo = 1) AS total_gastado,
        (SELECT COALESCE(AVG(porcentaje_progreso), 0) FROM inscripciones WHERE id_usuario_fk = p_id_usuario AND estado_inscripcion IN ('activa','completada') AND estado_activo = 1) AS progreso_promedio,
        (SELECT COUNT(*) FROM log_accesos WHERE id_usuario_fk = p_id_usuario AND tipo_accion = 'login_exitoso') AS total_accesos;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 7. sp_admin_inscripciones_usuario
-- Lista inscripciones de un usuario con datos del curso
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_inscripciones_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_inscripciones_usuario(IN p_id_usuario INT)
BEGIN
    SELECT
        i.id_inscripcion_pk, i.fecha_inscripcion, i.estado_inscripcion,
        i.porcentaje_progreso, i.monto_pagado, i.fecha_finalizacion,
        i.certificado_generado,
        c.id_curso_pk, c.titulo_curso, c.imagen_portada, c.nivel_dificultad,
        cat.nombre_categoria
    FROM inscripciones i
    INNER JOIN cursos c ON c.id_curso_pk = i.id_curso_fk
    LEFT JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    WHERE i.id_usuario_fk = p_id_usuario AND i.estado_activo = 1
    ORDER BY i.fecha_inscripcion DESC;
END$$
DELIMITER ;

-- -----------------------------------------------------------
-- 8. sp_admin_actividad_reciente_usuario
-- Últimas acciones de auditoría del usuario
-- -----------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_admin_actividad_reciente_usuario;
DELIMITER $$
CREATE PROCEDURE sp_admin_actividad_reciente_usuario(
    IN p_id_usuario INT,
    IN p_limite     INT
)
BEGIN
    SELECT id_log_acceso_pk, tipo_accion, panel_acceso, direccion_ip,
           fecha_acceso, detalles_adicionales
    FROM log_accesos
    WHERE id_usuario_fk = p_id_usuario
    ORDER BY fecha_acceso DESC
    LIMIT p_limite;
END$$
DELIMITER ;

-- ============================================================
-- RESUMEN: 8 procedimientos para admin de usuarios
-- sp_admin_listar_usuarios, _obtener_usuario, _guardar_usuario,
-- _cambiar_estado_usuario, _eliminar_usuario, _estadisticas_usuario,
-- _inscripciones_usuario, _actividad_reciente_usuario
-- ============================================================
