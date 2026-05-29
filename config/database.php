<?php
// /cursoonline/config/database.php
// ============================================================
// Archivo de configuración y conexión a la base de datos.
// Proyecto: EduTech Academy
// ============================================================

// --- Desactivar visualización de errores en producción ---
// En desarrollo se pueden activar para depuración.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ============================================================
// CONSTANTES DE CONEXIÓN A LA BASE DE DATOS
// ============================================================
define('DB_HOST',    'localhost');
define('DB_NAME',    'db_edutechacademy');
define('DB_USER',    'root');
define('DB_PASS',    '123456789');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    3306);

// ============================================================
// FUNCIÓN PRINCIPAL: obtenerConexion()
// Retorna una instancia PDO singleton a la base de datos.
// Usa prepared statements para prevenir inyección SQL.
// ============================================================

/**
 * Retorna la conexión PDO activa a la base de datos.
 * Implementa el patrón Singleton para evitar múltiples conexiones.
 *
 * @return PDO  Instancia de la conexión PDO
 */
function obtenerConexion(): PDO {
    // Variable estática para guardar la instancia única de conexión
    static $conexion = null;

    if ($conexion === null) {

        // --- Construcción del DSN (Data Source Name) ---
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        // --- Opciones de configuración PDO ---
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Lanzar excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           // Retornar arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                      // Usar prepared statements reales
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // --- Registrar error sin exponer detalles al usuario ---
            $mensaje_log = sprintf(
                "[ERROR BD] [%s] Archivo: %s | Linea: %d | Mensaje: %s",
                date('Y-m-d H:i:s'),
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            );
            error_log($mensaje_log);

            http_response_code(500);

            // Si es una petición AJAX/API, devolver JSON
            $es_ajax = (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            );
            // Si la URL está dentro de /api/, también responder con JSON
            $es_api = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);

            if ($es_ajax || $es_api) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'estado'  => 'error',
                    'codigo'  => 500,
                    'mensaje' => 'Error de conexión al servidor. Por favor intente más tarde.'
                ]);
            } else {
                // Para vistas HTML: mostrar página de error amigable
                $msg = 'No se pudo conectar con la base de datos. Por favor, contacte al administrador o intente más tarde.';
                echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
                    . '<title>Error de Servidor — EduTech Academy</title>'
                    . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">'
                    . '</head><body class="d-flex align-items-center justify-content-center vh-100 bg-light">'
                    . '<div class="text-center p-5"><h1 class="display-1 text-danger">500</h1>'
                    . '<h2 class="mb-3">Error de Servidor</h2>'
                    . '<p class="text-muted mb-4">' . htmlspecialchars($msg) . '</p>'
                    . '<a href="javascript:history.back()" class="btn btn-primary">Volver</a>'
                    . '</div></body></html>';
            }
            exit();
        }
    }

    return $conexion;
}

/**
 * Cierra la conexión PDO activa.
 * Útil al finalizar scripts de larga duración.
 *
 * @return void
 */
function cerrarConexion(): void {
    // PDO no tiene método close(); se destruye asignando null
    // En Singleton esto debe manejarse con cuidado.
    // Esta función sirve como punto de control futuro.
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/config/database.php
 * ============================================================
 * Archivo de configuración y conexión segura a la base de datos MySQL.
 *
 * Funciones principales:
 *   - Define constantes de conexión: host, nombre de BD, usuario, contraseña.
 *   - Provee obtenerConexion(): retorna instancia PDO Singleton reutilizable.
 *   - Usa PDO::ERRMODE_EXCEPTION para manejo robusto de errores.
 *   - Deshabilita emulación de prepared statements (seguridad contra SQL Injection).
 *   - Registra errores en log del sistema sin exponer información sensible al usuario.
 *   - Configura charset utf8mb4 para soporte completo de Unicode.
 *
 * Seguridad:
 *   - Nunca expone credenciales ni mensajes de error técnicos al navegador.
 *   - La carpeta /config/ está protegida con .htaccess (acceso denegado desde navegador).
 *
 * Última actualización: Fase 1 — Fundamentos y Base de Datos
 * ============================================================
 */
?>
