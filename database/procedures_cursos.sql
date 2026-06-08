-- ============================================================
-- /cursoonline/database/procedures_cursos.sql
-- Procedimientos Almacenados del Sistema de Cursos
-- EduTech Academy — Motor: MySQL 8.x
--
-- Incluye SP públicos (front-end) y de administración (panel).
-- Ejecutar sobre db_edutechacademy.
-- ============================================================

USE `db_edutechacademy`;

-- ################################################################
-- SECCIÓN 1: SP PÚBLICOS (Front-end del sitio)
-- ################################################################

-- ============================================================
-- sp_obtener_catalogo_cursos
-- Catálogo público con filtros: categoría y búsqueda textual.
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_obtener_catalogo_cursos`;
DELIMITER $$
CREATE PROCEDURE `sp_obtener_catalogo_cursos`(
    IN p_id_categoria INT,
    IN p_busqueda     VARCHAR(200)
)
BEGIN
    SELECT c.id_curso_pk, c.titulo_curso, c.resumen_corto,
           c.imagen_portada, c.precio, c.precio_con_descuento,
           c.nivel_dificultad, c.total_horas, c.calificacion_promedio,
           c.numero_estudiantes,
           cat.nombre_categoria, cat.icono_categoria, cat.color_categoria,
           u.primer_nombre AS profesor_nombre, u.primer_apellido AS profesor_apellido,
           u.foto_perfil AS profesor_foto
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT  JOIN usuarios u          ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE c.estado_activo = 1
      AND (p_id_categoria = 0 OR c.id_categoria_fk = p_id_categoria)
      AND (p_busqueda = ''
           OR c.titulo_curso   LIKE CONCAT('%', p_busqueda, '%')
           OR c.resumen_corto  LIKE CONCAT('%', p_busqueda, '%')
           OR cat.nombre_categoria LIKE CONCAT('%', p_busqueda, '%'))
    ORDER BY c.calificacion_promedio DESC, c.fecha_creacion DESC;
END$$
DELIMITER ;

-- ============================================================
-- sp_obtener_detalle_curso
-- Curso + módulos + clases + profesor para la página pública.
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_obtener_detalle_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_obtener_detalle_curso`(IN p_id_curso INT)
BEGIN
    SELECT c.id_curso_pk, c.titulo_curso, c.resumen_corto,
           c.descripcion_detallada, c.imagen_portada, c.video_presentacion,
           c.precio, c.precio_con_descuento, c.nivel_dificultad,
           c.total_horas, c.total_clases_estimado, c.calificacion_promedio,
           c.numero_estudiantes, c.requisitos_previos, c.para_quien_es,
           c.certificado_disponible, c.estado_activo, c.fecha_creacion,
           cat.nombre_categoria, cat.icono_categoria, cat.color_categoria,
           u.id_usuario_pk AS id_profesor, u.primer_nombre AS profesor_nombre,
           u.primer_apellido AS profesor_apellido, u.foto_perfil AS profesor_foto
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT  JOIN usuarios u          ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE c.id_curso_pk = p_id_curso AND c.estado_activo = 1;

    SELECT m.id_modulo_pk, m.titulo_modulo, m.orden_modulo,
           COUNT(cl.id_clase_pk) AS total_clases_modulo
    FROM modulos_curso m
    LEFT JOIN clases_curso cl ON cl.id_modulo_fk = m.id_modulo_pk AND cl.estado_activo = 1
    WHERE m.id_curso_fk = p_id_curso AND m.estado_activo = 1
    GROUP BY m.id_modulo_pk, m.titulo_modulo, m.orden_modulo
    ORDER BY m.orden_modulo;

    SELECT cl.id_clase_pk, cl.id_modulo_fk, cl.titulo_clase,
           cl.duracion_minutos, cl.tipo_video, cl.es_clase_gratuita, cl.orden_clase
    FROM clases_curso cl
    INNER JOIN modulos_curso m ON m.id_modulo_pk = cl.id_modulo_fk
    WHERE m.id_curso_fk = p_id_curso AND cl.estado_activo = 1 AND m.estado_activo = 1
    ORDER BY m.orden_modulo, cl.orden_clase;
