<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u526570234_sellos');
define('DB_USER', 'u526570234_sellos');
define('DB_PASS', 'Cuentadehostinger1983!');

// Conexión correcta usando constantes
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Alias para compatibilidad con scripts que usan $conn
$conn = $conexion;
?>
