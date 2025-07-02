<?php
require_once "includes/config.php";
require_once "includes/settings.php";

file_put_contents(__DIR__ . "/cron_estado.log", "[" . date("Y-m-d H:i:s") . "] Cron ejecutado\n", FILE_APPEND);

$hoy = date("Y-m-d");

// Buscar usuarios activos con suscripción
$sql = "SELECT id, preapproval_id FROM users WHERE active = 1 AND preapproval_id IS NOT NULL";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $preapproval_id = $row['preapproval_id'];

        $token = get_setting('mp_access_token');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.mercadopago.com/preapproval/" . $preapproval_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token"
            ]
        ]);
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http === 200) {
            $data = json_decode($response, true);
            $estado = $data['status'] ?? '';

            if ($estado !== "authorized") {
                $stmt = $conn->prepare("UPDATE users SET active = 0, plan = 'Cuenta suspendida' WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                file_put_contents(__DIR__ . "/cron_estado.log", "[" . date("H:i:s") . "] ⛔ Usuario $user_id suspendido ($estado)\n", FILE_APPEND);
            } else {
                file_put_contents(__DIR__ . "/cron_estado.log", "[" . date("H:i:s") . "] ✅ Usuario $user_id activo\n", FILE_APPEND);
            }
        } else {
            file_put_contents(__DIR__ . "/cron_estado.log", "[" . date("H:i:s") . "] ⚠️ Error al consultar $user_id (HTTP $http)\n", FILE_APPEND);
        }
    }
} else {
    file_put_contents(__DIR__ . "/cron_estado.log", "[" . date("H:i:s") . "] No hay usuarios activos para verificar\n", FILE_APPEND);
}
?>