END$$
DELIMITER ;

-- ============================================================
-- sp_crear_curso / sp_actualizar_curso / sp_eliminar_curso
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_crear_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_crear_curso`(
    IN p_titulo_curso VARCHAR(255), IN p_resumen_corto TEXT,
    IN p_descripcion_detallada LONGTEXT, IN p_imagen_portada VARCHAR(255),
    IN p_video_presentacion VARCHAR(500), IN p_precio DECIMAL(12,2),
    IN p_precio_con_descuento DECIMAL(12,2),
    IN p_nivel_dificultad ENUM('Principiante','Intermedio','Avanzado'),
    IN p_total_horas INT, IN p_id_categoria_fk INT,
    IN p_id_profesor_fk INT, IN p_requisitos_previos TEXT,
    IN p_para_quien_es TEXT, IN p_estado_activo TINYINT,
    IN p_creado_por INT, OUT p_id_nuevo INT
)
BEGIN
    INSERT INTO cursos (titulo_curso, resumen_corto, descripcion_detallada,
        imagen_portada, video_presentacion, precio, precio_con_descuento,
        nivel_dificultad, total_horas, id_categoria_fk, id_profesor_fk,
        requisitos_previos, para_quien_es, estado_activo, modificado_por)
    VALUES (p_titulo_curso, p_resumen_corto, p_descripcion_detallada,
        p_imagen_portada, p_video_presentacion, p_precio, p_precio_con_descuento,
        p_nivel_dificultad, p_total_horas, p_id_categoria_fk, p_id_profesor_fk,
        p_requisitos_previos, p_para_quien_es, p_estado_activo, p_creado_por);
    SET p_id_nuevo = LAST_INSERT_ID();
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_actualizar_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_actualizar_curso`(
    IN p_id_curso INT, IN p_titulo_curso VARCHAR(255),
    IN p_resumen_corto TEXT, IN p_descripcion_detallada LONGTEXT,
    IN p_imagen_portada VARCHAR(255), IN p_video_presentacion VARCHAR(500),
    IN p_precio DECIMAL(12,2), IN p_precio_con_descuento DECIMAL(12,2),
    IN p_nivel_dificultad ENUM('Principiante','Intermedio','Avanzado'),
    IN p_total_horas INT, IN p_id_categoria_fk INT,
    IN p_id_profesor_fk INT, IN p_requisitos_previos TEXT,
    IN p_para_quien_es TEXT, IN p_estado_activo TINYINT,
    IN p_modificado_por INT
)
BEGIN
    UPDATE cursos SET titulo_curso = p_titulo_curso,
        resumen_corto = p_resumen_corto,
        descripcion_detallada = p_descripcion_detallada,
        imagen_portada = p_imagen_portada,
        video_presentacion = p_video_presentacion,
        precio = p_precio, precio_con_descuento = p_precio_con_descuento,
        nivel_dificultad = p_nivel_dificultad, total_horas = p_total_horas,
        id_categoria_fk = p_id_categoria_fk, id_profesor_fk = p_id_profesor_fk,
        requisitos_previos = p_requisitos_previos,
        para_quien_es = p_para_quien_es, estado_activo = p_estado_activo,
        modificado_por = p_modificado_por,
        fecha_modificacion = NOW()
    WHERE id_curso_pk = p_id_curso;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_eliminar_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_eliminar_curso`(IN p_id_curso INT, IN p_eliminado_por INT)
BEGIN
    UPDATE cursos SET estado_activo = 0, modificado_por = p_eliminado_por,
           fecha_modificacion = NOW()
    WHERE id_curso_pk = p_id_curso;
    INSERT INTO log_actividad_usuario (id_usuario_fk, tipo_accion, descripcion_accion)
    VALUES (p_eliminado_por, 'ELIMINAR_CURSO',
            CONCAT('Curso ID: ', p_id_curso, ' desactivado'));
END$$
DELIMITER ;

-- ################################################################
-- SECCIÓN 2: SP DE ADMINISTRACIÓN (Panel /cursoonline/admin/)
-- ################################################################

-- ============================================================
-- sp_admin_obtener_curso — Datos completos del curso
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_obtener_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_obtener_curso`(IN p_id_curso INT)
BEGIN
    SELECT c.*, cat.nombre_categoria, cat.color_categoria,
           u.primer_nombre AS profesor_nombre, u.primer_apellido AS profesor_apellido
    FROM cursos c
    LEFT JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN usuarios u          ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE c.id_curso_pk = p_id_curso;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_estadisticas_curso — Métricas de detalle del curso
