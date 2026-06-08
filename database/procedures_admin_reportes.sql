-- ============================================================
-- /cursoonline/database/procedures_admin_reportes.sql
-- Procedimientos para Reportes y Estadísticas Avanzadas
-- EduTech Academy — Motor: MySQL 8.x
-- ============================================================

-- 1. Reporte general del sistema (métricas completas)
DROP PROCEDURE IF EXISTS sp_reporte_metricas_generales;
DELIMITER $$
CREATE PROCEDURE sp_reporte_metricas_generales()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM usuarios WHERE estado_activo = 1) AS total_usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE id_rol_fk = 3 AND estado_activo = 1) AS total_estudiantes,
        (SELECT COUNT(*) FROM usuarios WHERE id_rol_fk = 2 AND estado_activo = 1) AS total_profesores,
        (SELECT COUNT(*) FROM cursos WHERE estado_activo = 1) AS total_cursos,
        (SELECT COUNT(*) FROM modulos_curso WHERE estado_activo = 1) AS total_modulos,
        (SELECT COUNT(*) FROM clases_curso WHERE estado_activo = 1) AS total_clases,
        (SELECT COUNT(*) FROM inscripciones WHERE estado_activo = 1 AND estado_inscripcion = 'activa') AS inscripciones_activas,
        (SELECT COUNT(*) FROM inscripciones WHERE estado_activo = 1 AND estado_inscripcion = 'completada') AS inscripciones_completadas,
        (SELECT COUNT(*) FROM evaluaciones WHERE estado_activo = 1) AS total_evaluaciones,
        (SELECT COUNT(*) FROM transacciones_pago WHERE estado_transaccion = 'aprobada') AS transacciones_aprobadas,
        (SELECT COALESCE(SUM(monto_total), 0) FROM transacciones_pago WHERE estado_transaccion = 'aprobada') AS ingresos_totales,
        (SELECT COUNT(*) FROM log_accesos WHERE tipo_accion = 'login_exitoso') AS total_logins_exitosos,
        (SELECT COUNT(*) FROM log_accesos WHERE tipo_accion = 'login_fallido') AS total_logins_fallidos;
END$$
DELIMITER ;

-- 2. Cursos con mejor rendimiento (inscripciones + ingresos)
DROP PROCEDURE IF EXISTS sp_reporte_cursos_top;
DELIMITER $$
CREATE PROCEDURE sp_reporte_cursos_top(IN p_limite INT)
BEGIN
    SELECT
        c.id_curso_pk, c.titulo_curso, c.precio, c.nivel_dificultad,
        cat.nombre_categoria, cat.color_categoria,
        COUNT(DISTINCT i.id_inscripcion_pk) AS total_inscripciones,
        COUNT(DISTINCT CASE WHEN i.estado_inscripcion = 'completada' THEN i.id_inscripcion_pk END) AS completados,
        COALESCE(SUM(t.monto_total), 0) AS ingresos_generados,
        ROUND(AVG(i.porcentaje_progreso), 1) AS progreso_promedio
    FROM cursos c
    JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN inscripciones i ON i.id_curso_fk = c.id_curso_pk AND i.estado_activo = 1
    LEFT JOIN transacciones_pago t ON t.id_curso_fk = c.id_curso_pk AND t.estado_transaccion = 'aprobada'
    WHERE c.estado_activo = 1
    GROUP BY c.id_curso_pk, c.titulo_curso, c.precio, c.nivel_dificultad,
             cat.nombre_categoria, cat.color_categoria
    ORDER BY total_inscripciones DESC, ingresos_generados DESC
    LIMIT p_limite;
END$$
DELIMITER ;

-- 3. Crecimiento mensual de usuarios (últimos 12 meses)
DROP PROCEDURE IF EXISTS sp_reporte_crecimiento_usuarios;
DELIMITER $$
CREATE PROCEDURE sp_reporte_crecimiento_usuarios()
BEGIN
    SELECT
        DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes,
        DATE_FORMAT(fecha_creacion, '%M %Y') AS mes_nombre,
        COUNT(*) AS nuevos_usuarios,
        SUM(CASE WHEN id_rol_fk = 3 THEN 1 ELSE 0 END) AS nuevos_estudiantes,
        SUM(CASE WHEN id_rol_fk = 2 THEN 1 ELSE 0 END) AS nuevos_profesores
    FROM usuarios
    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m'), DATE_FORMAT(fecha_creacion, '%M %Y')
    ORDER BY mes ASC;
END$$
DELIMITER ;

-- 4. Distribución geográfica de estudiantes
DROP PROCEDURE IF EXISTS sp_reporte_distribucion_geografica;
DELIMITER $$
CREATE PROCEDURE sp_reporte_distribucion_geografica()
BEGIN
    SELECT
        COALESCE(pais_residencia, 'Desconocido') AS pais,
        COUNT(*) AS total_estudiantes
    FROM usuarios
    WHERE id_rol_fk = 3 AND estado_activo = 1
    GROUP BY pais_residencia
    ORDER BY total_estudiantes DESC;
END$$
DELIMITER ;

-- 5. Progreso académico general
DROP PROCEDURE IF EXISTS sp_reporte_progreso_general;
DELIMITER $$
CREATE PROCEDURE sp_reporte_progreso_general()
BEGIN
    SELECT
        ROUND(AVG(porcentaje_progreso), 1) AS progreso_promedio,
        COUNT(CASE WHEN porcentaje_progreso >= 100 THEN 1 END) AS completados_al_100,
        COUNT(CASE WHEN porcentaje_progreso >= 75 AND porcentaje_progreso < 100 THEN 1 END) AS entre_75_y_99,
        COUNT(CASE WHEN porcentaje_progreso >= 50 AND porcentaje_progreso < 75 THEN 1 END) AS entre_50_y_74,
        COUNT(CASE WHEN porcentaje_progreso >= 25 AND porcentaje_progreso < 50 THEN 1 END) AS entre_25_y_49,
        COUNT(CASE WHEN porcentaje_progreso > 0 AND porcentaje_progreso < 25 THEN 1 END) AS entre_1_y_24,
        COUNT(CASE WHEN porcentaje_progreso = 0 THEN 1 END) AS sin_iniciar
    FROM inscripciones
    WHERE estado_activo = 1 AND estado_inscripcion IN ('activa', 'completada');
END$$
DELIMITER ;

-- ============================================================
-- RESUMEN: 5 procedimientos para reportes
-- ============================================================
