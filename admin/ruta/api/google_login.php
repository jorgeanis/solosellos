<?php
// Archivo: api/google_login.php

// 1. Iniciar la sesión para poder guardar los datos del usuario
session_start();

// 2. Incluir la base de datos y el autoload de Composer
require_once '../includes/db.php';
require_once '../vendor/autoload.php';

// 3. Recibir el "credential" (el token JWT) que envía el frontend
$data = json_decode(file_get_contents('php://input'), true);
$credential = $data['credential'] ?? null;

if (!$credential) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó la credencial.']);
    exit;
}

// 4. Verificar el token con la librería de Google
$clientId = "178138949143-r0l20kgm740jvjm4ase15la41iji652k.apps.googleusercontent.com"; // <-- ¡IMPORTANTE! REEMPLAZA ESTO
$client = new Google_Client(['client_id' => $clientId]);
$payload = $client->verifyIdToken($credential);

if ($payload) {
    // El token es válido, tenemos los datos del usuario
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $nombre = $payload['name'];

    try {
        // 5. Buscar si el usuario ya existe en nuestra base de datos
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE google_id = :google_id");
        $stmt->execute(['google_id' => $google_id]);
        $user = $stmt->fetch();

        if ($user) {
            // El usuario ya existe, obtenemos su ID
            $user_id = $user['id'];
        } else {
            // El usuario es nuevo, lo insertamos en la base de datos
            $stmt = $pdo->prepare("INSERT INTO usuarios (google_id, nombre, email) VALUES (:google_id, :nombre, :email)");
            $stmt->execute(['google_id' => $google_id, 'nombre' => $nombre, 'email' => $email]);
            $user_id = $pdo->lastInsertId();
        }

        // 6. Guardar el ID de nuestro usuario en la sesión de PHP
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $nombre;

        // 7. Devolver una respuesta de éxito
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Login correcto.']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }

} else {
    // El token no es válido o ha expirado
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Credencial de Google inválida.']);
}
?>