-- /cursoonline/database/new_procedures.sql
-- ============================================================
-- Nuevas columnas en tabla cursos + Procedimientos Almacenados
-- para la página de detalle de curso — EduTech Academy
-- Motor: MySQL 8.x | Charset: utf8mb4
-- ============================================================
-- INSTRUCCIONES: Ejecutar este archivo completo en MySQL/phpMyAdmin
-- antes de usar detalle.php con las nuevas secciones.
-- ============================================================

USE `db_edutechacademy`;

-- ============================================================
-- PASO 1: Agregar nuevas columnas a la tabla cursos
-- NOTA: Se ejecutan por separado para compatibilidad MySQL 5.7+
-- Si ya existe la columna puedes omitir esa instrucción.
-- ============================================================

ALTER TABLE `cursos`
    ADD COLUMN `areas_laborales`   TEXT         NULL
        COMMENT 'Áreas donde puede desempeñarse laboralmente (texto o JSON de lista)'
        AFTER `requisitos_previos`;

ALTER TABLE `cursos`
    ADD COLUMN `titulo_que_otorga` VARCHAR(255) NULL
        COMMENT 'Título o certificado que otorga el curso'
        AFTER `areas_laborales`;

ALTER TABLE `cursos`
    ADD COLUMN `nivel_formacion`   VARCHAR(100) NULL
        COMMENT 'Nivel de formación: Técnico, Tecnólogo, Profesional, etc.'
        AFTER `titulo_que_otorga`;

ALTER TABLE `cursos`
    ADD COLUMN `metodologia`       TEXT         NULL
        COMMENT 'Descripción de la metodología del curso (clases asíncronas, proyectos, etc.)'
        AFTER `nivel_formacion`;

ALTER TABLE `cursos`
    ADD COLUMN `para_quien_es`     TEXT         NULL
        COMMENT 'A quién va dirigido el curso (texto o JSON de lista)'
        AFTER `metodologia`;

-- ============================================================
-- PASO 2: Procedimiento sp_obtener_detalle_curso
-- Retorna todos los datos del curso para la página de detalle,
-- incluyendo información del instructor y categoría.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_obtener_detalle_curso;

DELIMITER //

CREATE PROCEDURE sp_obtener_detalle_curso(
    IN p_id_curso INT UNSIGNED
)
BEGIN
    -- Datos principales del curso con instructor y categoría
    SELECT
        c.id_curso_pk,
        c.titulo_curso,
        c.resumen_corto,
        c.descripcion_detallada,
        c.imagen_portada,
        c.video_presentacion,
        c.tipo_video,
        c.total_horas,
        c.total_clases_estimado,
        c.duracion_meses,
        c.precio,
        c.precio_con_descuento,
        c.nivel_dificultad,
        c.lenguaje_programacion,
        c.requisitos_previos,
        c.certificado_disponible,
        c.numero_estudiantes,
        c.calificacion_promedio,
        c.areas_laborales,
        c.titulo_que_otorga,
        c.nivel_formacion,
        c.metodologia,
        c.para_quien_es,
        -- Categoría
        cat.nombre_categoria,
        cat.icono_categoria,
        cat.color_categoria,
        -- Instructor
        u.primer_nombre    AS profesor_nombre,
        u.primer_apellido  AS profesor_apellido,
        u.foto_perfil      AS profesor_foto,
        u.correo_electronico AS profesor_email
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT  JOIN usuarios u           ON u.id_usuario_pk     = c.id_profesor_fk
    WHERE c.id_curso_pk = p_id_curso
      AND c.estado_activo = 1;
END //

DELIMITER ;

-- ============================================================
-- PASO 3: Procedimiento sp_obtener_modulos_curso
-- Retorna módulos y clases del curso para el temario.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_obtener_modulos_curso;

DELIMITER //

