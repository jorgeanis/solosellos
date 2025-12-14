<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (empty($_GET['ids'])) {
    header('Location: pedidos.php');
    exit;
}

$ids_string = $_GET['ids'];
$ids_array = explode(',', $ids_string);

// Sanitize IDs to ensure they are integers
$sanitized_ids = array_map('intval', $ids_array);
$sanitized_ids = array_filter($sanitized_ids, function($id) {
    return $id > 0;
});

if (empty($sanitized_ids)) {
    header('Location: pedidos.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Create placeholders for the IN clause
$placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));

// We add user_id to the params for the WHERE clause
$params = $sanitized_ids;
$params[] = $user_id;

$sql = "DELETE FROM orders WHERE id IN ($placeholders) AND user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Location: pedidos.php');
exit;
?>
