<?php
// Archivo: api/obtener_un_pedido.php

require_once '../../includes/auth.php';
require_once '../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID de pedido.']);
    exit;
}

try {
    // Comprobar que el pedido pertenece al usuario actual
    $sql = "SELECT id, direccion, whatsapp, otros_datos, imagen_sello FROM pedidos WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id, 'user_id' => 1]);

    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        header('Content-Type: application/json');
        echo json_encode($pedido);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado o no te pertenece.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar la base de datos.']);
}

?>
