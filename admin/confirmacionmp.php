<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/settings.php';

$preapproval_id = $_GET['preapproval_id'] ?? '';
if (!$preapproval_id) {
    die('Falta el preapproval_id');
}

$token = get_setting('mp_access_token');
$ch = curl_init("https://api.mercadopago.com/preapproval/" . $preapproval_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http !== 200) {
    die('Error consultando MercadoPago');
}

$data = json_decode($response, true);
if (!isset($data['external_reference'])) {
    die('Respuesta inválida de MercadoPago');
}

$external_reference = $data['external_reference'];
$user_id = str_replace('usuario_', '', $external_reference);

$desde = date('Y-m-d');
$hasta = date('Y-m-d', strtotime('+15 days'));
$plan = 'Plan de Prueba';

$stmt = $conn->prepare("UPDATE users SET active = 1, preapproval_id = ?, desde = ?, hasta = ?, plan = ? WHERE id = ?");
$stmt->bind_param('ssssi', $preapproval_id, $desde, $hasta, $plan, $user_id);
$stmt->execute();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $_SESSION['user'] = $user;
    header('Location: setup_wizard.php');
    exit;
}

echo 'Error procesando la suscripción.';
