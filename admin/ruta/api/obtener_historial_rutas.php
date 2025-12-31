<?php
// Archivo: api/obtener_historial_rutas.php

require_once '../../includes/auth.php';
require_once '../includes/db.php';

try {
    $sql = "SELECT id, nombre_ruta, fecha_creacion FROM rutas WHERE user_id = ? ORDER BY fecha_creacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([1]);
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($rutas);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener el historial de rutas.']);
}
?>