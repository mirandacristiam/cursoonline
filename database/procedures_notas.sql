-- ============================================================
-- Stored Procedure — Notas del Estudiante (Evaluaciones)
-- EduTech Academy
-- ============================================================
-- Retorna todos los intentos de evaluación del estudiante
-- agrupados por curso y módulo, incluyendo evaluaciones
-- que aún no ha intentado (con datos NULL en intento).
-- ============================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `sp_obtener_evaluaciones_notas`(
    IN p_id_usuario INT
)
BEGIN
    SELECT 
        cur.id_curso_pk,
        cur.titulo_curso,
        e.id_evaluacion_pk,
        e.titulo_evaluacion,
        e.puntaje_maximo        AS eval_puntaje_maximo,
        e.puntaje_minimo_aprobacion AS eval_puntaje_minimo,
        ie.id_intento_pk,
        ie.numero_intento,
        ie.puntaje_obtenido,
        ie.estado_aprobado,
        ie.fecha_fin            AS fecha_intento,
        i.id_inscripcion_pk,
        m.orden_modulo,
        m.titulo_modulo
    FROM inscripciones i
    JOIN cursos cur ON i.id_curso_fk = cur.id_curso_pk
    JOIN modulos_curso m ON m.id_curso_fk = cur.id_curso_pk AND m.estado_activo = 1
    JOIN evaluaciones e ON e.id_curso_fk = cur.id_curso_pk
        AND e.orden_evaluacion = m.orden_modulo
        AND e.estado_activo = 1
    LEFT JOIN intentos_evaluacion ie ON ie.id_evaluacion_fk = e.id_evaluacion_pk
        AND ie.id_inscripcion_fk = i.id_inscripcion_pk
        AND ie.estado_activo = 1
    WHERE i.id_usuario_fk = p_id_usuario
      AND i.estado_activo = 1
      AND i.estado_inscripcion IN ('activa','completada')
    ORDER BY cur.titulo_curso, m.orden_modulo, ie.numero_intento DESC;
END$$

DELIMITER ;
