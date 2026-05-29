<?php
// /cursoonline/api/contacto.php
// ============================================================
// API para procesar mensajes del formulario de contacto público
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';
require_once '../includes/csrf.php';

iniciar_sesion_segura();

// Sólo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método de petición no permitido.']);
    exit();
}

$pdo = obtenerConexion();

// --- 1. VALIDACIÓN DEL TOKEN CSRF ---
if (!validar_token_csrf($pdo, $_POST['csrf_token'] ?? '', 'contacto')) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Petición no autorizada o sesión expirada (CSRF inválido).']);
    exit();
}

// --- 2. RECEPCIÓN Y SANITIZACIÓN DE ENTRADAS ---
$nombre   = isset($_POST['nombre'])  ? limpiar_entrada($_POST['nombre'])  : '';
$correo   = isset($_POST['correo'])  ? limpiar_entrada($_POST['correo'])  : '';
$telefono = isset($_POST['telefono'])? limpiar_entrada($_POST['telefono']): '';
$asunto   = isset($_POST['asunto'])  ? limpiar_entrada($_POST['asunto'])  : '';
$mensaje  = isset($_POST['mensaje']) ? limpiar_entrada($_POST['mensaje']) : '';
$ip       = obtener_ip_cliente();

// --- 3. VALIDACIÓN EN BACKEND (Doble Validación) ---
if (empty($nombre) || empty($correo) || empty($asunto) || empty($mensaje)) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Por favor, complete todos los campos obligatorios (*).']);
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'El correo electrónico ingresado no es válido.']);
    exit();
}

try {
    // --- 4. INSERCIÓN EN BASE DE DATOS ---
    $stmt = $pdo->prepare("
        INSERT INTO contacto_formulario 
            (nombre_completo, correo_electronico, numero_telefono, asunto_mensaje, mensaje, ip_origen, estado_respuesta, estado_activo) 
        VALUES 
            (:nombre, :correo, :telefono, :asunto, :mensaje, :ip, 'pendiente', 1)
    ");
    
    $stmt->execute([
        ':nombre'   => $nombre,
        ':correo'   => $correo,
        ':telefono' => $telefono,
        ':asunto'   => $asunto,
        ':mensaje'  => $mensaje,
        ':ip'       => $ip
    ]);
    
    // Invalidar token CSRF para evitar doble envío
    invalidar_token_csrf($pdo, $_POST['csrf_token'] ?? '');

    echo json_encode([
        'estado' => 'ok',
        'mensaje' => '¡Mensaje recibido con éxito! Nos pondremos en contacto contigo lo antes posible.'
    ]);
    
} catch (PDOException $e) {
    // Registrar error internamente
    error_log("[ERROR CONTACTO] " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Ocurrió un error al procesar tu solicitud. Por favor intenta más tarde.']);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/api/contacto.php
 * ============================================================
 * API AJAX para el procesamiento seguro de formularios de contacto.
 * ============================================================
 */
?>
