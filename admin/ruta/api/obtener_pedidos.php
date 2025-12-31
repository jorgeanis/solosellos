<?php
// Habilitar registro de errores a un archivo para depuración.
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_log.txt');
error_reporting(E_ALL);

// Archivo: api/obtener_pedidos.php

header('Content-Type: application/json');

try {
    require_once '../../includes/auth.php';
    require_once '../includes/db.php';

    if (!isset($pdo)) {
        throw new Exception("La conexión a la base de datos de 'ruta' no se pudo establecer.");
    }

    // Obtener ID de ruta si existe
    $ruta_id = $_GET['ruta_id'] ?? null;

    if ($ruta_id) {
        // Si especificamos ruta, traemos TODOS los de esa ruta (entregados o no) para reconstruir el historial visual
        $sql = "SELECT id, direccion, lat, lng, whatsapp, otros_datos, imagen_sello, fecha_creacion, entregado FROM pedidos WHERE ruta_id = ? AND user_id = ? ORDER BY fecha_creacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ruta_id, 1]); // Asumimos user_id fijo 1 según auth de ruta
    } else {
        // Comportamiento legado: pedidos pendientes sin ruta o general
        $sql = "SELECT id, direccion, lat, lng, whatsapp, otros_datos, imagen_sello, fecha_creacion, entregado FROM pedidos WHERE entregado = FALSE AND user_id = ? ORDER BY fecha_creacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([1]);
    }

    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pedidos);

} catch (Throwable $e) { // Captura tanto Errores como Excepciones en PHP 7+
    http_response_code(500);
    $error_details = [
        'status' => 'error', 
        'message' => 'Error interno en el servidor.',
        'error_details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    // También registrar el error en nuestro log personalizado por si acaso.
    error_log(json_encode($error_details));
    echo json_encode($error_details);
}

?>
