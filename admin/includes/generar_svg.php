<?php
// generar_svg.php
// Este archivo genera un SVG negativo con tipografía desde Google Fonts

if (!isset($_GET['order_id'])) {
    die('Falta el parámetro order_id');
}

$order_id = intval($_GET['order_id']);
require_once '../conexion.php'; // Asegurate de ajustar la ruta si es necesario

$sql = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Pedido no encontrado');
}

$order = $result->fetch_assoc();
$styles = json_decode($order['styles'], true);
$texts = [
    $order['text_line1'],
    $order['text_line2'],
    $order['text_line3'],
    $order['text_line4']
];

// Generar los bloques de texto SVG
$lineas_svg = "";
$import_fonts = [];

for ($i = 1; $i <= 4; $i++) {
    $text = htmlspecialchars($texts[$i - 1] ?? '');
    $font = $styles["fuente_linea_$i"] ?? 'Roboto';
    $size = $styles["tamano_linea_$i"] ?? 30;
    $bold = !empty($styles["bold_linea_$i"]) && $styles["bold_linea_$i"] === "true" ? 'bold' : 'normal';
    $align = $styles["alineacion_linea_$i"] ?? 'center';
    $top = $styles["margen_top_linea_$i"] ?? (40 + 40 * ($i - 1));

    $import_fonts[$font] = true;

    $lineas_svg .= "<text x='50%' y='{$top}' font-family='{$font}' font-size='{$size}' font-weight='{$bold}' fill='white' text-anchor='middle'>{$text}</text>\n";
}

$font_imports = '';
foreach ($import_fonts as $font => $_) {
    $encoded_font = str_replace(' ', '+', $font);
    $font_imports .= "@import url('https://fonts.googleapis.com/css2?family={$encoded_font}:wght@400;700&display=swap');\n";
}

$svg = "<?xml version='1.0' encoding='UTF-8' standalone='no'?>
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

header("Content-Type: image/svg+xml");
echo $svg;
?>
