<?php
// /cursoonline/database/create_procedures.php
// ============================================================
// Script para cargar los procedimientos almacenados en la BD
// ============================================================

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = obtenerConexion();
    
    // Leer el archivo SQL
    $ruta_sql = __DIR__ . '/procedures.sql';
    if (!file_exists($ruta_sql)) {
        throw new Exception("No se encontró el archivo SQL en: " . $ruta_sql);
    }
    
    $sql = file_get_contents($ruta_sql);
    
    // Quitar comentarios y separar por DELIMITER o por bloques
    // Dado que PDO no soporta DELIMITER // directamente en exec(),
    // debemos procesar las sentencias y ejecutarlas individualmente.
    
    // Eliminamos delimitadores ficticios y limpiamos
    $sql = preg_replace('/--.*\n/', '', $sql); // quitar comentarios SQL
    
    // Buscamos bloques de CREATE PROCEDURE
    $pattern = '/DROP PROCEDURE IF EXISTS (\w+);/i';
    preg_match_all($pattern, $sql, $matches_drop);
    
    // Ejecutamos los drops primero
    if (!empty($matches_drop[0])) {
        foreach ($matches_drop[0] as $drop_statement) {
            $pdo->exec($drop_statement);
        }
        echo "Procedimientos previos eliminados correctamente.<br>\n";
    }
    
    // Ahora ejecutamos las creaciones.
    // Como las creaciones están entre DELIMITER // y DELIMITER ;, las extraemos de forma limpia.
    // Alternativamente, podemos dividir usando '//'
    $bloques = explode('//', $sql);
    $creados = 0;
    
    foreach ($bloques as $bloque) {
        $bloque = trim($bloque);
        // Si contiene CREATE PROCEDURE, lo limpiamos de delimitadores y lo ejecutamos
        if (stripos($bloque, 'CREATE PROCEDURE') !== false) {
            // Quitar delimitador inicial/final de la sintaxis
            $sentencia = preg_replace('/DELIMITER\s+\/\/|DELIMITER\s+;/i', '', $bloque);
            $sentencia = trim($sentencia);
            if (!empty($sentencia)) {
                $pdo->exec($sentencia);
                $creados++;
            }
        }
    }
    
    echo "¡Setup de Procedimientos Almacenados Exitoso! Creados: {$creados} procedimientos.<br>\n";
    
} catch (Exception $e) {
    echo "ERROR al crear los procedimientos almacenados: " . $e->getMessage() . "<br>\n";
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/database/create_procedures.php
 * ============================================================
 * Script PHP de ejecución para registrar los procedimientos almacenados SQL.
 * Procesa la sintaxis DELIMITER y ejecuta sentencias individuales mediante PDO.
 * ============================================================
 */
?>
