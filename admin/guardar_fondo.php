<?php
session_start();
require_once '../db.php';

header('Content-Type: text/plain');
error_log("🟢 Entró en guardar_fondo.php");

if (!isset($_SESSION['user_id'], $_POST['fondo'])) {
    error_log("🔴 Faltan datos: sesión o fondo");
    echo "error: sesión o fondo no definido.";
    exit;
}

$user_id = $_SESSION['user_id'];
$fondo = $_POST['fondo'];

error_log("🟢 Datos recibidos - ID: $user_id, Fondo: $fondo");

// Preparar y ejecutar la actualización
$stmt = $conn->prepare("UPDATE users SET background_image = ? WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("si", $fondo, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        error_log("✅ Fondo actualizado correctamente.");
        echo "ok";
    } else {
        error_log("ℹ️ Fondo no cambió (sin cambios)");
        echo "sin cambios";
    }

    $stmt->close();
} else {
    error_log("❌ Error al preparar la consulta SQL.");
    echo "error al preparar consulta.";
}
?>
