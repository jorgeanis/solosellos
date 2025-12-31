<?php
// Archivo: includes/session.php
// Este es nuestro guardián de seguridad.

// Inicia la sesión en cada script que lo incluya.
session_start();

// Verificamos si el user_id NO está en la sesión.
if (!isset($_SESSION['user_id'])) {
    // Si no está, el usuario no ha iniciado sesión.
    http_response_code(401); // Código de "No Autorizado"
    // Devolvemos un error en formato JSON y detenemos el script.
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado. Por favor, inicia sesión.']);
    exit();
}

// Si el script continúa, significa que el usuario ha iniciado sesión correctamente.
// La variable $_SESSION['user_id'] está disponible para ser usada.
?>