CREATE PROCEDURE sp_obtener_modulos_curso(
    IN p_id_curso INT UNSIGNED
)
BEGIN
    -- Módulos activos
    SELECT
        m.id_modulo_pk,
        m.titulo_modulo,
        m.descripcion_modulo,
        m.total_horas_modulo,
        m.orden_modulo,
        (SELECT COUNT(*)
         FROM clases_curso cc
         WHERE cc.id_modulo_fk = m.id_modulo_pk AND cc.estado_activo = 1) AS total_clases_modulo
    FROM modulos_curso m
    WHERE m.id_curso_fk = p_id_curso
      AND m.estado_activo = 1
    ORDER BY m.orden_modulo ASC;
END //

DELIMITER ;

-- ============================================================
-- PASO 4: Procedimiento sp_obtener_clases_curso
-- Retorna clases de todos los módulos del curso.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_obtener_clases_curso;

DELIMITER //

CREATE PROCEDURE sp_obtener_clases_curso(
    IN p_id_curso INT UNSIGNED
)
BEGIN
    SELECT
        cl.id_clase_pk,
        cl.id_modulo_fk,
        cl.titulo_clase,
        cl.descripcion_clase,
        cl.duracion_minutos,
        cl.orden_clase,
        cl.es_clase_gratuita
    FROM clases_curso cl
    INNER JOIN modulos_curso m ON m.id_modulo_pk = cl.id_modulo_fk
    WHERE m.id_curso_fk  = p_id_curso
      AND cl.estado_activo = 1
      AND m.estado_activo  = 1
    ORDER BY m.orden_modulo ASC, cl.orden_clase ASC;
END //

DELIMITER ;

-- ============================================================
-- PASO 5: Procedimiento sp_obtener_competencias_curso
-- Retorna competencias / lo que aprenderás del curso.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_obtener_competencias_curso;

DELIMITER //

CREATE PROCEDURE sp_obtener_competencias_curso(
    IN p_id_curso INT UNSIGNED
)
BEGIN
    SELECT
        id_competencia_pk,
        descripcion_competencia,
        icono_competencia,
        orden_visualizacion
    FROM competencias_curso
    WHERE id_curso_fk = p_id_curso
      AND estado_activo = 1
    ORDER BY orden_visualizacion ASC;
END //

DELIMITER ;

-- ============================================================
-- PASO 6: Procedimiento sp_verificar_inscripcion
-- Verifica si un usuario ya está inscrito en un curso.
-- ============================================================
DROP PROCEDURE IF EXISTS sp_verificar_inscripcion;

DELIMITER //

CREATE PROCEDURE sp_verificar_inscripcion(
    IN  p_id_usuario INT UNSIGNED,
    IN  p_id_curso   INT UNSIGNED,
    OUT p_inscrito   TINYINT(1)
)
BEGIN
    SELECT COUNT(*) INTO p_inscrito
    FROM inscripciones
    WHERE id_usuario_fk      = p_id_usuario
      AND id_curso_fk        = p_id_curso
      AND estado_inscripcion IN ('activa', 'completada')
      AND estado_activo      = 1;
END //

DELIMITER ;

/*
 * ============================================================
 * RESUMEN: new_procedures.sql
 * ============================================================
 * CAMBIOS EN TABLA cursos (ALTER TABLE):
 *   - areas_laborales      : Áreas de desempeño laboral
 *   - titulo_que_otorga    : Título o certificado del curso
 *   - nivel_formacion      : Nivel académico (Técnico, Profesional, etc.)
 *   - metodologia          : Descripción de la metodología
 *   - para_quien_es        : Dirigido a quién
 *
 * NUEVOS PROCEDIMIENTOS:
 *   1. sp_obtener_detalle_curso   : Datos completos del curso + instructor + categoría
 *   2. sp_obtener_modulos_curso   : Módulos con conteo de clases
 *   3. sp_obtener_clases_curso    : Clases de todos los módulos
 *   4. sp_obtener_competencias_curso : Competencias / lo que aprenderás
 *   5. sp_verificar_inscripcion   : Verifica si el usuario ya está inscrito
 * ============================================================
 */
