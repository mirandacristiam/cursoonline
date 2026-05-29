-- ============================================================
-- /cursoonline/database/procedures_cursos.sql
-- Procedimientos Almacenados del Sistema de Cursos
-- EduTech Academy — Motor: MySQL 8.x
--
-- ⚠ INSTRUCCIONES DE EJECUCIÓN:
--   1. Abre phpMyAdmin o MySQL Workbench
--   2. Selecciona la base de datos: db_edutechacademy
--   3. Ejecuta este archivo completo (Import o paste en SQL)
-- ============================================================

USE `db_edutechacademy`;

-- ============================================================
-- PROCEDIMIENTO 1: sp_obtener_catalogo_cursos
-- Retorna el catálogo público de cursos activos con filtro
-- Parámetros:
--   p_id_categoria  INT  — 0 = todas las categorías
--   p_busqueda  VARCHAR  — '' = sin filtro de texto
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_obtener_catalogo_cursos`;

DELIMITER $$
CREATE PROCEDURE `sp_obtener_catalogo_cursos`(
    IN p_id_categoria  INT,
    IN p_busqueda      VARCHAR(200)
)
BEGIN
    SELECT
        c.id_curso_pk,
        c.titulo_curso,
        c.resumen_corto,
        c.imagen_portada,
        c.precio,
        c.precio_descuento,
        c.nivel_dificultad,
        c.total_horas,
        c.calificacion_promedio,
        c.total_estudiantes_inscritos,
        c.id_categoria_fk,
        cat.nombre_categoria,
        cat.icono_categoria,
        cat.color_categoria,
        u.primer_nombre         AS profesor_nombre,
        u.primer_apellido       AS profesor_apellido,
        u.foto_perfil           AS profesor_foto
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT  JOIN usuarios u           ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE c.estado_activo = 1
      AND (p_id_categoria = 0 OR c.id_categoria_fk = p_id_categoria)
      AND (
            p_busqueda = ''
            OR c.titulo_curso   LIKE CONCAT('%', p_busqueda, '%')
            OR c.resumen_corto  LIKE CONCAT('%', p_busqueda, '%')
            OR cat.nombre_categoria LIKE CONCAT('%', p_busqueda, '%')
          )
    ORDER BY c.calificacion_promedio DESC, c.fecha_creacion DESC;
END$$
DELIMITER ;

-- ============================================================
-- PROCEDIMIENTO 2: sp_obtener_detalle_curso
-- Retorna TODOS los datos de un curso para la página de detalle
-- Parámetros:
--   p_id_curso  INT — ID del curso a mostrar
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_obtener_detalle_curso`;

DELIMITER $$
CREATE PROCEDURE `sp_obtener_detalle_curso`(
    IN p_id_curso INT
)
BEGIN
    -- ── Datos principales del curso ───────────────────
    SELECT
        c.id_curso_pk,
        c.titulo_curso,
        c.resumen_corto,
        c.descripcion_larga,
        c.imagen_portada,
        c.video_trailer_url,
        c.precio,
        c.precio_descuento,
        c.nivel_dificultad,
        c.total_horas,
        c.total_clases,
        c.calificacion_promedio,
        c.total_estudiantes_inscritos,
        c.idioma,
        c.requisitos_previos,
        c.lo_que_aprenderas,
        c.para_quien_es,
        c.estado_activo,
        c.fecha_creacion,
        c.id_categoria_fk,
        cat.nombre_categoria,
        cat.icono_categoria,
        cat.color_categoria,
        u.id_usuario_pk         AS id_profesor,
        u.primer_nombre         AS profesor_nombre,
        u.primer_apellido       AS profesor_apellido,
        u.foto_perfil           AS profesor_foto,
        u.descripcion_bio       AS profesor_bio
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT  JOIN usuarios u           ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE c.id_curso_pk = p_id_curso
      AND c.estado_activo = 1;

    -- ── Módulos / Secciones del curso ─────────────────
    SELECT
        m.id_modulo_pk,
        m.nombre_modulo,
        m.orden_modulo,
        COUNT(cl.id_clase_pk) AS total_clases_modulo
    FROM modulos_curso m
    LEFT JOIN clases cl ON cl.id_modulo_fk = m.id_modulo_pk AND cl.estado_activo = 1
    WHERE m.id_curso_fk = p_id_curso AND m.estado_activo = 1
    GROUP BY m.id_modulo_pk, m.nombre_modulo, m.orden_modulo
    ORDER BY m.orden_modulo;

    -- ── Clases del curso ─────────────────────────────
    SELECT
        cl.id_clase_pk,
        cl.id_modulo_fk,
        cl.titulo_clase,
        cl.duracion_minutos,
        cl.tipo_clase,
        cl.es_preview_gratuito,
        cl.orden_clase
    FROM clases cl
    INNER JOIN modulos_curso m ON m.id_modulo_pk = cl.id_modulo_fk
    WHERE m.id_curso_fk = p_id_curso
      AND cl.estado_activo = 1
      AND m.estado_activo  = 1
    ORDER BY m.orden_modulo, cl.orden_clase;
END$$
DELIMITER ;

