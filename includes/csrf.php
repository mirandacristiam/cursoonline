<?php
// /cursoonline/includes/csrf.php
// ============================================================
// Manejo de Tokens CSRF — EduTech Academy
// ============================================================

/**
 * Genera un token CSRF único y lo guarda en la base de datos asociado a la sesión PHP.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param string $nombre_formulario Identificador opcional del formulario.
 * @return string El token generado.
 */
function generar_token_csrf(PDO $pdo, $nombre_formulario = 'general') {
    $token = bin2hex(random_bytes(32)); // Token criptográficamente seguro
    $id_sesion = session_id();
    
    if (empty($id_sesion)) {
        // Fallback si no hay sesión iniciada
        $id_sesion = 'no-session-' . bin2hex(random_bytes(8));
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO tokens_csrf (token_valor, id_sesion_php, nombre_formulario, fecha_expiracion) 
            VALUES (:token, :sesion, :formulario, DATE_ADD(NOW(), INTERVAL :expiracion SECOND))
        ");
        $stmt->execute([
            ':token'      => $token,
            ':sesion'     => $id_sesion,
            ':formulario' => $nombre_formulario,
            ':expiracion' => CSRF_EXPIRACION_SEG
        ]);
        
        return $token;
    } catch (PDOException $e) {
        error_log("[CSRF ERROR] No se pudo generar el token: " . $e->getMessage());
        return '';
    }
}

/**
 * Valida un token CSRF recibido verificando que exista, pertenezca a la sesión y no haya expirado o sido usado.
 * Si es válido, lo marca como usado para que no pueda reutilizarse.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param string $token El token recibido en la petición.
 * @return bool True si es válido, False si es inválido.
 */
function validar_token_csrf(PDO $pdo, $token) {
    if (empty($token)) return false;
    
    $id_sesion = session_id();

    try {
        $stmt = $pdo->prepare("
            SELECT id_token_pk 
            FROM tokens_csrf 
            WHERE token_valor = :token 
              AND id_sesion_php = :sesion 
              AND estado_usado = 0 
              AND fecha_expiracion > NOW()
        ");
        $stmt->execute([
            ':token'  => $token,
            ':sesion' => $id_sesion
        ]);
        
        $registro = $stmt->fetch();
        
        if ($registro) {
            // Marcar el token como usado (One-time use)
            $stmt_upd = $pdo->prepare("UPDATE tokens_csrf SET estado_usado = 1 WHERE id_token_pk = :id");
            $stmt_upd->execute([':id' => $registro['id_token_pk']]);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("[CSRF ERROR] No se pudo validar el token: " . $e->getMessage());
        return false;
    }
}

/**
 * Función de conveniencia para imprimir el campo oculto con el token en formularios HTML.
 * Usa htmlspecialchars() directamente para no depender de security.php.
 *
 * @param PDO $pdo
 * @param string $nombre_formulario
 */
function imprimir_campo_csrf(PDO $pdo, $nombre_formulario = 'general') {
    $token = generar_token_csrf($pdo, $nombre_formulario);
    // Usamos htmlspecialchars directamente para evitar dependencia de security.php
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/includes/csrf.php
 * ============================================================
 * Generación y validación de tokens CSRF.
 * - generar_token_csrf(): Genera un string seguro (bin2hex + random_bytes)
 *   y lo inserta en la tabla `tokens_csrf` asociado a la sesión actual.
 * - validar_token_csrf(): Verifica la existencia, sesión, expiración y
 *   estado no usado del token. Lo marca como usado inmediatamente tras validarlo.
 * - imprimir_campo_csrf(): Helper para añadir el <input hidden> en las vistas.
 *
 * Última actualización: Fase 2 — Autenticación y Seguridad
 * ============================================================
 */
?>
