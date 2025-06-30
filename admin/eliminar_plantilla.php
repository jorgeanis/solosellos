<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}

$id = (int)$_GET['id'];

// Verificar que la plantilla pertenezca al usuario
$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user']['id']]);
$plantilla = $stmt->fetch();

if (!$plantilla) {
    die("No tienes permiso para eliminar esta plantilla.");
}

// Eliminar plantilla
$stmt = $pdo->prepare("DELETE FROM templates WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user']['id']]);

// Eliminar SVG si existe
$svg_path = 'svgs/' . $id . '.svg';
if (file_exists($svg_path)) {
    unlink($svg_path);
}

// Redirigir de vuelta a plantillas
header("Location: plantillas.php");
exit;
?>