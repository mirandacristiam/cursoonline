-- ============================================================
-- /cursoonline/database/procedures_admin_cursos.sql
-- Procedimientos almacenados para gestión admin de cursos
-- EduTech Academy — Motor: MySQL 8.x
-- ============================================================

-- 1. Listar cursos (paginado con filtros)
DROP PROCEDURE IF EXISTS sp_admin_listar_cursos;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_cursos(
    IN p_busqueda   VARCHAR(255),
    IN p_id_categoria INT,
    IN p_estado     VARCHAR(20),
    IN p_pagina     INT,
    IN p_por_pagina INT,
    OUT p_total     INT
)
BEGIN
    DECLARE v_off INT DEFAULT 0;
    SET v_off = (p_pagina - 1) * p_por_pagina;

    SELECT SQL_CALC_FOUND_ROWS c.*, cat.nombre_categoria, cat.color_categoria,
           u.primer_nombre AS prof_nombre, u.primer_apellido AS prof_apellido,
           (SELECT COUNT(*) FROM inscripciones WHERE id_curso_fk = c.id_curso_pk AND estado_activo = 1) AS total_inscripciones
    FROM cursos c
    LEFT JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN usuarios u          ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE (p_busqueda = '' OR c.titulo_curso LIKE CONCAT('%', p_busqueda, '%'))
      AND (p_id_categoria = 0 OR c.id_categoria_fk = p_id_categoria)
      AND (p_estado = 'todos' OR (p_estado = 'activo' AND c.estado_activo = 1) OR (p_estado = 'inactivo' AND c.estado_activo = 0))
    ORDER BY c.fecha_creacion DESC
    LIMIT v_off, p_por_pagina;
    SELECT FOUND_ROWS() INTO p_total;
END$$
DELIMITER ;

-- 2. Obtener curso completo para edición
DROP PROCEDURE IF EXISTS sp_admin_obtener_curso;
DELIMITER $$
CREATE PROCEDURE sp_admin_obtener_curso(IN p_id_curso INT)
BEGIN
    SELECT c.*,
           cat.nombre_categoria, cat.color_categoria,
           u.primer_nombre AS profesor_nombre, u.primer_apellido AS profesor_apellido
    FROM cursos c
    LEFT JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN usuarios u ON u.id_usuario_pk = c.id_profesor_fk
    WHERE c.id_curso_pk = p_id_curso;
END$$
DELIMITER ;

-- 3. Guardar curso (insert o update)
DROP PROCEDURE IF EXISTS sp_admin_guardar_curso;
DELIMITER $$
CREATE PROCEDURE sp_admin_guardar_curso(
    IN p_id_curso              INT,
    IN p_titulo_curso          VARCHAR(255),
    IN p_resumen_corto         TEXT,
    IN p_descripcion_detallada LONGTEXT,
    IN p_imagen_portada        VARCHAR(255),
    IN p_video_presentacion    VARCHAR(500),
    IN p_tipo_video            VARCHAR(50),
    IN p_precio                DECIMAL(12,2),
    IN p_precio_con_descuento  DECIMAL(12,2),
    IN p_nivel_dificultad      VARCHAR(50),
    IN p_total_horas           INT,
    IN p_total_clases_estimado INT,
    IN p_duracion_meses        TINYINT,
    IN p_id_categoria_fk       INT,
    IN p_id_profesor_fk        INT,
    IN p_lenguaje_programacion VARCHAR(100),
    IN p_requisitos_previos    TEXT,
    IN p_certificado_disponible TINYINT,
    IN p_areas_laborales       TEXT,
    IN p_titulo_que_otorga     VARCHAR(255),
    IN p_nivel_formacion       VARCHAR(100),
    IN p_metodologia           TEXT,
    IN p_para_quien_es         TEXT,
    IN p_estado_activo         TINYINT,
    IN p_modificado_por        INT,
    OUT p_id_nuevo             INT
)
BEGIN
    IF p_id_curso > 0 THEN
        -- UPDATE
        UPDATE cursos SET
            titulo_curso          = p_titulo_curso,
            resumen_corto         = p_resumen_corto,
            descripcion_detallada = p_descripcion_detallada,
            imagen_portada        = p_imagen_portada,
            video_presentacion    = p_video_presentacion,
            tipo_video            = p_tipo_video,
            precio                = p_precio,
            precio_con_descuento  = p_precio_con_descuento,
            nivel_dificultad      = p_nivel_dificultad,
            total_horas           = p_total_horas,
            total_clases_estimado = p_total_clases_estimado,
            duracion_meses        = p_duracion_meses,
            id_categoria_fk       = p_id_categoria_fk,
            id_profesor_fk        = p_id_profesor_fk,
            lenguaje_programacion = p_lenguaje_programacion,
            requisitos_previos    = p_requisitos_previos,
            certificado_disponible = p_certificado_disponible,
            areas_laborales       = p_areas_laborales,
            titulo_que_otorga     = p_titulo_que_otorga,
            nivel_formacion       = p_nivel_formacion,
            metodologia           = p_metodologia,
            para_quien_es         = p_para_quien_es,
            estado_activo         = p_estado_activo,
            modificado_por        = p_modificado_por,
            fecha_modificacion    = NOW()
        WHERE id_curso_pk = p_id_curso;
        SET p_id_nuevo = p_id_curso;
    ELSE
        -- INSERT
        INSERT INTO cursos (
            titulo_curso, resumen_corto, descripcion_detallada, imagen_portada,
            video_presentacion, tipo_video, precio, precio_con_descuento,
            nivel_dificultad, total_horas, total_clases_estimado, duracion_meses,
            id_categoria_fk, id_profesor_fk, lenguaje_programacion,
            requisitos_previos, certificado_disponible,
            areas_laborales, titulo_que_otorga, nivel_formacion, metodologia,
            para_quien_es, estado_activo, modificado_por
        ) VALUES (
            p_titulo_curso, p_resumen_corto, p_descripcion_detallada, p_imagen_portada,
            p_video_presentacion, IFNULL(p_tipo_video, 'youtube'), p_precio, p_precio_con_descuento,
            p_nivel_dificultad, p_total_horas, p_total_clases_estimado, p_duracion_meses,
            p_id_categoria_fk, p_id_profesor_fk, p_lenguaje_programacion,
            p_requisitos_previos, p_certificado_disponible,
            p_areas_laborales, p_titulo_que_otorga, p_nivel_formacion, p_metodologia,
            p_para_quien_es, p_estado_activo, p_modificado_por
        );
        SET p_id_nuevo = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- 4. Cambiar estado (activar/desactivar)
