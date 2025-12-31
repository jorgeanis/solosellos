<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Asignar un ID de usuario fijo de la app "ruta" para los pedidos transferidos.
$ruta_user_id = 1; 

// 2. Obtener los IDs de los pedidos de "solosellos" desde la URL.
$pedidos_ids_str = $_GET['ids'] ?? '';
if (empty($pedidos_ids_str)) {
    die("No se han seleccionado pedidos.");
}
$pedidos_ids = explode(',', $pedidos_ids_str);
$pedidos_ids = array_filter($pedidos_ids, 'is_numeric');
if (empty($pedidos_ids)) {
    die("Los IDs de los pedidos no son válidos.");
}

// 3. Conectar a la base de datos de "solosellos" y obtener los datos de los pedidos.
require_once 'includes/db.php'; 

$placeholders = implode(',', array_fill(0, count($pedidos_ids), '?'));
// Se agregan campos de estilos, texto y COORDENADAS
$sql_solosellos = "
    SELECT 
        o.id, o.user_id, o.name, o.lastname, o.phone, o.address, o.lat, o.lng,
        o.styles, o.text_line1, o.text_line2, o.text_line3, o.text_line4,
        m.image as model_image
    FROM 
        orders o
    JOIN 
        models m ON o.model_id = m.id
    WHERE 
        o.id IN ($placeholders)
";

$stmt_solosellos = $pdo->prepare($sql_solosellos);
$stmt_solosellos->execute($pedidos_ids);
$solosellos_orders = $stmt_solosellos->fetchAll(PDO::FETCH_ASSOC);

// 4.5 Crear el registro de la RUTA (Cabecera)
$nombre_ruta = "Ruta " . date('d/m/Y H:i') . " (" . count($solosellos_orders) . " pedidos)";
$stmt_crear_ruta = $pdo->prepare("INSERT INTO rutas (nombre) VALUES (?)");
$stmt_crear_ruta->execute([$nombre_ruta]);
$ruta_id = $pdo->lastInsertId();

// 5. Preparar la consulta para insertar los pedidos en la tabla "pedidos" de "ruta".
$sql_ruta_insert = "
    INSERT INTO pedidos 
        (user_id, direccion, lat, lng, whatsapp, imagen_sello, otros_datos, entregado, ruta_id) 
    VALUES 
        (:user_id, :direccion, :lat, :lng, :whatsapp, :imagen_sello, :otros_datos, 0, :ruta_id)
";
$stmt_ruta_insert = $pdo->prepare($sql_ruta_insert);

// Directorio donde se guardarán los SVGs
$upload_dir = __DIR__ . '/ruta/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 6. Recorrer los pedidos, generar SVG e insertarlos en "ruta".
foreach ($solosellos_orders as $order) {
    
    // --- LÓGICA DE GENERACIÓN DE SVG ---
    $styles = json_decode($order['styles'], true);
    $texts = [
        $order['text_line1'],
        $order['text_line2'],
        $order['text_line3'],
        $order['text_line4']
    ];

    $lineas_svg = "";
    $import_fonts = [];

    for ($i = 0; $i < 4; $i++) {
        $lineNum = $i + 1;
        $text = htmlspecialchars($texts[$i] ?? '');
        
        $font = $styles['fuente'][$i] ?? 'Roboto';
        $size = $styles['tamano'][$i] ?? 30;
        
        $isBold = false;
        if (isset($styles['bold'][$i])) {
            $isBold = filter_var($styles['bold'][$i], FILTER_VALIDATE_BOOLEAN);
        }
        $fontWeight = $isBold ? 'bold' : 'normal';

        $marginTop = $styles['margen_top'][$i] ?? 0;
        $defaultY = 40 + ($i * 35); 
        $yPos = $defaultY + (int)$marginTop;

        $import_fonts[$font] = true;

        if (!empty($text)) {
            $lineas_svg .= "<text x='50%' y='{$yPos}' font-family='{$font}' font-size='{$size}' font-weight='{$fontWeight}' fill='white' text-anchor='middle'>{$text}</text>\n";
        }
    }

    $font_imports = '';
    foreach ($import_fonts as $font => $_) {
        if ($font) {
            $encoded_font = str_replace(' ', '+', $font);
            $font_imports .= "@import url('https://fonts.googleapis.com/css2?family={$encoded_font}:wght@400;700&display=swap');\n";
        }
    }

    $svgContent = "<?xml version='1.0' encoding='UTF-8' standalone='no'?>
<svg xmlns='http://www.w3.org/2000/svg' width='340' height='180'>
    <style><![CDATA[
        {$font_imports}
        text {
            dominant-baseline: middle;
        }
    ]]></style>
    <rect width='100%' height='100%' fill='black'/>
    {$lineas_svg}
</svg>";

    $fileName = "preview_{$order['id']}_" . rand(1000, 9999) . ".svg";
    file_put_contents($upload_dir . $fileName, $svgContent);

    // --- FIN GENERACIÓN SVG ---

    $otros_datos = json_encode([
        'solosellos_order_id' => $order['id'],
        'cliente' => trim($order['name'] . ' ' . $order['lastname'])
    ]);

    $stmt_ruta_insert->execute([
        ':user_id' => $ruta_user_id,
        ':direccion' => $order['address'],
        ':lat' => $order['lat'],
        ':lng' => $order['lng'],
        ':whatsapp' => $order['phone'],
        ':imagen_sello' => $fileName, 
        ':otros_datos' => $otros_datos,
        ':ruta_id' => $ruta_id
    ]);
}

// 7. Redirigir directamente a la hoja de ruta ESPECIFICA
header('Location: ruta/hoja_de_ruta.php?ruta_id=' . $ruta_id);
exit();
?>