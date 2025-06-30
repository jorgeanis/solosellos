<?php
$model_id = $_POST['model_id'] ?? $_POST['modelo_id'] ?? 0;

require_once '../admin/includes/db.php';

try {
  $link_code = $_POST['u'] ?? '';
  $template_id = $_POST['template_id'] ?? '';
  $name = $_POST['nombre'] ?? '';
  $dni = $_POST['dni'] ?? '';
  $address = $_POST['domicilio'] ?? '';
  $phone = $_POST['telefono'] ?? '';
  $email = $_POST['email'] ?? '';
  $line1 = $_POST['linea1'] ?? '';
  $line2 = $_POST['linea2'] ?? '';
  $line3 = $_POST['linea3'] ?? '';
  $line4 = $_POST['linea4'] ?? '';

  // Obtener ID del usuario desde el código único
  $stmt = $pdo->prepare("SELECT id FROM users WHERE link_code = ?");
  $stmt->execute([$link_code]);
  $user = $stmt->fetch();
  if (!$user) throw new Exception("Usuario no encontrado.");
  $user_id = $user['id'];

  // Validar que el modelo exista
  $stmt = $pdo->prepare("SELECT * FROM models WHERE id = ?");
  $stmt->execute([$model_id]);
  $modelo = $stmt->fetch();
  if (!$modelo) throw new Exception("Modelo no encontrado.");

  // Validar plantilla
  $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
  $stmt->execute([$template_id]);
  $plantilla = $stmt->fetch();

  if (!$plantilla) throw new Exception("Plantilla no encontrada.");

  
  // Generar código único de pedido
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


  // Crear nombre de archivo SVG
  $svg_name = "preview_" . time() . "_" . rand(1000,9999) . ".svg";
  $svg_path = "../orders/" . $svg_name;

  // Estilos
  $estilos = json_encode([
    "fuente" => [
      $plantilla["fuente_linea_1"],
      $plantilla["fuente_linea_2"],
      $plantilla["fuente_linea_3"],
      $plantilla["fuente_linea_4"]
    ],
    "tamano" => [
      $plantilla["tamano_linea_1"],
      $plantilla["tamano_linea_2"],
      $plantilla["tamano_linea_3"],
      $plantilla["tamano_linea_4"]
    ],
    "bold" => [
      $plantilla["bold_linea_1"],
      $plantilla["bold_linea_2"],
      $plantilla["bold_linea_3"],
      $plantilla["bold_linea_4"]
    ],
    "alineacion" => [
      $plantilla["alineacion_linea_1"],
      $plantilla["alineacion_linea_2"],
      $plantilla["alineacion_linea_3"],
      $plantilla["alineacion_linea_4"]
    ],
    "margen_top" => [
      $plantilla["margen_top_linea_1"],
      $plantilla["margen_top_linea_2"],
      $plantilla["margen_top_linea_3"],
      $plantilla["margen_top_linea_4"]
    ]
  ]);

  // Insertar en DB

  $stmt = $pdo->prepare("INSERT INTO orders (
    order_code, user_id, name, lastname, address, phone, email,
    model_id, template_id, svg,
    text_line1, text_line2, text_line3, text_line4, styles,
    created_at, status
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pendiente')");

  $stmt->execute([
    $order_code,
    $user_id,
    $name,
    '', // lastname vacío
    $address,
    $phone,
    $email,
    $model_id,
    $template_id,
    $svg_name,
    $line1, $line2, $line3, $line4,
    $estilos
  ]);

  header("Location: thanks.php?u=$link_code&order=$order_code");
  exit;

} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage();
}
?>
