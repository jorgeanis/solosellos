<?php
// Archivo: admin/ruta/includes/db.php

// Intentar cargar la configuración global de la app principal
$configPath = __DIR__ . '/../../includes/config.php';

if (file_exists($configPath)) {
    require_once $configPath;
    // Mapear las constantes de la app principal a las de ruta
    if (!defined('RUTA_DB_HOST')) define('RUTA_DB_HOST', DB_HOST);
    if (!defined('RUTA_DB_USER')) define('RUTA_DB_USER', DB_USER);
    if (!defined('RUTA_DB_PASS')) define('RUTA_DB_PASS', DB_PASS);
    if (!defined('RUTA_DB_NAME')) define('RUTA_DB_NAME', DB_NAME); // Usar la misma DB unificada
} else {
    // Fallback por si no encuentra el config (ej. estructura de carpetas diferente)
    // Ajustar estos valores manualmente si es necesario
    define('RUTA_DB_HOST', 'localhost');
    define('RUTA_DB_USER', 'root'); // Valor común en XAMPP
    define('RUTA_DB_PASS', '');
    define('RUTA_DB_NAME', 'u526570234_sellos'); 
}

// --- CONEXIÓN Y CREACIÓN DE ESTRUCTURA ---
try {
    // Conexión inicial
    $pdo = new PDO("mysql:host=" . RUTA_DB_HOST . ";dbname=" . RUTA_DB_NAME . ";charset=utf8mb4", RUTA_DB_USER, RUTA_DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error: No se pudo conectar a la base de datos unificada (" . RUTA_DB_NAME . "). " . $e->getMessage());
}
?>
