<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/settings.php';

$input = file_get_contents("php://input");
$headers = getallheaders();
$clave_secreta = get_setting('webhook_secret');
$firma_recibida = $headers['x-signature'] ?? '';
$hash_calculado = hash_hmac('sha256', $input, $clave_secreta);

if ($firma_recibida !== $hash_calculado) {
    http_response_code(401);
    exit("Firma inválida");
}

$payload = json_decode($input, true);
if (!$payload || !isset($payload['type'])) exit;

$log = fopen("webhook_log.txt", "a");
fwrite($log, date("Y-m-d H:i:s") . " - " . json_encode($payload) . "\n");
fclose($log);

if ($payload['type'] === 'preapproval') {
    $preapproval_id = $payload['data']['id'];

    $token = get_setting('mp_access_token');
    $ch = curl_init("https://api.mercadopago.com/preapproval/$preapproval_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token"
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['status']) && $data['status'] == 'authorized') {
        $referencia = $data['external_reference'];
        $user_id = str_replace("usuario_", "", $referencia);

        $conn->query("UPDATE users SET activo = 1, plan = 'mensual' WHERE id = $user_id");
    }
}
?>