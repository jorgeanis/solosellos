<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/settings.php';

if ($_SESSION['user']['email'] !== 'admin@solosellos.com') {
    echo '<p>No tenés permiso para ver esta página.</p>';
    require_once 'includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_setting('mp_public_key', $_POST['mp_public_key'] ?? '');
    set_setting('mp_access_token', $_POST['mp_access_token'] ?? '');
    set_setting('webhook_secret', $_POST['webhook_secret'] ?? '');
    set_setting('mp_subscription_link', $_POST['mp_subscription_link'] ?? '');
    $msg = 'Configuración guardada';
}

$public_key = get_setting('mp_public_key');
$access_token = get_setting('mp_access_token');
$webhook_secret = get_setting('webhook_secret');
$mp_subscription_link = get_setting('mp_subscription_link');
?>
<h2>Configuración de Integraciones</h2>
<?php if (!empty($msg)) echo "<p style='color:green;'>$msg</p>"; ?>
<form method="post" class="card" style="max-width:400px;">
    <label>Public Key de MercadoPago</label>
    <input type="text" name="mp_public_key" value="<?= htmlspecialchars($public_key) ?>">
    <label>Access Token de MercadoPago</label>
    <input type="text" name="mp_access_token" value="<?= htmlspecialchars($access_token) ?>">
    <label>Clave Webhook</label>
    <input type="text" name="webhook_secret" value="<?= htmlspecialchars($webhook_secret) ?>">
    <label>Enlace de Suscripción de MercadoPago</label>
    <input type="text" name="mp_subscription_link" value="<?= htmlspecialchars($mp_subscription_link) ?>">
    <button type="submit">Guardar</button>
</form>
<?php require_once 'includes/footer.php'; ?>
