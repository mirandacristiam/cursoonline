-- ============================================================
-- /cursoonline/database/procedures_dashboard.sql
-- Procedimientos almacenados para el Dashboard del Admin
-- EduTech Academy
-- ============================================================

-- 1. Métricas generales del sistema
DROP PROCEDURE IF EXISTS sp_dashboard_metricas;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_metricas()
BEGIN
    DECLARE v_cursos       INT DEFAULT 0;
    DECLARE v_usuarios     INT DEFAULT 0;
    DECLARE v_estudiantes  INT DEFAULT 0;
    DECLARE v_ventas       DECIMAL(14,2) DEFAULT 0;
    DECLARE v_nuevos_mes   INT DEFAULT 0;
    DECLARE v_bloqueados   INT DEFAULT 0;
    DECLARE v_profesores   INT DEFAULT 0;
    DECLARE v_inscripciones_activas INT DEFAULT 0;
    DECLARE v_evaluaciones INT DEFAULT 0;

    SELECT COUNT(*) INTO v_cursos FROM cursos WHERE estado_activo = 1;
    SELECT COUNT(*) INTO v_usuarios FROM usuarios WHERE estado_activo = 1;
    SELECT COUNT(*) INTO v_estudiantes FROM usuarios WHERE id_rol_fk = 3 AND estado_activo = 1;
    SELECT COUNT(*) INTO v_profesores FROM usuarios WHERE id_rol_fk = 2 AND estado_activo = 1;
    SELECT COALESCE(SUM(monto_total),0) INTO v_ventas FROM transacciones_pago WHERE estado_transaccion = 'aprobada';
    SELECT COUNT(*) INTO v_nuevos_mes FROM usuarios WHERE MONTH(fecha_creacion) = MONTH(NOW()) AND YEAR(fecha_creacion) = YEAR(NOW());
    SELECT COUNT(*) INTO v_inscripciones_activas FROM inscripciones WHERE estado_inscripcion IN ('activa','completada') AND estado_activo = 1;
    SELECT COUNT(*) INTO v_evaluaciones FROM evaluaciones WHERE estado_activo = 1;
    SELECT COUNT(*) INTO v_bloqueados FROM log_intentos_fallidos WHERE estado_bloqueado = 1;

    SELECT
        v_cursos AS total_cursos,
        v_usuarios AS total_usuarios,
        v_estudiantes AS total_estudiantes,
        v_profesores AS total_profesores,
        v_ventas AS total_ventas,
        v_nuevos_mes AS nuevos_este_mes,
        v_inscripciones_activas AS inscripciones_activas,
        v_evaluaciones AS total_evaluaciones,
        v_bloqueados AS intentos_bloqueados;
END$$
DELIMITER ;

-- 2. Ventas mensuales (últimos 12 meses)
DROP PROCEDURE IF EXISTS sp_dashboard_ventas_mensuales;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_ventas_mensuales()
BEGIN
    SELECT
        DATE_FORMAT(fecha_transaccion, '%Y-%m') AS mes,
        DATE_FORMAT(fecha_transaccion, '%M %Y') AS mes_nombre,
        COUNT(*) AS total_transacciones,
        COALESCE(SUM(monto_total), 0) AS total_ingresos
    FROM transacciones_pago
    WHERE estado_transaccion = 'aprobada'
      AND fecha_transaccion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_transaccion, '%Y-%m'), DATE_FORMAT(fecha_transaccion, '%M %Y')
    ORDER BY mes ASC;
END$$
DELIMITER ;

-- 3. Inscripciones por mes (últimos 12 meses)
DROP PROCEDURE IF EXISTS sp_dashboard_inscripciones_mensuales;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_inscripciones_mensuales()
BEGIN
    SELECT
        DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes,
        DATE_FORMAT(fecha_creacion, '%M %Y') AS mes_nombre,
        COUNT(*) AS total_inscripciones
    FROM inscripciones
    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m'), DATE_FORMAT(fecha_creacion, '%M %Y')
    ORDER BY mes ASC;
END$$
DELIMITER ;

-- 4. Distribución de usuarios por rol
DROP PROCEDURE IF EXISTS sp_dashboard_distribucion_roles;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_distribucion_roles()
BEGIN
    SELECT
        r.id_rol_pk,
        r.nombre_rol,
        COUNT(u.id_usuario_pk) AS total_usuarios
    FROM roles r
    LEFT JOIN usuarios u ON u.id_rol_fk = r.id_rol_pk AND u.estado_activo = 1
    WHERE r.estado_activo = 1
    GROUP BY r.id_rol_pk, r.nombre_rol
    ORDER BY r.id_rol_pk;
END$$
DELIMITER ;

-- 5. Ingresos por categoría de curso
DROP PROCEDURE IF EXISTS sp_dashboard_ingresos_por_categoria;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_ingresos_por_categoria()
BEGIN
    SELECT
        cat.id_categoria_pk,
        cat.nombre_categoria,
        cat.color_categoria,
        cat.icono_categoria,
        COUNT(DISTINCT t.id_transaccion_pk) AS total_transacciones,
        COALESCE(SUM(t.monto_total), 0) AS total_ingresos,
        COUNT(DISTINCT c.id_curso_pk) AS total_cursos
    FROM categorias_curso cat
    LEFT JOIN cursos c ON c.id_categoria_fk = cat.id_categoria_pk AND c.estado_activo = 1
    LEFT JOIN transacciones_pago t ON t.id_curso_fk = c.id_curso_pk AND t.estado_transaccion = 'aprobada'
    WHERE cat.estado_activo = 1
    GROUP BY cat.id_categoria_pk, cat.nombre_categoria, cat.color_categoria, cat.icono_categoria
    ORDER BY total_ingresos DESC;
