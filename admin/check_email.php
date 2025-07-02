<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json');
$email = $_POST['email'] ?? '';
if (!$email) {
    echo json_encode(['exists' => false]);
    exit;
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$exists = $stmt->fetchColumn() ? true : false;
echo json_encode(['exists' => $exists]);