DROP PROCEDURE IF EXISTS sp_admin_cambiar_estado_curso;
DELIMITER $$
CREATE PROCEDURE sp_admin_cambiar_estado_curso(
    IN p_id_curso        INT,
    IN p_nuevo_estado    TINYINT,
    IN p_modificado_por  INT
)
BEGIN
    UPDATE cursos
    SET estado_activo     = p_nuevo_estado,
        modificado_por    = p_modificado_por,
        fecha_modificacion = NOW()
    WHERE id_curso_pk = p_id_curso;
END$$
DELIMITER ;

-- 5. Eliminar curso (soft delete)
DROP PROCEDURE IF EXISTS sp_admin_eliminar_curso;
DELIMITER $$
CREATE PROCEDURE sp_admin_eliminar_curso(
    IN p_id_curso       INT,
    IN p_eliminado_por  INT
)
BEGIN
    UPDATE cursos
    SET estado_activo     = 0,
        modificado_por    = p_eliminado_por,
        fecha_modificacion = NOW()
    WHERE id_curso_pk = p_id_curso;
END$$
DELIMITER ;

-- 6. Estadísticas de un curso
DROP PROCEDURE IF EXISTS sp_admin_estadisticas_curso;
DELIMITER $$
CREATE PROCEDURE sp_admin_estadisticas_curso(IN p_id_curso INT)
BEGIN
    SELECT
        (SELECT COUNT(*) FROM inscripciones WHERE id_curso_fk = p_id_curso AND estado_activo = 1) AS total_inscripciones,
        (SELECT COUNT(*) FROM inscripciones WHERE id_curso_fk = p_id_curso AND estado_inscripcion = 'activa' AND estado_activo = 1) AS inscripciones_activas,
        (SELECT COUNT(*) FROM inscripciones WHERE id_curso_fk = p_id_curso AND estado_inscripcion = 'completada' AND estado_activo = 1) AS inscripciones_completadas,
        (SELECT COUNT(*) FROM inscripciones WHERE id_curso_fk = p_id_curso AND estado_inscripcion = 'cancelada' AND estado_activo = 1) AS inscripciones_canceladas,
        (SELECT COALESCE(SUM(monto_pagado), 0) FROM inscripciones WHERE id_curso_fk = p_id_curso AND estado_activo = 1) AS ingresos_totales,
        (SELECT COALESCE(AVG(porcentaje_progreso), 0) FROM inscripciones WHERE id_curso_fk = p_id_curso AND estado_inscripcion IN ('activa','completada') AND estado_activo = 1) AS progreso_promedio,
        (SELECT COUNT(*) FROM modulos_curso WHERE id_curso_fk = p_id_curso AND estado_activo = 1) AS total_modulos,
        (SELECT COUNT(*) FROM clases_curso cl INNER JOIN modulos_curso m ON m.id_modulo_pk = cl.id_modulo_fk WHERE m.id_curso_fk = p_id_curso AND cl.estado_activo = 1 AND m.estado_activo = 1) AS total_clases,
        (SELECT COUNT(*) FROM evaluaciones WHERE id_curso_fk = p_id_curso AND estado_activo = 1) AS total_evaluaciones;