END$$
DELIMITER ;

-- 6. Cursos más populares por número de inscripciones
DROP PROCEDURE IF EXISTS sp_dashboard_cursos_populares;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_cursos_populares(IN p_limit INT)
BEGIN
    SELECT
        c.id_curso_pk,
        c.titulo_curso,
        c.precio,
        c.precio_con_descuento,
        c.calificacion_promedio,
        cat.nombre_categoria,
        cat.color_categoria,
        COUNT(i.id_inscripcion_pk) AS total_inscripciones
    FROM cursos c
    JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN inscripciones i ON i.id_curso_fk = c.id_curso_pk AND i.estado_activo = 1
    WHERE c.estado_activo = 1
    GROUP BY c.id_curso_pk, c.titulo_curso, c.precio, c.precio_con_descuento,
             c.calificacion_promedio, cat.nombre_categoria, cat.color_categoria
    ORDER BY total_inscripciones DESC
    LIMIT p_limit;
END$$
DELIMITER ;

-- 7. Últimas transacciones
DROP PROCEDURE IF EXISTS sp_dashboard_ultimas_transacciones;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_ultimas_transacciones(IN p_limit INT)
BEGIN
    SELECT
        t.id_transaccion_pk,
        t.numero_referencia,
        t.monto_total,
        t.estado_transaccion,
        t.fecha_transaccion,
        c.titulo_curso,
        u.primer_nombre,
        u.primer_apellido,
        u.correo_electronico,
        mp.nombre_medio_pago
    FROM transacciones_pago t
    JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
    JOIN usuarios u ON u.id_usuario_pk = t.id_usuario_fk
    LEFT JOIN medios_pago mp ON mp.id_medio_pago_pk = t.id_medio_pago_fk
    ORDER BY t.fecha_transaccion DESC
    LIMIT p_limit;
END$$
DELIMITER ;

-- 8. Últimos usuarios registrados
DROP PROCEDURE IF EXISTS sp_dashboard_ultimos_usuarios;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_ultimos_usuarios(IN p_limit INT)
BEGIN
    SELECT
        u.id_usuario_pk,
        u.primer_nombre,
        u.primer_apellido,
        u.correo_electronico,
        u.foto_perfil,
        u.fecha_registro,
        u.ultimo_acceso,
        r.id_rol_pk,
        r.nombre_rol
    FROM usuarios u
    JOIN roles r ON r.id_rol_pk = u.id_rol_fk
    WHERE u.estado_activo = 1
    ORDER BY u.fecha_registro DESC
    LIMIT p_limit;
END$$
DELIMITER ;

-- 9. Progreso general: estudiantes con mayor avance
DROP PROCEDURE IF EXISTS sp_dashboard_estudiantes_top;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_estudiantes_top(IN p_limit INT)
BEGIN
    SELECT
        u.id_usuario_pk,
        u.primer_nombre,
        u.primer_apellido,
        u.foto_perfil,
        COUNT(i.id_inscripcion_pk) AS cursos_inscritos,
        ROUND(AVG(i.porcentaje_progreso), 1) AS progreso_promedio,
        COUNT(CASE WHEN i.estado_inscripcion = 'completada' THEN 1 END) AS cursos_completados
    FROM usuarios u
    JOIN inscripciones i ON i.id_usuario_fk = u.id_usuario_pk AND i.estado_activo = 1
    WHERE u.id_rol_fk = 3 AND u.estado_activo = 1
    GROUP BY u.id_usuario_pk, u.primer_nombre, u.primer_apellido, u.foto_perfil
    ORDER BY progreso_promedio DESC
    LIMIT p_limit;
END$$
DELIMITER ;

-- 10. Evaluaciones: tasas de aprobación general
DROP PROCEDURE IF EXISTS sp_dashboard_estadisticas_evaluaciones;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_estadisticas_evaluaciones()
BEGIN
    SELECT
        COUNT(*) AS total_intentos,
        SUM(CASE WHEN estado_aprobado = 1 THEN 1 ELSE 0 END) AS intentos_aprobados,
        SUM(CASE WHEN estado_aprobado = 0 AND estado_intento = 'completado' THEN 1 ELSE 0 END) AS intentos_reprobados,
        ROUND(AVG(puntaje_obtenido), 1) AS puntaje_promedio
    FROM intentos_evaluacion
    WHERE estado_activo = 1 AND estado_intento = 'completado';
END$$
DELIMITER ;

-- ============================================================
-- RESUMEN: procedures_dashboard.sql
-- 10 SPs para métricas, ventas, inscripciones, roles,
-- categorías, cursos populares, transacciones, usuarios,
-- top estudiantes y evaluaciones.
-- ============================================================