-- ============================================================
-- PROCEDIMIENTO 3: sp_crear_curso
-- Inserta un nuevo curso en el catálogo
-- Parámetros: todos los campos editables del curso
-- Retorna: el ID del nuevo curso creado
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_crear_curso`;

DELIMITER $$
CREATE PROCEDURE `sp_crear_curso`(
    IN  p_titulo_curso          VARCHAR(200),
    IN  p_resumen_corto         VARCHAR(500),
    IN  p_descripcion_larga     LONGTEXT,
    IN  p_imagen_portada        VARCHAR(500),
    IN  p_video_trailer_url     VARCHAR(500),
    IN  p_precio                DECIMAL(10,2),
    IN  p_precio_descuento      DECIMAL(10,2),
    IN  p_nivel_dificultad      ENUM('principiante','intermedio','avanzado'),
    IN  p_total_horas           DECIMAL(5,1),
    IN  p_id_categoria_fk       INT,
    IN  p_id_profesor_fk        INT,
    IN  p_requisitos_previos    TEXT,
    IN  p_lo_que_aprenderas     TEXT,
    IN  p_para_quien_es         TEXT,
    IN  p_idioma                VARCHAR(50),
    IN  p_estado_activo         TINYINT,
    IN  p_creado_por            INT,
    OUT p_id_nuevo              INT
)
BEGIN
    INSERT INTO cursos (
        titulo_curso, resumen_corto, descripcion_larga, imagen_portada,
        video_trailer_url, precio, precio_descuento, nivel_dificultad,
        total_horas, id_categoria_fk, id_profesor_fk,
        requisitos_previos, lo_que_aprenderas, para_quien_es,
        idioma, estado_activo, modificado_por
    ) VALUES (
        p_titulo_curso, p_resumen_corto, p_descripcion_larga, p_imagen_portada,
        p_video_trailer_url, p_precio, p_precio_descuento, p_nivel_dificultad,
        p_total_horas, p_id_categoria_fk, p_id_profesor_fk,
        p_requisitos_previos, p_lo_que_aprenderas, p_para_quien_es,
        p_idioma, p_estado_activo, p_creado_por
    );
    SET p_id_nuevo = LAST_INSERT_ID();
END$$
DELIMITER ;

-- ============================================================
-- PROCEDIMIENTO 4: sp_actualizar_curso
-- Actualiza los datos de un curso existente
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_actualizar_curso`;

DELIMITER $$
CREATE PROCEDURE `sp_actualizar_curso`(
    IN  p_id_curso              INT,
    IN  p_titulo_curso          VARCHAR(200),
    IN  p_resumen_corto         VARCHAR(500),
    IN  p_descripcion_larga     LONGTEXT,
    IN  p_imagen_portada        VARCHAR(500),
    IN  p_video_trailer_url     VARCHAR(500),
    IN  p_precio                DECIMAL(10,2),
    IN  p_precio_descuento      DECIMAL(10,2),
    IN  p_nivel_dificultad      ENUM('principiante','intermedio','avanzado'),
    IN  p_total_horas           DECIMAL(5,1),
    IN  p_id_categoria_fk       INT,
    IN  p_id_profesor_fk        INT,
    IN  p_requisitos_previos    TEXT,
    IN  p_lo_que_aprenderas     TEXT,
    IN  p_para_quien_es         TEXT,
    IN  p_idioma                VARCHAR(50),
    IN  p_estado_activo         TINYINT,
    IN  p_modificado_por        INT
)
BEGIN
    UPDATE cursos SET
        titulo_curso        = p_titulo_curso,
        resumen_corto       = p_resumen_corto,
        descripcion_larga   = p_descripcion_larga,
        imagen_portada      = p_imagen_portada,
        video_trailer_url   = p_video_trailer_url,
        precio              = p_precio,
        precio_descuento    = p_precio_descuento,
        nivel_dificultad    = p_nivel_dificultad,
        total_horas         = p_total_horas,
        id_categoria_fk     = p_id_categoria_fk,
        id_profesor_fk      = p_id_profesor_fk,
        requisitos_previos  = p_requisitos_previos,
        lo_que_aprenderas   = p_lo_que_aprenderas,
        para_quien_es       = p_para_quien_es,
        idioma              = p_idioma,
        estado_activo       = p_estado_activo,
        modificado_por      = p_modificado_por,
        fecha_actualizacion = NOW()
    WHERE id_curso_pk = p_id_curso;
END$$
DELIMITER ;

-- ============================================================
-- PROCEDIMIENTO 5: sp_eliminar_curso (Soft Delete)
-- Marca el curso como inactivo (NO lo borra físicamente)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_eliminar_curso`;

DELIMITER $$
CREATE PROCEDURE `sp_eliminar_curso`(
    IN p_id_curso       INT,
    IN p_eliminado_por  INT
)
BEGIN
    UPDATE cursos
    SET estado_activo    = 0,
        modificado_por   = p_eliminado_por,
        fecha_actualizacion = NOW()
    WHERE id_curso_pk = p_id_curso;

    -- Registrar en log
    INSERT INTO log_actividad_usuario (id_usuario_fk, tipo_accion, descripcion_accion, direccion_ip)
    VALUES (p_eliminado_por, 'ELIMINAR_CURSO',
            CONCAT('Curso ID: ', p_id_curso, ' desactivado (soft delete)'), '0.0.0.0');
END$$
DELIMITER ;
