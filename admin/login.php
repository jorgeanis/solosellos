<?php
session_start();
require_once 'includes/db.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/settings.php';

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

// Lógica de suscripción de MercadoPago
if (isset($_GET['preapproval_id'])) {
    $preapproval_id = $_GET['preapproval_id'];
    $token = get_setting('mp_access_token');
    $ch = curl_init("https://api.mercadopago.com/preapproval/" . $preapproval_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['status']) && $data['status'] === 'authorized') {
        $referencia = $data['external_reference'];
        $user_id = str_replace("usuario_", "", $referencia);
        $pdo->query("UPDATE users SET active = 1, plan = 'mensual' WHERE id = " . intval($user_id));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - SoloSellos</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Tarjeta de Login -->
        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden border border-gray-100 transition-all">
            
            <div class="p-8">
                <!-- Logo -->
                <div class="flex justify-center mb-8">
                    <img src="../assets/images/logo-login.png" alt="SoloSellos Logo" class="h-20 w-auto object-contain">
                </div>

                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Panel de Control</h2>
                    <p class="text-gray-500 mt-2 text-sm">Ingresá tus credenciales para continuar</p>
                </div>

                <!-- Alertas de MercadoPago -->
                <?php if (isset($_GET['sub']) && $_GET['sub'] === 'ok'): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-start gap-3">
                        <i class="fas fa-check-circle mt-0.5"></i>
                        <p>¡Tu suscripción fue confirmada! Ya podés iniciar sesión.</p>
                    </div>
                <?php elseif (isset($_GET['preapproval_id'])): ?>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl text-sm flex items-start gap-3">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <p>¡Gracias por suscribirte! Tu cuenta ha sido activada automáticamente.</p>
                    </div>
                <?php endif; ?>

                <!-- Error de Login -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-start gap-3 animate-pulse">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5 ml-1">Correo Electrónico</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="far fa-envelope"></i>
                            </span>
                            <input type="email" name="email" required 
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-all"
                                placeholder="ejemplo@correo.com">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1.5 ml-1">
                            <label class="text-sm font-semibold text-gray-700">Contraseña</label>
                            <!-- <a href="#" class="text-xs text-brand-600 hover:text-brand-700 font-medium">¿Olvidaste tu contraseña?</a> -->
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input type="password" name="password" required 
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-all"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full py-3.5 px-4 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-200 transition-all active:scale-[0.98] mt-2">
                        Iniciar Sesión
                    </button>
                </form>
            </div>

            <!-- Footer Tarjeta -->
            <div class="bg-gray-50 p-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">© <?= date('Y') ?> SoloSellos · Sistema de Gestión</p>
            </div>
        </div>

        <!-- Links adicionales (Opcional) -->
        <div class="mt-8 text-center space-y-4">
            <a href="../index.php" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">
                <i class="fas fa-arrow-left mr-1.5 text-xs"></i> Volver a la web principal
            </a>
        </div>
    </div>

</body>
</html>