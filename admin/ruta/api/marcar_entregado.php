<?php
// Archivo: api/marcar_entregado.php

require_once '../../includes/auth.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if ($id) {
        try {
            $sql = "UPDATE pedidos SET entregado = TRUE WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id, 'user_id' => 1]);

            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Pedido marcado como entregado.']);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado o no te pertenece.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
