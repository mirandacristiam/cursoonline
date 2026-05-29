<?php
// /cursoonline/api/progreso.php
// ============================================================
// API para registrar el progreso de clases del estudiante
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/security.php';

iniciar_sesion_segura();

// Verificar autenticación y que sea un estudiante
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] !== ROL_ESTUDIANTE) {
    http_response_code(403);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso denegado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido.']);
    exit();
}

$accion = isset($_POST['accion']) ? limpiar_entrada($_POST['accion']) : '';

if ($accion === 'actualizar_progreso') {
    $id_inscripcion = isset($_POST['id_inscripcion']) ? (int)$_POST['id_inscripcion'] : 0;
    $id_clase       = isset($_POST['id_clase']) ? (int)$_POST['id_clase'] : 0;
    $porcentaje     = isset($_POST['porcentaje']) ? (int)$_POST['porcentaje'] : 0;
    
    if ($id_inscripcion <= 0 || $id_clase <= 0 || $porcentaje < 0 || $porcentaje > 100) {
        http_response_code(400);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Parámetros de progreso inválidos.']);
        exit();
    }
    
    $pdo = obtenerConexion();
    
    try {
        // Verificar que la inscripción pertenezca al usuario logueado
        $stmt_check = $pdo->prepare("
            SELECT id_inscripcion_pk 
            FROM inscripciones 
            WHERE id_inscripcion_pk = :id_ins AND id_usuario_fk = :id_user AND estado_activo = 1
        ");
        $stmt_check->execute([
            ':id_ins'  => $id_inscripcion,
            ':id_user' => $_SESSION['id_usuario']
        ]);
        
        if ($stmt_check->rowCount() === 0) {
            http_response_code(403);
            echo json_encode(['estado' => 'error', 'mensaje' => 'Inscripción no autorizada.']);
            exit();
        }
        
        // LLAMAR AL PROCEDIMIENTO ALMACENADO sp_actualizar_progreso_estudiante
        $stmt_proc = $pdo->prepare("
            CALL sp_actualizar_progreso_estudiante(
                :id_ins, 
                :id_clase, 
                :porcentaje, 
                @progreso_final
            )
        ");
        $stmt_proc->execute([
            ':id_ins'     => $id_inscripcion,
            ':id_clase'   => $id_clase,
            ':porcentaje' => $porcentaje
        ]);
        
        // Obtener el valor del parámetro OUT
        $progreso_final = (float)$pdo->query("SELECT @progreso_final")->fetchColumn();
        
        echo json_encode([
            'estado' => 'ok',
            'mensaje' => 'Progreso actualizado con éxito.',
            'progreso_final' => $progreso_final
        ]);
        
    } catch (PDOException $e) {
        error_log("[ERROR API PROGRESO] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Error de base de datos al procesar el progreso.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no reconocida.']);
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/api/progreso.php
 * ============================================================
 * API interna que conecta el frontend con el procedimiento almacenado
 * sp_actualizar_progreso_estudiante para auditoría académica.
 * ============================================================
 */
?>
