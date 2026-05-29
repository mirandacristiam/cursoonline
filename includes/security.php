<?php
// /cursoonline/includes/security.php
// ============================================================
// Funciones de seguridad globales — EduTech Academy
// ============================================================

/**
 * Sanitiza una cadena de texto para mostrarla en HTML sin riesgo de XSS.
 * Utiliza htmlspecialchars con codificación UTF-8.
 *
 * @param string|null $texto Texto a sanitizar.
 * @return string Texto sanitizado.
 */
function sanitizar_html($texto) {
    if ($texto === null) return '';
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Limpia una entrada de texto de posibles caracteres invisibles o espacios en blanco adicionales.
 *
 * @param string|null $entrada La entrada del usuario.
 * @return string Entrada limpia.
 */
function limpiar_entrada($entrada) {
    if ($entrada === null) return '';
    return trim(strip_tags($entrada));
}

/**
 * Verifica si una dirección IP está actualmente bloqueada en la base de datos.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param string $ip Dirección IP a verificar.
 * @return bool True si está bloqueada, False si no.
 */
function verificar_ip_bloqueada(PDO $pdo, $ip) {
    try {
        $stmt = $pdo->prepare("
            SELECT id_ip_bloqueada_pk 
            FROM ips_bloqueadas 
            WHERE direccion_ip = :ip 
              AND estado_activo = 1 
              AND (tipo_bloqueo = 'permanente' OR fecha_desbloqueo > NOW())
        ");
        $stmt->execute([':ip' => $ip]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("[SEGURIDAD ERROR] Error al verificar IP: " . $e->getMessage());
        return true; // Por seguridad, si falla la BD asumimos bloqueada o error 500
    }
}

/**
 * Registra un intento fallido de login y bloquea la IP si supera el máximo permitido.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param string $correo Correo intentado.
 * @param string $ip Dirección IP del usuario.
 * @param int $max_intentos Número máximo de intentos.
 * @param int $tiempo_bloqueo_segundos Segundos de bloqueo si supera el máximo.
 * @return bool True si la IP o correo acaba de ser bloqueado, False si aún tiene intentos.
 */
function registrar_intento_fallido(PDO $pdo, $correo, $ip, $max_intentos, $tiempo_bloqueo_segundos) {
    try {
        // Buscar si ya existe un registro de intentos para esta IP y correo
        $stmt = $pdo->prepare("
            SELECT id_intento_fallido_pk, numero_intentos, estado_bloqueado 
            FROM log_intentos_fallidos 
            WHERE correo_electronico_intento = :correo AND direccion_ip = :ip
        ");
        $stmt->execute([':correo' => $correo, ':ip' => $ip]);
        $registro = $stmt->fetch();

        if ($registro) {
            $intentos = $registro['numero_intentos'] + 1;
            $bloquear = $intentos >= $max_intentos ? 1 : 0;
            
            $sql = "UPDATE log_intentos_fallidos 
                    SET numero_intentos = :intentos, 
                        fecha_ultimo_intento = NOW()";
            
            if ($bloquear) {
                $sql .= ", estado_bloqueado = 1, fecha_bloqueo_hasta = DATE_ADD(NOW(), INTERVAL :tiempo_bloqueo SECOND)";
            }
            
            $sql .= " WHERE id_intento_fallido_pk = :id";
            
            $stmt_upd = $pdo->prepare($sql);
            $params = [
                ':intentos' => $intentos,
                ':id'       => $registro['id_intento_fallido_pk']
            ];
            if ($bloquear) {
                $params[':tiempo_bloqueo'] = $tiempo_bloqueo_segundos;
            }
            $stmt_upd->execute($params);
            
            return $bloquear === 1;
        } else {
            // Primer intento fallido
            $stmt_ins = $pdo->prepare("
                INSERT INTO log_intentos_fallidos 
                    (correo_electronico_intento, direccion_ip, numero_intentos) 
                VALUES (:correo, :ip, 1)
            ");
            $stmt_ins->execute([':correo' => $correo, ':ip' => $ip]);
            return false;
        }
    } catch (PDOException $e) {
        error_log("[SEGURIDAD ERROR] Error al registrar intento fallido: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si un correo desde una IP tiene un bloqueo temporal activo por fuerza bruta.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param string $correo Correo a verificar.
 * @param string $ip Dirección IP.
 * @return bool True si está bloqueado temporalmente.
 */
function verificar_bloqueo_temporal(PDO $pdo, $correo, $ip) {
    try {
        $stmt = $pdo->prepare("
            SELECT id_intento_fallido_pk 
            FROM log_intentos_fallidos 
            WHERE correo_electronico_intento = :correo 
              AND direccion_ip = :ip 
              AND estado_bloqueado = 1 
              AND fecha_bloqueo_hasta > NOW()
        ");
        $stmt->execute([':correo' => $correo, ':ip' => $ip]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Limpia los intentos fallidos tras un login exitoso.
 */
function limpiar_intentos_fallidos(PDO $pdo, $correo, $ip) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM log_intentos_fallidos 
            WHERE correo_electronico_intento = :correo AND direccion_ip = :ip
        ");
        $stmt->execute([':correo' => $correo, ':ip' => $ip]);
    } catch (PDOException $e) {
        // Silencioso
    }
}

/**
 * Obtiene la dirección IP real del cliente.
 */
function obtener_ip_cliente() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // Si viene una lista (por proxies), tomar la primera
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }
    return trim($ip);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/includes/security.php
 * ============================================================
 * Librería de funciones de seguridad esenciales.
 *
 * Incluye:
 * - sanitizar_html(): Prevención de XSS para salidas.
 * - limpiar_entrada(): Limpieza básica de inputs POST/GET.
 * - verificar_ip_bloqueada(): Verifica prohibiciones permanentes de IPs.
 * - registrar_intento_fallido() / verificar_bloqueo_temporal(): Control anti fuerza bruta.
 * - obtener_ip_cliente(): Obtiene la IP real detrás de proxies.
 *
 * Última actualización: Fase 2 — Autenticación y Seguridad
 * ============================================================
 */
?>
