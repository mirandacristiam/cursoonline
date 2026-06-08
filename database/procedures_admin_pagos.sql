-- ============================================================
-- /cursoonline/database/procedures_admin_pagos.sql
-- Procedimientos para gestión admin de pagos/transacciones
-- EduTech Academy — Motor: MySQL 8.x
-- ============================================================

-- 1. Listar transacciones con filtros
DROP PROCEDURE IF EXISTS sp_admin_listar_transacciones;
DELIMITER $$
CREATE PROCEDURE sp_admin_listar_transacciones(
    IN p_estado     VARCHAR(30),
    IN p_busqueda   VARCHAR(255),
    IN p_pagina     INT,
    IN p_por_pagina INT,
    OUT p_total     INT
)
BEGIN
    DECLARE v_off INT DEFAULT 0;
    SET v_off = (p_pagina - 1) * p_por_pagina;

    SELECT SQL_CALC_FOUND_ROWS
        t.id_transaccion_pk, t.numero_referencia, t.monto_total,
        t.estado_transaccion, t.observaciones, t.fecha_creacion, t.fecha_modificacion,
        u.id_usuario_pk,
        CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS nombre_estudiante,
        u.correo_electronico,
        c.id_curso_pk, c.titulo_curso,
        mp.nombre_medio_pago
    FROM transacciones_pago t
    JOIN usuarios u ON u.id_usuario_pk = t.id_usuario_fk
    JOIN cursos c   ON c.id_curso_pk   = t.id_curso_fk
    JOIN medios_pago mp ON mp.id_medio_pago_pk = t.id_medio_pago_fk
    WHERE t.estado_activo = 1
      AND (p_estado = 'todos' OR t.estado_transaccion = p_estado)
      AND (p_busqueda = '' OR c.titulo_curso LIKE CONCAT('%', p_busqueda, '%')
           OR CONCAT(u.primer_nombre, ' ', u.primer_apellido) LIKE CONCAT('%', p_busqueda, '%')
           OR t.numero_referencia LIKE CONCAT('%', p_busqueda, '%'))
    ORDER BY
        CASE t.estado_transaccion WHEN 'pendiente' THEN 0 ELSE 1 END ASC,
        t.fecha_creacion DESC
    LIMIT v_off, p_por_pagina;
    SELECT FOUND_ROWS() INTO p_total;
END$$
DELIMITER ;

-- 2. Contar transacciones por estado
DROP PROCEDURE IF EXISTS sp_admin_contar_transacciones;
DELIMITER $$
CREATE PROCEDURE sp_admin_contar_transacciones()
BEGIN
    SELECT estado_transaccion, COUNT(*) AS total
    FROM transacciones_pago
    WHERE estado_activo = 1
    GROUP BY estado_transaccion;
END$$
DELIMITER ;

-- 3. Obtener detalle de una transacción
DROP PROCEDURE IF EXISTS sp_admin_obtener_transaccion;
DELIMITER $$
CREATE PROCEDURE sp_admin_obtener_transaccion(IN p_id_transaccion INT)
BEGIN
    SELECT t.*,
           CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS nombre_estudiante,
           u.correo_electronico, u.numero_telefono,
           c.titulo_curso, c.precio, c.imagen_portada,
           mp.nombre_medio_pago,
           adm.primer_nombre AS admin_nombre, adm.primer_apellido AS admin_apellido
    FROM transacciones_pago t
    JOIN usuarios u ON u.id_usuario_pk = t.id_usuario_fk
    JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
    JOIN medios_pago mp ON mp.id_medio_pago_pk = t.id_medio_pago_fk
    LEFT JOIN usuarios adm ON adm.id_usuario_pk = t.modificado_por
    WHERE t.id_transaccion_pk = p_id_transaccion;
END$$
DELIMITER ;

-- 4. Estadísticas de pagos para dashboard
DROP PROCEDURE IF EXISTS sp_admin_estadisticas_pagos;
DELIMITER $$
CREATE PROCEDURE sp_admin_estadisticas_pagos()
BEGIN
    SELECT
        COUNT(*) AS total_transacciones,
        COALESCE(SUM(CASE WHEN estado_transaccion = 'aprobada' THEN monto_total ELSE 0 END), 0) AS total_aprobado,
        COALESCE(SUM(CASE WHEN estado_transaccion = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes,
        COALESCE(SUM(CASE WHEN estado_transaccion = 'aprobada' THEN 1 ELSE 0 END), 0) AS aprobadas,
        COALESCE(SUM(CASE WHEN estado_transaccion = 'rechazada' THEN 1 ELSE 0 END), 0) AS rechazadas,
        COALESCE(SUM(CASE WHEN estado_transaccion = 'cancelada' THEN 1 ELSE 0 END), 0) AS canceladas,
        COALESCE(SUM(monto_total), 0) AS monto_total_bruto
    FROM transacciones_pago
    WHERE estado_activo = 1;
END$$
DELIMITER ;

-- 5. Ingresos mensuales (últimos 12 meses)
DROP PROCEDURE IF EXISTS sp_admin_ingresos_mensuales;
DELIMITER $$
CREATE PROCEDURE sp_admin_ingresos_mensuales()
BEGIN
    SELECT
        DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes,
        DATE_FORMAT(fecha_creacion, '%M %Y') AS mes_nombre,
        COUNT(*) AS total_transacciones,
        COALESCE(SUM(monto_total), 0) AS total_ingresos
    FROM transacciones_pago
    WHERE estado_transaccion = 'aprobada'
      AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m'), DATE_FORMAT(fecha_creacion, '%M %Y')
    ORDER BY mes ASC;
END$$
DELIMITER ;

-- ============================================================
-- RESUMEN: 5 procedimientos para admin de pagos
-- ============================================================
