<?php
// Archivo: api/obtener_entregados.php

// 1. Proteger el endpoint y obtener el ID del usuario
require_once '../../includes/auth.php';

// 2. Conectar a la base de datos
require_once '../includes/db.php';

try {
    // 3. Seleccionar solo los pedidos del usuario actual que ya han sido entregados
    $sql = "SELECT id, direccion, whatsapp, otros_datos, imagen_sello, fecha_creacion FROM pedidos WHERE entregado = TRUE AND user_id = ? ORDER BY fecha_creacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([1]);

    $pedidos_entregados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Devolver los resultados en formato JSON
    header('Content-Type: application/json');
    echo json_encode($pedidos_entregados);

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar la base de datos.']);
}

?>
