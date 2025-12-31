<?php
// Archivo: api/obtener_una_ruta.php

require_once '../../includes/auth.php';
require_once '../includes/db.php';

$ruta_id = $_GET['id'] ?? null;

if (!$ruta_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID de ruta.']);
    exit;
}

try {
    // La consulta JOIN nos trae los datos de los pedidos para una ruta específica, en el orden correcto.
    // También nos aseguramos de que la ruta pertenezca al usuario en sesión.
    $sql = "SELECT p.* 
            FROM pedidos p
            JOIN ruta_items ri ON p.id = ri.pedido_id
            JOIN rutas r ON ri.ruta_id = r.id
            WHERE ri.ruta_id = ? AND r.user_id = ?
            ORDER BY ri.orden_parada ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ruta_id, 1]);
    $pedidos_de_la_ruta = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($pedidos_de_la_ruta);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener los detalles de la ruta.']);
}
?>