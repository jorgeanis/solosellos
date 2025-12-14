<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user']['id'];

// Verify that the order belongs to the user before deleting
$stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if ($order) {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
}

header('Location: pedidos.php');
exit;
?>
