<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php'; // Adjust path if necessary

$order_id = 172; // The specific order ID you want to test

// Fetch order data for the specified ID
$stmt = $pdo->prepare("SELECT orders.* FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pedido con ID $order_id no encontrado.");
}

// Decode styles and prepare data for the renderer
$styles = json_decode($order['styles'], true);

$plantilla_data = [
    'id' => $order['template_id'],
    'nombre' => $order['template_name'] ?? 'Plantilla sin nombre' // Fallback if no name
];

for ($i = 1; $i <= 4; $i++) {
    $plantilla_data["linea$i"] = [
        'texto' => $order["text_line$i"],
        'fuente' => $styles['fuente'][$i-1] ?? 'Arial',
        'tamano' => $styles['tamano'][$i-1] ?? '16',
        'negrita' => $styles['bold'][$i-1] ?? false,
        'alineacion' => $styles['alineacion'][$i-1] ?? 'center',
        'margen' => $styles['margen_top'][$i-1] ?? '0',
        'mayuscula' => $styles['mayuscula'][$i-1] ?? false
    ];
}

// Prepare and load Google Fonts efficiently
$fonts_to_load = [];
if (is_array($styles) && isset($styles["fuente"])) {
    foreach ($styles["fuente"] as $font) {
        if (!empty($font)) {
            $fonts_to_load[] = $font;
        }
    }
}
$fonts_to_load = array_unique($fonts_to_load);

$google_fonts_url = '';
if (!empty($fonts_to_load)) {
    $font_families = [];
    foreach ($fonts_to_load as $font) {
        $font_families[] = 'family=' . urlencode($font) . ':wght@400;700';
    }
    $google_fonts_url = 'https://fonts.googleapis.com/css2?' . implode('&', $font_families) . '&display=swap';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Preview for Order <?= $order_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if (!empty($google_fonts_url)): ?>
        <link href="<?= $google_fonts_url ?>" rel="stylesheet">
    <?php endif; ?>

    <link rel="stylesheet" href="../assets/css/plantilla-preview.css">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .test-container {
            border: 2px solid #ccc;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        /* Styles for the black preview box */
        .plantilla-preview-wrapper {
            position: relative;
            width: 380px;
            height: 140px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: black; /* Explicitly black background */
            transform: scale(0.7);
            transform-origin: top left;
            color: black; /* Explicitly black text */
            text-align: center; /* Keep text-align for centering text within lines */
        }
    </style>
</head>
<body>

<div class="test-container">
    <h2>Preview for Order #<?= $order_id ?></h2>
    <div class="plantilla-preview-wrapper"
         id="final-preview"
         data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
        <?php
        $plantilla = $plantilla_data;
        include 'includes/_plantilla_preview.php';
        ?>
    </div>
</div>

<!-- JS del Renderizador -->
<script src="../assets/js/plantilla-renderer.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const previewWrapper = document.getElementById('final-preview');
    const templateDataAttr = previewWrapper.getAttribute('data-template-data');

    if (templateDataAttr) {
        try {
            const templateData = JSON.parse(templateDataAttr);
            console.log('Datos de la plantilla recibidos en JS:', templateData);
            const previewContainer = previewWrapper.querySelector('.plantilla-preview-container');

            if (previewContainer) {
                window.renderizarPlantilla(previewContainer, templateData);
            }
        } catch (e) {
            console.error('Error al renderizar la vista previa final:', e);
        }
    }
});
</script>

</body>
</html>