END$$
DELIMITER ;

-- 7. Listar categorías activas
DROP PROCEDURE IF EXISTS sp_admin_listar_categorias;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_categorias()
BEGIN
    SELECT id_categoria_pk, nombre_categoria, icono_categoria, color_categoria
    FROM categorias_curso
    WHERE estado_activo = 1
    ORDER BY nombre_categoria;
END$$
DELIMITER ;

-- 8. Listar profesores activos
DROP PROCEDURE IF EXISTS sp_admin_listar_profesores;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_profesores()
BEGIN
    SELECT id_usuario_pk, primer_nombre, primer_apellido, correo_electronico, foto_perfil
    FROM usuarios
    WHERE id_rol_fk = 2 AND estado_activo = 1
    ORDER BY primer_apellido, primer_nombre;
END$$
DELIMITER ;

-- 9. Listar módulos de un curso con conteo de clases
DROP PROCEDURE IF EXISTS sp_admin_listar_modulos;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_modulos(IN p_id_curso INT)
BEGIN
    SELECT m.*,
           (SELECT COUNT(*) FROM clases_curso WHERE id_modulo_fk = m.id_modulo_pk AND estado_activo = 1) AS total_clases
    FROM modulos_curso m
    WHERE m.id_curso_fk = p_id_curso AND m.estado_activo = 1
    ORDER BY m.orden_modulo;
END$$
DELIMITER ;

-- 10. Listar clases de un módulo
DROP PROCEDURE IF EXISTS sp_admin_listar_clases;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_clases(IN p_id_modulo INT)
BEGIN
    SELECT *
    FROM clases_curso
    WHERE id_modulo_fk = p_id_modulo AND estado_activo = 1
    ORDER BY orden_clase;
END$$
DELIMITER ;

-- 11. Guardar módulo (insert o update)
DROP PROCEDURE IF EXISTS sp_admin_guardar_modulo;
DELIMITER $$
CREATE PROCEDURE sp_admin_guardar_modulo(
    IN p_id_modulo      INT,
    IN p_id_curso_fk    INT,
    IN p_titulo_modulo  VARCHAR(255),
    IN p_descripcion_modulo TEXT,
    IN p_total_horas_modulo INT,
    IN p_orden_modulo   TINYINT,
    IN p_modificado_por INT,
    OUT p_id_nuevo      INT
)
BEGIN
    IF p_id_modulo > 0 THEN
        UPDATE modulos_curso SET
            titulo_modulo      = p_titulo_modulo,
            descripcion_modulo = p_descripcion_modulo,
            total_horas_modulo = p_total_horas_modulo,
            orden_modulo       = p_orden_modulo,
            modificado_por     = p_modificado_por,
            fecha_modificacion = NOW()
        WHERE id_modulo_pk = p_id_modulo;
        SET p_id_nuevo = p_id_modulo;
    ELSE
        INSERT INTO modulos_curso (id_curso_fk, titulo_modulo, descripcion_modulo, total_horas_modulo, orden_modulo, modificado_por)
        VALUES (p_id_curso_fk, p_titulo_modulo, p_descripcion_modulo, p_total_horas_modulo, p_orden_modulo, p_modificado_por);
        SET p_id_nuevo = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- 12. Eliminar módulo (soft delete)
DROP PROCEDURE IF EXISTS sp_admin_eliminar_modulo;
DELIMITER $$
CREATE PROCEDURE sp_admin_eliminar_modulo(
    IN p_id_modulo      INT,
    IN p_modificado_por INT
)
BEGIN
    UPDATE modulos_curso
    SET estado_activo     = 0,
        modificado_por    = p_modificado_por,
        fecha_modificacion = NOW()
    WHERE id_modulo_pk = p_id_modulo;
END$$
DELIMITER ;

