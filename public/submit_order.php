<?php
require_once '../admin/includes/db.php';

try {
    // --- 1. Recoger todos los datos del POST ---
    $link_code = $_POST['u'] ?? '';
    $model_id = $_POST['model_id'] ?? 0;
    $template_id = $_POST['template_id'] ?? 0;

    // Datos del cliente
    $name = $_POST['name'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $comments = $_POST['comments'] ?? '';

    // --- Obtener el precio del modelo ---
    $model_price = $_POST['model_price'] ?? 0.00;

    // --- LOGICA REFERIDOS ---
    session_start();
    $referred_by_code = null;
    
    // Check if referral session exists and matches the user of this order
    // We re-verify settings to be safe
    if (isset($_SESSION['referral_code']) && isset($_SESSION['referral_user_id']) && $_SESSION['referral_user_id'] == $user_id) {
        $stmt_check_ref = $pdo->prepare("SELECT referral_active, referral_type, referral_value FROM users WHERE id = ?");
        $stmt_check_ref->execute([$user_id]);
        $ref_settings = $stmt_check_ref->fetch();

        if ($ref_settings && $ref_settings['referral_active']) {
             $referred_by_code = $_SESSION['referral_code'];
             // Nota: Confiamos en el precio que viene del frontend (model_price) ya que se calculó allí.
             // En un sistema más estricto, recalcularíamos aquí usando los datos de la DB.
             // Por ahora, asumimos que model_price ya trae el descuento si se aplicó en el frontend.
        }
    }

    

    // --- 2. Recoger datos de personalización (texto y estilos) ---
    $text_lines = [];
    $styles_data = [
        'fuente' => [], 'tamano' => [], 'bold' => [], 
        'alineacion' => [], 'margen_top' => [], 'mayuscula' => []
    ];

    for ($i = 1; $i <= 4; $i++) {
        $text_lines[$i] = $_POST["linea{$i}_texto_editor"] ?? '';
        $styles_data['fuente'][$i-1] = $_POST["fuente{$i}"] ?? 'Arial';
        $styles_data['tamano'][$i-1] = $_POST["tamano{$i}"] ?? '16';
        $styles_data['bold'][$i-1] = isset($_POST["negrita{$i}"]);
        $styles_data['alineacion'][$i-1] = $_POST["alineacion{$i}"] ?? 'center';
        $styles_data['margen_top'][$i-1] = $_POST["margen_top{$i}"] ?? '0';
        $styles_data['mayuscula'][$i-1] = isset($_POST["mayuscula{$i}"]);
    }
    
    $estilos_json = json_encode($styles_data);

    // --- 3. Validaciones y obtener IDs ---
    $stmt = $pdo->prepare("SELECT id FROM users WHERE link_code = ?");
    $stmt->execute([$link_code]);
    $user = $stmt->fetch();
    if (!$user) throw new Exception("Usuario no encontrado.");
    $user_id = $user['id'];

    // --- 4. Generar código de pedido único ---
    function generateOrderCode($length = 10) {
        return substr(strtoupper(bin2hex(random_bytes(ceil($length / 2)))), 0, $length);
    }
    $order_code = generateOrderCode();
    $exists = true;
    while ($exists) {
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_code = ?");
        $stmt->execute([$order_code]);
        $exists = $stmt->fetchColumn() !== false;
        if ($exists) {
            $order_code = generateOrderCode();
        }
    }

    // --- 5. Insertar en la base de datos ---
    $sql = "INSERT INTO orders (
        order_code, user_id, name, lastname, address, phone, email,
        model_id, template_id, price,
        text_line1, text_line2, text_line3, text_line4, comments, styles, referred_by,
        created_at, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pendiente')";
    
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $order_code,
        $user_id,
        $name,
        $lastname,
        $address,
        $phone,
        $email,
        $model_id,
        $template_id,
        $model_price,
        $text_lines[1],
        $text_lines[2],
        $text_lines[3],
        $text_lines[4],
        $comments,
        $estilos_json,
        $referred_by_code
    ]);

    if ($stmt->rowCount() > 0) {
        error_log("DEBUG: Pedido insertado correctamente. Order ID: " . $pdo->lastInsertId());
    } else {
        error_log("ERROR: Fallo al insertar el pedido.");
    }

    // --- 6. Redirigir a la página de agradecimiento ---
    error_log("DEBUG: Redirigiendo a thanks.php con u=$link_code y order=$order_code");
    header("Location: thanks.php?u=$link_code&order=$order_code");
    exit;

} catch (Exception $e) {
    // Manejo de errores
    error_log("Error en submit_order.php: " . $e->getMessage());
    die("ERROR: Ocurrió un problema al procesar tu pedido. Por favor, intenta de nuevo.");
}
?>