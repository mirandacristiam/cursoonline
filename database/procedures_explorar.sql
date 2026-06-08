-- ============================================================
-- Stored Procedure — Explorar Cursos Disponibles
-- EduTech Academy
-- ============================================================

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_obtener_cursos_disponibles_estudiante`(
    IN p_id_usuario INT,
    IN p_filtro_categoria INT,
    IN p_filtro_nivel VARCHAR(50),
    IN p_filtro_buscar VARCHAR(100)
)
BEGIN
    DECLARE v_where TEXT DEFAULT 'WHERE c.estado_activo = 1';
    DECLARE v_params TEXT DEFAULT '';

    -- Construir WHERE dinámico
    IF p_filtro_categoria > 0 THEN
        SET v_where = CONCAT(v_where, ' AND c.id_categoria_fk = ', p_filtro_categoria);
    END IF;
    IF p_filtro_nivel IS NOT NULL AND p_filtro_nivel != '' THEN
        SET v_where = CONCAT(v_where, ' AND c.nivel_dificultad = ''', REPLACE(p_filtro_nivel, '''', ''''''), '''');
    END IF;
    IF p_filtro_buscar IS NOT NULL AND p_filtro_buscar != '' THEN
        SET v_where = CONCAT(v_where, ' AND (c.titulo_curso LIKE ''%', REPLACE(p_filtro_buscar, '''', ''''''), '%'' OR c.resumen_corto LIKE ''%', REPLACE(p_filtro_buscar, '''', ''''''), '%'')');
    END IF;

    -- Excluir cursos donde el estudiante ya tiene inscripción activa
    SET v_where = CONCAT(v_where, '
        AND NOT EXISTS (
            SELECT 1 FROM inscripciones i
            WHERE i.id_curso_fk = c.id_curso_pk
              AND i.id_usuario_fk = ', p_id_usuario, '
              AND i.estado_activo = 1
        )');

    SET @sql = CONCAT('
        SELECT c.id_curso_pk, c.titulo_curso, c.resumen_corto, c.imagen_portada,
               c.precio, c.precio_con_descuento, c.nivel_dificultad,
               c.total_horas, c.numero_estudiantes, c.calificacion_promedio,
               cat.nombre_categoria, cat.icono_categoria, cat.color_categoria,
               u.primer_nombre AS prof_nombre, u.primer_apellido AS prof_apellido
        FROM cursos c
        JOIN categorias_curso cat ON c.id_categoria_fk = cat.id_categoria_pk
        LEFT JOIN usuarios u ON c.id_profesor_fk = u.id_usuario_pk
    ', v_where, '
        ORDER BY c.calificacion_promedio DESC, c.titulo_curso ASC
    ');

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;
