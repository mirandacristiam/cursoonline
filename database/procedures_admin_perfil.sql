-- ============================================================
-- /cursoonline/database/procedures_admin_perfil.sql
-- Procedimientos almacenados para la gestión del perfil
-- del administrador / usuario general
-- ============================================================

-- ============================================================
-- 1. sp_admin_actualizar_perfil
-- Actualiza los datos personales del usuario
-- ============================================================
DROP PROCEDURE IF EXISTS sp_admin_actualizar_perfil;
DELIMITER //
CREATE PROCEDURE sp_admin_actualizar_perfil(
    IN p_id_usuario       INT,
    IN p_primer_nombre    VARCHAR(255),
    IN p_segundo_nombre   VARCHAR(255),
    IN p_primer_apellido  VARCHAR(255),
    IN p_segundo_apellido VARCHAR(255),
    IN p_telefono         VARCHAR(50),
    IN p_tipo_documento   VARCHAR(50),
    IN p_numero_documento VARCHAR(50),
    IN p_fecha_nacimiento DATE,
    IN p_ciudad           VARCHAR(255),
    IN p_departamento     VARCHAR(255),
    IN p_pais             VARCHAR(255)
)
BEGIN
    UPDATE usuarios
    SET primer_nombre               = p_primer_nombre,
        segundo_nombre              = p_segundo_nombre,
        primer_apellido             = p_primer_apellido,
        segundo_apellido            = p_segundo_apellido,
        numero_telefono             = p_telefono,
        tipo_documento_identidad    = p_tipo_documento,
        numero_documento_identidad  = p_numero_documento,
        fecha_nacimiento            = p_fecha_nacimiento,
        ciudad_residencia           = p_ciudad,
        departamento_residencia     = p_departamento,
        pais_residencia             = p_pais,
        fecha_modificacion          = NOW(),
        modificado_por              = p_id_usuario
    WHERE id_usuario_pk = p_id_usuario AND estado_activo = 1;
END//
DELIMITER ;

-- ============================================================
-- 2. sp_admin_obtener_hash
-- Obtiene el hash de la contraseña actual para verificación
-- ============================================================
DROP PROCEDURE IF EXISTS sp_admin_obtener_hash;
DELIMITER //
CREATE PROCEDURE sp_admin_obtener_hash(
    IN p_id_usuario INT
)
BEGIN
    SELECT contrasena_hash
    FROM usuarios
    WHERE id_usuario_pk = p_id_usuario AND estado_activo = 1;
END//
DELIMITER ;

-- ============================================================
-- 3. sp_admin_cambiar_password
-- Actualiza la contraseña del usuario
-- ============================================================
DROP PROCEDURE IF EXISTS sp_admin_cambiar_password;
DELIMITER //
CREATE PROCEDURE sp_admin_cambiar_password(
    IN p_id_usuario INT,
    IN p_hash       VARCHAR(255)
)
BEGIN
    UPDATE usuarios
    SET contrasena_hash     = p_hash,
        fecha_modificacion  = NOW(),
        modificado_por      = p_id_usuario
    WHERE id_usuario_pk = p_id_usuario AND estado_activo = 1;
END//
DELIMITER ;

-- ============================================================
-- 4. sp_admin_obtener_foto
-- Obtiene la ruta de la foto actual
-- ============================================================
DROP PROCEDURE IF EXISTS sp_admin_obtener_foto;
DELIMITER //
CREATE PROCEDURE sp_admin_obtener_foto(
    IN p_id_usuario INT
)
BEGIN
    SELECT foto_perfil
    FROM usuarios
    WHERE id_usuario_pk = p_id_usuario;
END//
DELIMITER ;

-- ============================================================
-- 5. sp_admin_subir_foto
-- Actualiza la ruta de la foto de perfil
-- ============================================================
DROP PROCEDURE IF EXISTS sp_admin_subir_foto;
DELIMITER //
CREATE PROCEDURE sp_admin_subir_foto(
    IN p_id_usuario INT,
    IN p_foto       VARCHAR(500)
)
BEGIN
    UPDATE usuarios
    SET foto_perfil          = p_foto,
        fecha_modificacion   = NOW(),
        modificado_por       = p_id_usuario
    WHERE id_usuario_pk = p_id_usuario AND estado_activo = 1;
END//
DELIMITER ;

-- ============================================================
-- 6. sp_admin_eliminar_foto
-- Elimina la foto de perfil (pone NULL)
-- ============================================================
DROP PROCEDURE IF EXISTS sp_admin_eliminar_foto;
DELIMITER //
CREATE PROCEDURE sp_admin_eliminar_foto(
    IN p_id_usuario INT
)
BEGIN
    UPDATE usuarios
    SET foto_perfil          = NULL,
        fecha_modificacion   = NOW(),
        modificado_por       = p_id_usuario
    WHERE id_usuario_pk = p_id_usuario AND estado_activo = 1;
END//
DELIMITER ;
