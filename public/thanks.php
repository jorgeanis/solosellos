<?php
require_once '../admin/includes/db.php';

$order_code = $_GET['order'] ?? null;
$link_code = $_GET['u'] ?? null;

if (!$order_code || !$link_code) {
    die("Pedido no válido.");
}

// 1. OBTENER DATOS COMPLETOS DEL PEDIDO Y DEL USUARIO
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.name AS admin_name, u.email AS admin_email, u.logo AS admin_logo,
           u.footer AS admin_footer, u.whatsapp AS admin_whatsapp, u.color_primary AS color,
           u.referral_active,
           m.title AS model_title, m.description AS model_description, m.image AS model_image,
           t.nombre AS template_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN templates t ON o.template_id = t.id
    JOIN models m ON o.model_id = m.id
    WHERE o.order_code = ? AND u.link_code = ?
");
$stmt->execute([$order_code, $link_code]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pedido no encontrado.");
}

// 2. DECODIFICAR ESTILOS Y PREPARAR DATOS PARA EL RENDERIZADOR
$custom_styles = json_decode($order['styles'], true);

$plantilla_data = [
    'id' => $order['template_id'],
    'nombre' => $order['template_name']
];

for ($i = 1; $i <= 4; $i++) {
    $plantilla_data["linea$i"] = [
        'texto' => $order["text_line$i"],
        'fuente' => $custom_styles['fuente'][$i-1] ?? 'Arial',
        'tamano' => $custom_styles['tamano'][$i-1] ?? '16',
        'negrita' => $custom_styles['bold'][$i-1] ?? false,
        'alineacion' => $custom_styles['alineacion'][$i-1] ?? 'center',
        'margen' => $custom_styles['margen_top'][$i-1] ?? '0',
        'mayuscula' => $custom_styles['mayuscula'][$i-1] ?? false
    ];
}


