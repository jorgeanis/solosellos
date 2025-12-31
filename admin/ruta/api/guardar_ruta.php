<?php
// Archivo: api/guardar_ruta.php

require_once '../../includes/auth.php';
require_once '../includes/db.php';

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$user_id = 1;
$data = json_decode(file_get_contents('php://input'), true);
$ordered_pedido_ids = $data['ordered_pedido_ids'] ?? null;

// Validar la entrada
if (empty($ordered_pedido_ids) || !is_array($ordered_pedido_ids)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionaron los IDs de los pedidos ordenados.']);
    exit;
}

// Iniciar transacción para asegurar la integridad de los datos
$pdo->beginTransaction();

try {
    // 1. Crear la nueva ruta en la tabla `rutas`
    $nombre_ruta = "Ruta del " . date('d-m-Y H:i');
    $stmt_ruta = $pdo->prepare("INSERT INTO rutas (user_id, nombre_ruta) VALUES (?, ?)");
    $stmt_ruta->execute([$user_id, $nombre_ruta]);
    $ruta_id = $pdo->lastInsertId();

    // 2. Insertar cada parada en la tabla `ruta_items`
    $stmt_items = $pdo->prepare("INSERT INTO ruta_items (ruta_id, pedido_id, orden_parada) VALUES (?, ?, ?)");
    foreach ($ordered_pedido_ids as $index => $pedido_id) {
        $orden_parada = $index + 1;
        $stmt_items->execute([$ruta_id, $pedido_id, $orden_parada]);
    }

    // Si todo fue bien, confirmar la transacción
    $pdo->commit();

    http_response_code(201); // 201 Creado
    echo json_encode(['status' => 'success', 'message' => 'Ruta guardada correctamente.', 'ruta_id' => $ruta_id]);

} catch (Exception $e) {
    // Si algo falló, revertir la transacción para no dejar datos a medias
    $pdo->rollBack();
    http_response_code(500);
    // En un entorno de producción, sería mejor registrar el error en un log.
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar la ruta: ' . $e->getMessage()]);
}
?>