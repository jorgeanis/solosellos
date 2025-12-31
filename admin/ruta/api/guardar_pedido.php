<?php
// Archivo: api/guardar_pedido.php

// 1. Incluir el guardián de sesión. Si el usuario no ha iniciado sesión, el script se detendrá aquí.
require_once '../../includes/auth.php';

// 2. Incluir la conexión a la base de datos
require_once '../includes/db.php';

// 3. Verificar que la solicitud sea de tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $user_id = 1; // Obtenemos el ID del usuario de la sesión

    $direccion = trim($_POST['direccion'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $otros_datos = trim($_POST['otros_datos'] ?? '');
    $imagen_base64 = $_POST['imagen_base64'] ?? '';

    if (empty($direccion) || empty($whatsapp) || empty($imagen_base64)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
        exit;
    }

    list($type, $data) = explode(';', $imagen_base64);
    list(, $data)      = explode(',', $data);
    $data = base64_decode($data);

    if ($data === false) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El formato de la imagen no es válido.']);
        exit;
    }

    $nombre_archivo = uniqid('sello_', true) . '.png';
    $ruta_guardado = '../uploads/' . $nombre_archivo;

    if (file_put_contents($ruta_guardado, $data)) {
        try {
            $sql = "INSERT INTO pedidos (user_id, direccion, whatsapp, otros_datos, imagen_sello) VALUES (:user_id, :direccion, :whatsapp, :otros_datos, :imagen_sello)";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':user_id' => $user_id, // Asociar el pedido con el usuario
                ':direccion' => $direccion,
                ':whatsapp' => $whatsapp,
                ':otros_datos' => $otros_datos,
                ':imagen_sello' => $nombre_archivo
            ]);

            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Pedido guardado correctamente.']);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar en la base de datos.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar el archivo de imagen.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
