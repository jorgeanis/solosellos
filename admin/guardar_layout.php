<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get the raw POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$batch_id = $data['batch_id'] ?? null;
$layout_state = $data['layout_state'] ?? null;
$user_id = $_SESSION['user']['id'];

if (!$batch_id || !$layout_state) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

try {
    // First, verify the user owns this batch
    $stmt = $pdo->prepare("SELECT user_id FROM export_batches WHERE id = ?");
    $stmt->execute([$batch_id]);
    $owner_id = $stmt->fetchColumn();

    if ($owner_id != $user_id) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para guardar este lote.']);
        exit;
    }

    // Now, update the layout state
    $stmt = $pdo->prepare("UPDATE export_batches SET layout_state = ? WHERE id = ?");
    $stmt->execute([$layout_state, $batch_id]);

    echo json_encode(['success' => true, 'message' => 'Diseño guardado correctamente.']);

} catch (PDOException $e) {
    // In a real app, you'd log this error instead of echoing it
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>