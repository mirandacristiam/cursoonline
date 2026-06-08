-- ============================================================
-- /cursoonline/database/procedures_admin_seguridad.sql
-- Procedimientos para Seguridad, Auditoría y Logs
-- EduTech Academy — Motor: MySQL 8.x
-- ============================================================

-- 1. Actividad de usuarios (auditoría) con paginación
DROP PROCEDURE IF EXISTS sp_admin_actividad_usuarios;
DELIMITER $$
CREATE PROCEDURE sp_admin_actividad_usuarios(
    IN p_pagina     INT,
    IN p_por_pagina INT,
    IN p_tipo       VARCHAR(100),
    OUT p_total     INT
)
BEGIN
    DECLARE v_off INT DEFAULT 0;
    SET v_off = (p_pagina - 1) * p_por_pagina;

    SELECT SQL_CALC_FOUND_ROWS
        l.*, u.primer_nombre, u.primer_apellido, r.nombre_rol
    FROM log_actividad_usuario l
    JOIN usuarios u ON l.id_usuario_fk = u.id_usuario_pk
    JOIN roles r ON u.id_rol_fk = r.id_rol_pk
    WHERE (p_tipo = '' OR l.tipo_accion = p_tipo)
    ORDER BY l.fecha_actividad DESC
    LIMIT v_off, p_por_pagina;
    SELECT FOUND_ROWS() INTO p_total;
END$$
DELIMITER ;

-- 2. Accesos (logins/logouts) con paginación
DROP PROCEDURE IF EXISTS sp_admin_listar_accesos;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_accesos(
    IN p_pagina     INT,
    IN p_por_pagina INT,
    IN p_tipo       VARCHAR(50),
    OUT p_total     INT
)
BEGIN
    DECLARE v_off INT DEFAULT 0;
    SET v_off = (p_pagina - 1) * p_por_pagina;

    SELECT SQL_CALC_FOUND_ROWS
        a.*, u.primer_nombre, u.primer_apellido, r.nombre_rol
    FROM log_accesos a
    LEFT JOIN usuarios u ON a.id_usuario_fk = u.id_usuario_pk
    LEFT JOIN roles r ON u.id_rol_fk = r.id_rol_pk
    WHERE (p_tipo = '' OR a.tipo_accion = p_tipo)
    ORDER BY a.fecha_acceso DESC
    LIMIT v_off, p_por_pagina;
    SELECT FOUND_ROWS() INTO p_total;
END$$
DELIMITER ;

-- 3. Errores del sistema con paginación
DROP PROCEDURE IF EXISTS sp_admin_listar_errores;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_errores(
    IN p_pagina     INT,
    IN p_por_pagina INT,
    IN p_tipo       VARCHAR(100),
    OUT p_total     INT
)
BEGIN
    DECLARE v_off INT DEFAULT 0;
    SET v_off = (p_pagina - 1) * p_por_pagina;

    SELECT SQL_CALC_FOUND_ROWS *
    FROM log_errores_sistema
    WHERE (p_tipo = '' OR tipo_error = p_tipo)
    ORDER BY fecha_error DESC
    LIMIT v_off, p_por_pagina;
    SELECT FOUND_ROWS() INTO p_total;
END$$
DELIMITER ;

-- 4. IPs bloqueadas
DROP PROCEDURE IF EXISTS sp_admin_listar_ips_bloqueadas;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_ips_bloqueadas()
BEGIN
    SELECT ib.*, u.primer_nombre, u.primer_apellido
    FROM ips_bloqueadas ib
    LEFT JOIN usuarios u ON u.id_usuario_pk = ib.id_usuario_bloqueo_fk
    WHERE ib.estado_activo = 1
    ORDER BY ib.fecha_bloqueo DESC;
END$$
DELIMITER ;

-- 5. Intentos fallidos de login (fuerza bruta)
DROP PROCEDURE IF EXISTS sp_admin_intentos_fallidos;
DELIMITER $$
CREATE PROCEDURE sp_admin_intentos_fallidos(IN p_limite INT)
BEGIN
    SELECT *
    FROM log_intentos_fallidos
    WHERE estado_activo = 1
    ORDER BY fecha_ultimo_intento DESC
    LIMIT p_limite;
END$$
DELIMITER ;

