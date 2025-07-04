<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/settings.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$nombre || !$email || !$telefono || !$password) {
        die("Faltan datos obligatorios.");
    }

    // Validar si el email ya existe
    $verificar = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $verificar->bind_param("s", $email);
    $verificar->execute();
    $verificar->store_result();

    if ($verificar->num_rows > 0) {
        header('Location: formreg.html?error=exists');
        exit;
    }
    $verificar->close();

    // Crear usuario
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $link_code = uniqid('u_');

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, whatsapp, link_code, active, plan)
                            VALUES (?, ?, ?, ?, ?, 0, 'pendiente')");
    $stmt->bind_param("sssss", $nombre, $email, $password_hashed, $telefono, $link_code);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();

    $referencia = "usuario_" . $user_id;
    $base_link = get_setting('mp_subscription_link');
    if (!$base_link) {
        $plan_id = "2c93808497c876110197ccac22f2022c";
        $base_link = "https://www.mercadopago.com.ar/subscriptions/checkout?preapproval_plan_id={$plan_id}";
    }
    $url_mp = $base_link . "&external_reference={$referencia}";
    header("Location: $url_mp");
    exit;
}
?>