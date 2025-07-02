<?php
session_start();
require_once "includes/config.php";
require_once "includes/settings.php";

$preapproval_id = $_GET['preapproval_id'] ?? '';
if (!$preapproval_id) {
    die("Error: No se recibió el ID de suscripción.");
}

// Consultar API de MercadoPago
$token = get_setting('mp_access_token');
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.mercadopago.com/preapproval/" . $preapproval_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token"
    ]
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($http_code !== 200) {
    die("Error al consultar MercadoPago (HTTP $http_code)");
}

$data = json_decode($response, true);
if (!isset($data['external_reference'])) {
    die("No se encontró referencia externa en la respuesta de MercadoPago.");
}

$external_reference = $data['external_reference'];
$user_id = str_replace("usuario_", "", $external_reference);

$desde = date("Y-m-d");
$hasta = date("Y-m-d", strtotime("+15 days"));
$plan = "Plan de Prueba";

// Actualizar usuario
$update = $conn->prepare("UPDATE users SET active = 1, preapproval_id = ?, desde = ?, hasta = ?, plan = ? WHERE id = ?");
$update->bind_param("ssssi", $preapproval_id, $desde, $hasta, $plan, $user_id);
$update->execute();

// Obtener datos de sesión
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $_SESSION['usuario'] = $user['email'];
    $_SESSION['nombre'] = $user['name'];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['admin'] = true;
    header("Location: dashboard.php");
    exit;
} else {
    echo "Error: No se encontró el usuario luego de la activación.";
}
?>