-- 6. Estadísticas de seguridad para dashboard
DROP PROCEDURE IF EXISTS sp_admin_estadisticas_seguridad;
DELIMITER $$
CREATE PROCEDURE sp_admin_estadisticas_seguridad()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM log_accesos WHERE tipo_accion = 'login_exitoso') AS logins_exitosos,
        (SELECT COUNT(*) FROM log_accesos WHERE tipo_accion = 'login_fallido') AS logins_fallidos,
        (SELECT COUNT(*) FROM log_actividad_usuario) AS total_acciones_auditadas,
        (SELECT COUNT(*) FROM log_errores_sistema) AS total_errores,
        (SELECT COUNT(*) FROM ips_bloqueadas WHERE estado_activo = 1 AND (fecha_desbloqueo IS NULL OR fecha_desbloqueo > NOW())) AS ips_bloqueadas_activas,
        (SELECT COUNT(*) FROM log_intentos_fallidos WHERE estado_bloqueado = 1) AS cuentas_bloqueadas,
        (SELECT tipo_error, COUNT(*) AS total FROM log_errores_sistema GROUP BY tipo_error ORDER BY total DESC LIMIT 1) AS error_mas_comun;
END$$
DELIMITER ;

-- 7. Accesos por día (últimos 30 días) para gráfica
DROP PROCEDURE IF EXISTS sp_admin_accesos_diarios;
DELIMITER $$
CREATE PROCEDURE sp_admin_accesos_diarios()
BEGIN
    SELECT
        DATE(fecha_acceso) AS dia,
        SUM(CASE WHEN tipo_accion = 'login_exitoso' THEN 1 ELSE 0 END) AS exitosos,
        SUM(CASE WHEN tipo_accion = 'login_fallido' THEN 1 ELSE 0 END) AS fallidos
    FROM log_accesos
    WHERE fecha_acceso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_acceso)
    ORDER BY dia ASC;
END$$
DELIMITER ;

-- 8. Errores por día (últimos 30 días) para gráfica
DROP PROCEDURE IF EXISTS sp_admin_errores_diarios;
DELIMITER $$
CREATE PROCEDURE sp_admin_errores_diarios()
BEGIN
    SELECT
        DATE(fecha_error) AS dia,
        COUNT(*) AS total_errores
    FROM log_errores_sistema
    WHERE fecha_error >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_error)
    ORDER BY dia ASC;
END$$
DELIMITER ;

-- 9. Actividad por usuario (top)
DROP PROCEDURE IF EXISTS sp_admin_top_actividad_usuarios;
DELIMITER $$
CREATE PROCEDURE sp_admin_top_actividad_usuarios(IN p_limite INT)
BEGIN
    SELECT
        u.id_usuario_pk, u.primer_nombre, u.primer_apellido, u.correo_electronico,
        r.nombre_rol,
        COUNT(l.id_log_acceso_pk) AS total_accesos,
        MAX(l.fecha_acceso) AS ultimo_acceso
    FROM log_accesos l
    JOIN usuarios u ON u.id_usuario_pk = l.id_usuario_fk
    JOIN roles r ON r.id_rol_pk = u.id_rol_fk
    WHERE l.id_usuario_fk IS NOT NULL
    GROUP BY u.id_usuario_pk, u.primer_nombre, u.primer_apellido,
             u.correo_electronico, r.nombre_rol
    ORDER BY total_accesos DESC
    LIMIT p_limite;
END$$
DELIMITER ;

-- 10. Alertas de seguridad activas
DROP PROCEDURE IF EXISTS sp_admin_alertas_seguridad;
DELIMITER $$
CREATE PROCEDURE sp_admin_alertas_seguridad()
BEGIN
    -- Intentos fallidos recientes (última hora)
    SELECT 'login_fallido' AS tipo_alerta,
           COUNT(*) AS total,
           'Intentos de login fallidos en la última hora' AS descripcion
    FROM log_accesos
    WHERE tipo_accion = 'login_fallido'
      AND fecha_acceso >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    UNION ALL
    -- Errores críticos recientes
    SELECT 'error_critico' AS tipo_alerta,
           COUNT(*) AS total,
           'Errores del sistema en la última hora' AS descripcion
    FROM log_errores_sistema
    WHERE fecha_error >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    UNION ALL
    -- IPs bloqueadas activas
    SELECT 'ip_bloqueada' AS tipo_alerta,
           COUNT(*) AS total,
           'IPs bloqueadas actualmente' AS descripcion
    FROM ips_bloqueadas
    WHERE estado_activo = 1
      AND (fecha_desbloqueo IS NULL OR fecha_desbloqueo > NOW())
    UNION ALL
    -- Cuentas bloqueadas por fuerza bruta
    SELECT 'cuenta_bloqueada' AS tipo_alerta,
           COUNT(*) AS total,
           'Cuentas bloqueadas por intentos fallidos' AS descripcion
    FROM log_intentos_fallidos
    WHERE estado_bloqueado = 1;
END$$
DELIMITER ;

-- ============================================================
-- RESUMEN: 10 procedimientos para seguridad
-- ============================================================
