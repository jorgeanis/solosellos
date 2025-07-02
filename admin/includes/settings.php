<?php
require_once __DIR__ . '/db.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    name VARCHAR(50) PRIMARY KEY,
    value TEXT
)");

function get_setting(string $name): string {
    global $pdo;
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE name = ?');
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['value'] ?? '';
}

function set_setting(string $name, string $value): void {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
    $stmt->execute([$name, $value]);
}
?>
