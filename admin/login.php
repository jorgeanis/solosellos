<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && hash('sha256', $pass) === $user['password']) {
        $_SESSION['user'] = $user;
        $needs_setup = empty($user['name']) || empty($user['color_primary']);
        if ($needs_setup) {
            header("Location: setup_wizard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Credenciales inválidas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - SoloSellos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .login-container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 320px;
            text-align: center;
            animation: fadeIn 0.6s ease;
        }

        .login-card img {
            width: 100px;
            margin-bottom: 20px;
        }

        .login-card h2 {
            margin-bottom: 10px;
            font-size: 22px;
            color: #333;
        }

        .login-card input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: border 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card input:focus {
            border-color: #1abc9c;
            box-shadow: 0 0 5px rgba(26, 188, 156, 0.4);
            outline: none;
        }

        .login-card button {
            width: 100%;
            padding: 10px;
            background-color: #1abc9c;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-card button:hover {
            background-color: #16a085;
        }

        .error-msg {
            background: #ffe0e0;
            color: #c0392b;
            border: 1px solid #e74c3c;
            padding: 8px;
            margin: 10px 0;
            border-radius: 5px;
            animation: slideDown 0.3s ease;
        }

        footer {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
            font-size: 12px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-logo {
            display: block;
            max-width: 300px;
            width: 100%;
            height: auto;
            margin: 40px auto 30px auto;
        }
        .login-field {
            max-width: 300px;
            width: 100%;
            margin: 8px auto;
            display: block;
            text-align: left;
            align: center;
        }
        .login-button {
            max-width: 300px;
            width: 100%;
            margin: 8px auto;
            display: block;
            text-align: center;
            align: center;
}
</style>

</head>
<body>

<div class="login-container">
    <div class="login-card" style="text-align: center;">
        <img src="../assets/images/logo-login.png" style="max-width:200px; width:100%; height:auto; display:block; margin:30px auto;" />
        <br>
        <hr>
        <br>
        <h2>Acceso al panel</h2>

        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        
<?php
require_once __DIR__ . '/includes/config.php';

if (isset($_GET['preapproval_id'])) {
    $preapproval_id = $_GET['preapproval_id'];

    // Consultar la API de MercadoPago
    $ch = curl_init("https://api.mercadopago.com/preapproval/" . $preapproval_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer APP_USR-1930182136477954-061623-8099269069413fe668c807c1046ed8a9-687266560"  // 👈 Reemplazá esto con tu token real
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['status']) && $data['status'] === 'authorized') {
        $referencia = $data['external_reference'];
        $user_id = str_replace("usuario_", "", $referencia);
        $conn->query("UPDATE users SET active = 1, plan = 'mensual' WHERE id = $user_id");
    }
}
?>

<?php if (isset($_GET['sub']) && $_GET['sub'] === 'ok'): ?>
  <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
    ✅ ¡Tu suscripción fue confirmada correctamente! Ahora podés iniciar sesión.
  </div>
<?php elseif (isset($_GET['preapproval_id'])): ?>
  <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
    ✅ ¡Gracias por suscribirte! Tu cuenta ha sido activada automáticamente.
  </div>
<?php endif; ?>
<form method="POST" style="display: flex; flex-direction: column; align-items: center;">
            <input type="email" name="email" placeholder="Correo" required class="login-field">
            <input type="password" name="password" placeholder="Contraseña" required class="login-field">
            <button type="submit" class="login-button">Ingresar</button>
        </form>

        <footer style="margin-top: 20px;">
            © <?= date('Y') ?> SoloSellos
        </footer>
    </div>
</div>

</body>
</html>