// 3. PREPARAR Y CARGAR LAS FUENTES DE GOOGLE DE FORMA EFICIENTE
$fonts_to_load = [];
if (isset($custom_styles['fuente']) && is_array($custom_styles['fuente'])) {
    foreach ($custom_styles['fuente'] as $font) {
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

    // Check referral status for this user
    $referral_active = $order['referral_active'] ?? 0;
    $referral_link = "";
    if ($referral_active) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST']; // e.g., localhost or domain.com
        // Construct path relative to public/thanks.php -> public/index.php
        $scriptPath = dirname($_SERVER['PHP_SELF']); 
        // If scriptPath ends in /public, we are good.
        $baseUrl = $protocol . $domainName . str_replace('/thanks.php', '/index.php', $_SERVER['PHP_SELF']);
        
        $referral_link = $baseUrl . "?u=" . $link_code . "&ref=" . $order_code;
    }
?>
<!DOCTYPE html>
<html lang="es"><head>
    <meta charset="UTF-8">
    <title>¡Gracias por tu pedido!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if (!empty($google_fonts_url)): ?>
        <link href="<?= $google_fonts_url ?>" rel="stylesheet">
    <?php endif; ?>

    <link rel="stylesheet" href="../assets/css/plantilla-preview.css">

    <style>
        :root {
            --color-principal: <?= htmlspecialchars($order['color'] ?? '#009688') ?>;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 650px;
            margin: 30px auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .card-header {
            background-color: var(--color-principal);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .card-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .card-body {
            padding: 30px;

        }
        .preview-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #e0e0e0;
            /* Flexbox para centrar el contenido transformado */
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .preview-section h3 {
            margin-top: 0;
            color: #555;
            font-weight: 600;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .plantilla-preview-wrapper {
            position: relative;
            width: 380px;
            height: 140px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            transform: scale(0.7); /* Ajustado para mejor visibilidad */
            /* margin: 0 auto; ya no es necesario */
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Adaptable */
            gap: 20px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px dashed #e0e0e0;
        }
        .info-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .info-item strong {
            display: block;
            color: var(--color-principal); /* Color principal para los títulos */
            margin-bottom: 5px;
            font-size: 15px;
        }
        .model-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .model-info img {
            width: 50px; /* Tamaño pequeño para la imagen del modelo */
            height: 50px;
            object-fit: contain; /* Para que la imagen no se corte */
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 3px;
            background-color: #fff;
        }
        .whatsapp-button {
            display: block; /* Ocupar todo el ancho */
            background-color: #25D366;
            color: white;
            padding: 15px 25px;
            border-radius: 10px; /* Más redondeado */
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            margin-top: 30px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }
        .whatsapp-button:hover {
            background-color: #1DA851;
            transform: translateY(-2px);
        }
        
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>¡Gracias por tu pedido!</h1>
        </div>
        <div class="card-body">
            <p>Hemos recibido tu pedido correctamente con el número: <strong style="font-size: 1.1em;"><?php echo $order['id']; ?></strong>, por favor presiona el botón verde para volver al WhatsApp.</p>

                <div class="preview-section">
                
                <div class="plantilla-preview-wrapper" 
                     id="final-preview" 
                     data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
                    <?php 
                    // Pasamos la variable al scope del include
                    $plantilla = $plantilla_data; 
                    include '../admin/includes/_plantilla_preview.php'; 
                    ?>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="https://wa.me/+549<?= preg_replace('/[^0-9]/', '', $order['admin_whatsapp']) ?>?text=Hola!%20Acabo%20de%20realizar%20el%20pedido%20nro%20<?= $order['id'] ?>" 
                   class="whatsapp-button" target="_blank">
                   Volver al Whatsapp
                </a>
            </div>

            <?php if ($referral_active && !empty($referral_link)): ?>
            <div style="margin-top: 30px; background: #e8f5e9; border: 1px dashed #2ecc71; padding: 20px; border-radius: 10px; text-align: center;">
                <h3 style="color: #27ae60; margin-top: 0;">🎁 ¡Regala un descuento!</h3>
                <p style="margin-bottom: 15px;">Comparte este enlace con tus amigos. Si compran usándolo, obtendrán un descuento especial.</p>
                <div style="background: white; padding: 10px; border: 1px solid #ccc; border-radius: 5px; word-break: break-all; font-family: monospace; font-size: 14px; margin-bottom: 10px;">
                    <?= $referral_link ?>
                </div>
                <button onclick="navigator.clipboard.writeText('<?= $referral_link ?>'); alert('Enlace copiado!');" style="background: #27ae60; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    Copiar enlace
                </button>
                <a href="https://wa.me/?text=¡Hola!%20Te%20regalo%20un%20descuento%20para%20tu%20sello%20personalizado.%20Usá%20este%20link:%20<?= urlencode($referral_link) ?>" target="_blank" style="display: inline-block; margin-left: 10px; background: #25D366; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-weight: bold;">
                    Compartir en WhatsApp
                </a>
            </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item">
                    <strong>Nombre:</strong> <?= htmlspecialchars($order["name"]) ?> <?= htmlspecialchars($order["lastname"]) ?>
                </div>
                <div class="info-item">
                    <strong>Teléfono:</strong> <?= htmlspecialchars($order["phone"]) ?>
                </div>
                <div class="info-item">
                    <strong>Dirección:</strong> <?= htmlspecialchars($order["address"]) ?>
                </div>
                <div class="info-item">
                    <strong>Modelo:</strong> 
                    <div class="model-info">
                        <?php if (!empty($order['model_image'])): ?>
                            <img src="../assets/images/<?= htmlspecialchars($order['model_image']) ?>" alt="<?= htmlspecialchars($order['model_title']) ?>" title="<?= htmlspecialchars($order['model_title']) ?>">
                        <?php endif; ?>
                        <span><?= htmlspecialchars($order['model_title']) ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <strong>Plantilla:</strong> <?= htmlspecialchars($order['template_name']) ?>
                </div>
                <div class="info-item">
                    <strong>Importe:</strong> $<?= number_format($order['price'], 2) ?>
                </div>
                <?php if (!empty($order['comments'])): ?>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <strong>Indicaciones:</strong> <?= nl2br(htmlspecialchars($order['comments'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
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
            console.log('Datos de la plantilla recibidos en JS:', templateData); // Añadido para depuración
            const previewContainer = previewWrapper.querySelector('.plantilla-preview-container');
            
            if (previewContainer) {
                // Usamos la función global para renderizar la vista previa
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