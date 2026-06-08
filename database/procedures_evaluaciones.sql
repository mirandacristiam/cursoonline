-- ============================================================
-- Stored Procedure — Evaluaciones del Estudiante por Módulo
-- EduTech Academy
-- ============================================================

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_obtener_evaluaciones_estudiante`(
    IN p_id_usuario INT
)
BEGIN
    -- Retorna cursos, módulos y evaluaciones con el progreso del estudiante
    SELECT 
        c.id_curso_pk, c.titulo_curso, c.imagen_portada,
        i.id_inscripcion_pk,
        m.id_modulo_pk, m.titulo_modulo, m.orden_modulo,
        e.id_evaluacion_pk, e.titulo_evaluacion, e.descripcion_evaluacion,
        e.puntaje_maximo, e.puntaje_minimo_aprobacion,
        e.numero_clases_requeridas, e.orden_evaluacion,
        e.intentos_permitidos, e.tiempo_limite_minutos,
        -- Total de clases en el módulo
        (SELECT COUNT(*) FROM clases_curso cc
         WHERE cc.id_modulo_fk = m.id_modulo_pk AND cc.estado_activo = 1) AS total_clases_modulo,
        -- Clases completadas por el estudiante en el módulo
        (SELECT COUNT(*) FROM progreso_clases pc
         JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
         WHERE cc.id_modulo_fk = m.id_modulo_pk
           AND pc.id_inscripcion_fk = i.id_inscripcion_pk
           AND pc.estado_completada = 1) AS clases_completadas_modulo
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    JOIN modulos_curso m ON m.id_curso_fk = c.id_curso_pk
    LEFT JOIN evaluaciones e ON e.id_curso_fk = c.id_curso_pk
        AND e.estado_activo = 1
        AND e.orden_evaluacion = m.orden_modulo
    WHERE i.id_usuario_fk = p_id_usuario
      AND i.estado_activo = 1
      AND i.estado_inscripcion IN ('activa','completada')
      AND m.estado_activo = 1
    ORDER BY c.titulo_curso, m.orden_modulo, e.orden_evaluacion;
END$$
DELIMITER ;