-- Usada en admin/cursos/ver.php
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_estadisticas_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_estadisticas_curso`(IN p_id_curso INT)
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

-- ============================================================
-- sp_admin_listar_modulos — Módulos del curso
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_listar_modulos`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_listar_modulos`(IN p_id_curso INT)
BEGIN
    SELECT m.*,
           (SELECT COUNT(*) FROM clases_curso WHERE id_modulo_fk = m.id_modulo_pk AND estado_activo = 1) AS total_clases
    FROM modulos_curso m
    WHERE m.id_curso_fk = p_id_curso AND m.estado_activo = 1
    ORDER BY m.orden_modulo;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_listar_clases — Clases de un módulo
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_listar_clases`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_listar_clases`(IN p_id_modulo INT)
BEGIN
    SELECT *
    FROM clases_curso
    WHERE id_modulo_fk = p_id_modulo AND estado_activo = 1
    ORDER BY orden_clase;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_listar_competencias
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_listar_competencias`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_listar_competencias`(IN p_id_curso INT)
BEGIN
    SELECT *
    FROM competencias_curso
    WHERE id_curso_fk = p_id_curso AND estado_activo = 1
    ORDER BY orden_visualizacion;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_guardar_modulo (INSERT / UPDATE)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_guardar_modulo`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_guardar_modulo`(
    IN p_id INT, IN p_curso INT, IN p_titulo VARCHAR(255),
    IN p_desc TEXT, IN p_horas INT, IN p_orden INT,
    IN p_usr INT, OUT p_nid INT
)
BEGIN
    IF p_id > 0 THEN
        UPDATE modulos_curso SET titulo_modulo = p_titulo,
            descripcion_modulo = p_desc, total_horas_modulo = p_horas,
            orden_modulo = p_orden, modificado_por = p_usr,
            fecha_modificacion = NOW()
        WHERE id_modulo_pk = p_id;
        SET p_nid = p_id;
    ELSE
        INSERT INTO modulos_curso (id_curso_fk, titulo_modulo, descripcion_modulo,
            total_horas_modulo, orden_modulo, modificado_por)
        VALUES (p_curso, p_titulo, p_desc, p_horas, p_orden, p_usr);
        SET p_nid = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_eliminar_modulo (soft delete)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_eliminar_modulo`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_eliminar_modulo`(IN p_id INT, IN p_usr INT)
BEGIN
    UPDATE modulos_curso SET estado_activo = 0, modificado_por = p_usr,
           fecha_modificacion = NOW()
    WHERE id_modulo_pk = p_id;
    UPDATE clases_curso SET estado_activo = 0
    WHERE id_modulo_fk = p_id;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_guardar_clase (INSERT / UPDATE)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_guardar_clase`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_guardar_clase`(
    IN p_id INT, IN p_modulo INT, IN p_titulo VARCHAR(255),
    IN p_desc TEXT, IN p_video VARCHAR(500), IN p_tipo_video VARCHAR(30),
    IN p_duracion INT, IN p_orden INT, IN p_gratis TINYINT,
    IN p_usr INT, OUT p_nid INT
)
BEGIN
    IF p_id > 0 THEN
        UPDATE clases_curso SET titulo_clase = p_titulo,
            descripcion_clase = p_desc, url_video = p_video,
            tipo_video = p_tipo_video, duracion_minutos = p_duracion,
            orden_clase = p_orden, es_clase_gratuita = p_gratis,
            modificado_por = p_usr, fecha_modificacion = NOW()
        WHERE id_clase_pk = p_id;
        SET p_nid = p_id;
    ELSE
        INSERT INTO clases_curso (id_modulo_fk, titulo_clase, descripcion_clase,
            url_video, tipo_video, duracion_minutos, orden_clase,
            es_clase_gratuita, modificado_por)
        VALUES (p_modulo, p_titulo, p_desc, p_video, p_tipo_video,
            p_duracion, p_orden, p_gratis, p_usr);
        SET p_nid = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_eliminar_clase (soft delete)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_eliminar_clase`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_eliminar_clase`(IN p_id INT, IN p_usr INT)
BEGIN
    UPDATE clases_curso SET estado_activo = 0, modificado_por = p_usr,
           fecha_modificacion = NOW()
    WHERE id_clase_pk = p_id;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_guardar_competencia (INSERT / UPDATE)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_guardar_competencia`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_guardar_competencia`(
    IN p_id INT, IN p_curso INT, IN p_desc TEXT,
    IN p_icono VARCHAR(100), IN p_orden INT,
    IN p_usr INT, OUT p_nid INT
)
BEGIN
    IF p_id > 0 THEN
        UPDATE competencias_curso SET descripcion_competencia = p_desc,
            icono_competencia = p_icono, orden_visualizacion = p_orden,
            modificado_por = p_usr, fecha_modificacion = NOW()
        WHERE id_competencia_pk = p_id;
        SET p_nid = p_id;
    ELSE
        INSERT INTO competencias_curso (id_curso_fk, descripcion_competencia,
            icono_competencia, orden_visualizacion, modificado_por)
        VALUES (p_curso, p_desc, p_icono, p_orden, p_usr);
        SET p_nid = LAST_INSERT_ID();
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_eliminar_competencia (soft delete)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_eliminar_competencia`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_eliminar_competencia`(IN p_id INT, IN p_usr INT)
BEGIN
    UPDATE competencias_curso SET estado_activo = 0, modificado_por = p_usr,
           fecha_modificacion = NOW()
    WHERE id_competencia_pk = p_id;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_cambiar_estado_curso — Activar / desactivar
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_cambiar_estado_curso`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_cambiar_estado_curso`(IN p_id INT, IN p_est TINYINT, IN p_usr INT)
BEGIN
    UPDATE cursos SET estado_activo = p_est, modificado_por = p_usr,
           fecha_modificacion = NOW()
    WHERE id_curso_pk = p_id;
END$$
DELIMITER ;

-- ============================================================
-- sp_admin_listar_cursos — Listado paginado con filtros
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_listar_cursos`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_listar_cursos`(
    IN p_busqueda VARCHAR(255), IN p_id_categoria INT,
    IN p_estado VARCHAR(20), IN p_pagina INT, IN p_por_pagina INT,
    OUT p_total INT
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

-- ============================================================
-- sp_admin_listar_categorias / sp_admin_listar_profesores
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_admin_listar_categorias`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_listar_categorias`()
BEGIN
    SELECT * FROM categorias_curso WHERE estado_activo = 1 ORDER BY nombre_categoria;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_admin_listar_profesores`;
DELIMITER $$
CREATE PROCEDURE `sp_admin_listar_profesores`()
BEGIN
    SELECT id_usuario_pk, primer_nombre, primer_apellido, correo_electronico
    FROM usuarios WHERE id_rol_fk = 2 AND estado_activo = 1
    ORDER BY primer_nombre;
END$$
DELIMITER ;