-- 13. Guardar clase (insert o update)
DROP PROCEDURE IF EXISTS sp_admin_guardar_clase;
DELIMITER $$
CREATE PROCEDURE sp_admin_guardar_clase(
    IN p_id_clase        INT,
    IN p_id_modulo_fk    INT,
    IN p_titulo_clase    VARCHAR(255),
    IN p_descripcion_clase TEXT,
    IN p_url_video       VARCHAR(500),
    IN p_tipo_video      VARCHAR(50),
    IN p_duracion_minutos INT,
    IN p_orden_clase     TINYINT,
    IN p_es_clase_gratuita TINYINT,
    IN p_modificado_por  INT,
    OUT p_id_nuevo       INT
)
BEGIN
    IF p_id_clase > 0 THEN
        UPDATE clases_curso SET
            titulo_clase     = p_titulo_clase,
            descripcion_clase = p_descripcion_clase,
            url_video        = p_url_video,
            tipo_video       = IFNULL(p_tipo_video, 'youtube'),
            duracion_minutos = p_duracion_minutos,
            orden_clase      = p_orden_clase,
            es_clase_gratuita = p_es_clase_gratuita,
            modificado_por   = p_modificado_por,
            fecha_modificacion = NOW()
        WHERE id_clase_pk = p_id_clase;
        SET p_id_nuevo = p_id_clase;
    ELSE
        INSERT INTO clases_curso (id_modulo_fk, titulo_clase, descripcion_clase, url_video, tipo_video, duracion_minutos, orden_clase, es_clase_gratuita, modificado_por)
        VALUES (p_id_modulo_fk, p_titulo_clase, p_descripcion_clase, p_url_video, IFNULL(p_tipo_video, 'youtube'), p_duracion_minutos, p_orden_clase, p_es_clase_gratuita, p_modificado_por);
        SET p_id_nuevo = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- 14. Eliminar clase (soft delete)
DROP PROCEDURE IF EXISTS sp_admin_eliminar_clase;
DELIMITER $$
CREATE PROCEDURE sp_admin_eliminar_clase(
    IN p_id_clase        INT,
    IN p_modificado_por  INT
)
BEGIN
    UPDATE clases_curso
    SET estado_activo     = 0,
        modificado_por    = p_modificado_por,
        fecha_modificacion = NOW()
    WHERE id_clase_pk = p_id_clase;
END$$
DELIMITER ;

-- 15. Listar competencias de un curso
DROP PROCEDURE IF EXISTS sp_admin_listar_competencias;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_competencias(IN p_id_curso INT)
BEGIN
    SELECT *
    FROM competencias_curso
    WHERE id_curso_fk = p_id_curso AND estado_activo = 1
    ORDER BY orden_visualizacion;
END$$
DELIMITER ;

-- 16. Guardar competencia (insert o update)
DROP PROCEDURE IF EXISTS sp_admin_guardar_competencia;
DELIMITER $$
CREATE PROCEDURE sp_admin_guardar_competencia(
    IN p_id_competencia      INT,
    IN p_id_curso_fk         INT,
    IN p_descripcion_competencia TEXT,
    IN p_icono_competencia   VARCHAR(100),
    IN p_orden_visualizacion TINYINT,
    IN p_modificado_por      INT,
    OUT p_id_nuevo           INT
)
BEGIN
    IF p_id_competencia > 0 THEN
        UPDATE competencias_curso SET
            descripcion_competencia = p_descripcion_competencia,
            icono_competencia       = p_icono_competencia,
            orden_visualizacion     = p_orden_visualizacion,
            modificado_por          = p_modificado_por,
            fecha_modificacion      = NOW()
        WHERE id_competencia_pk = p_id_competencia;
        SET p_id_nuevo = p_id_competencia;
    ELSE
        INSERT INTO competencias_curso (id_curso_fk, descripcion_competencia, icono_competencia, orden_visualizacion, modificado_por)
        VALUES (p_id_curso_fk, p_descripcion_competencia, p_icono_competencia, p_orden_visualizacion, p_modificado_por);
        SET p_id_nuevo = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- 17. Eliminar competencia (soft delete)
DROP PROCEDURE IF EXISTS sp_admin_eliminar_competencia;
DELIMITER $$
CREATE PROCEDURE sp_admin_eliminar_competencia(
    IN p_id_competencia  INT,
    IN p_modificado_por  INT
)
BEGIN
    UPDATE competencias_curso
    SET estado_activo     = 0,
        modificado_por    = p_modificado_por,
        fecha_modificacion = NOW()
    WHERE id_competencia_pk = p_id_competencia;
END$$
DELIMITER ;

-- ============================================================
-- RESUMEN: 17 procedimientos para admin de cursos
-- sp_admin_listar_cursos, _obtener_curso, _guardar_curso,
-- _cambiar_estado_curso, _eliminar_curso, _estadisticas_curso,
-- _listar_categorias, _listar_profesores, _listar_modulos,
-- _listar_clases, _guardar_modulo, _eliminar_modulo,
-- _guardar_clase, _eliminar_clase, _listar_competencias,
-- _guardar_competencia, _eliminar_competencia
-- ============================================================
