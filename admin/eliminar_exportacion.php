<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isset($_GET['id'])) {
    $batch_id_to_delete = $_GET['id'];
    $user_id = $_SESSION['user']['id'];

    try {
        $pdo->beginTransaction();

        // First, verify the user owns this batch
        $stmt = $pdo->prepare("SELECT user_id FROM export_batches WHERE id = ?");
        $stmt->execute([$batch_id_to_delete]);
        $owner_id = $stmt->fetchColumn();

        if ($owner_id == $user_id) {
            // Ownership confirmed.
            // With ON DELETE CASCADE, we only need to delete from the parent table.
            $stmt_batch = $pdo->prepare("DELETE FROM export_batches WHERE id = ?");
            $stmt_batch->execute([$batch_id_to_delete]);
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // In a real app, you might set an error message in a session flash variable
        // and log the exception.
    }
}

// Redirect back to the export view to show the updated list
header("Location: pedidos.php?view=exportaciones");
exit;
?>
