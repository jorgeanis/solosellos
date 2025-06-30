
<?php
$estado = $order['estado'];
$badgeClass = match($estado) {
    'Recibido' => 'badge-gray',
    'Confirmado' => 'badge-blue',
    'En Producción' => 'badge-yellow',
    'Cancelado' => 'badge-red',
    'Entregado' => 'badge-green',
    default => 'badge-gray'
};

require_once '../admin/includes/db.php';

$order_code = $_GET['order'] ?? null;
$link_code = $_GET['u'] ?? null;

if (!$order_code || !$link_code) {
    echo "Pedido no válido.";
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, 
           u.name AS admin_name,
           u.email AS admin_email,
           u.logo AS admin_logo,
           u.footer AS admin_footer,
           u.whatsapp AS admin_whatsapp,
           u.color_primary AS color,
           u.color_secundary AS color_secundary,
           m.title AS model_title,
           m.description AS model_description,
           m.image AS model_image,
           t.fuente_linea_1, t.fuente_linea_2, t.fuente_linea_3, t.fuente_linea_4,
           t.tamano_linea_1, t.tamano_linea_2, t.tamano_linea_3, t.tamano_linea_4,
           t.bold_linea_1, t.bold_linea_2, t.bold_linea_3, t.bold_linea_4,
           t.alineacion_linea_1, t.alineacion_linea_2, t.alineacion_linea_3, t.alineacion_linea_4,
           t.margen_top_linea_1, t.margen_top_linea_2, t.margen_top_linea_3, t.margen_top_linea_4
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN templates t ON o.template_id = t.id
    JOIN models m ON o.model_id = m.id
    WHERE o.order_code = ? AND u.link_code = ?
");
$stmt->execute([$order_code, $link_code]);
$order = $stmt->fetch();

if (!$order) {
    echo "Pedido no encontrado.";
    exit;
}

$color = $order['color'] ?? '#009688';

$fonts = [];
for ($i = 1; $i <= 4; $i++) {
    $fuente = $order["fuente_linea_$i"];
    if (!in_array($fuente, $fonts)) {
        $fonts[] = $fuente;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen del Pedido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php foreach ($fonts as $font): ?>
        <link href="https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $font) ?>:wght@400;700&display=swap" rel="stylesheet">
    <?php endforeach; ?>
    <style>

header {
    background-color: #fff;
    padding: 20px;
    border-bottom: 1px solid #ddd;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.header-logo img {
    height: 60px;
    border-radius: 6px;
}
.header-info {
    text-align: right;
}
.header-info h1 {
    font-size: 18px;
    margin: 0;
}
.header-info p {
    font-size: 14px;
    margin: 0;
    color: #666;
}

        :root {
            --color-secundario: <?= $order['color_secundary'] ?? '#555' ?>;
            --color-primario: <?= $color ?>;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            color: #333;
        }
        a.whatsapp-button {
        background-color: #25D366;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        display: inline-block;
        transition: background-color 0.3s;
    }

    a.whatsapp-button:hover {
        background-color: #1DA851;
    }

    footer {
            background: var(--color-secundario); color: #fff;
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .admin-
        .admin-logo {
            max-height: 60px;
        }
        .admin-info {
            text-align: right;
			
        }
        .admin-info h1 {
            margin: 0;
            font-size: 18px;
			color: #000000;
        }
        .admin-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            overflow: hidden;
        }
		.section2 {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .section-title {
  background: #e1f5fe;
  padding: 12px 18px;
  font-family: 'Roboto', sans-serif;
  font-weight: 600;
  font-size: 18px;
  border-radius: 10px 10px 0 0;
  color: #01579b;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
        .section-content {
  font-family: 'Roboto', sans-serif;
  font-size: 15px;
  line-height: 1.6;
  color: #444;
  background: #fff;
  border-radius: 0 0 10px 10px;
  padding: 20px;
  margin-bottom: 25px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
        .preview {
            transform: scale(0.8);
            transform-origin: top center;
        }
        .linea-prev {
            white-space: nowrap;
            width: 100%;
        }
        .modelo img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        a.whatsapp-button {
        background-color: #08A61F;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        display: inline-block;
        transition: background-color 0.3s;
    }

    a.whatsapp-button:hover {
        background-color: #1DA851;
    }

    footer {
            background: var(--color-secundario); color: #fff;
            font-size: 13px;
            text-align: center;
            color: #FFFFFF;
        }
        @media (min-width: 768px) {
            .container {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            .section {
                flex: 1 1 30%;
            }
        }
    
.badge {
  display: inline-block;
  padding: 4px 10px;
  font-size: 13px;
  border-radius: 20px;
  font-weight: bold;
  color: white;
}
.badge-gray    { background-color: #9e9e9e; }
.badge-blue    { background-color: #2196f3; }
.badge-yellow  { background-color: #fbc02d; color: #000; }
.badge-red     { background-color: #f44336; }
.badge-green   { background-color: #4caf50; }

</style>
</head>
<body>
    

    

<header>
    <div class="header-content">
        <div class="header-logo">
            <img src="../assets/images/<?= htmlspecialchars($order['admin_logo']) ?>" alt="Logo">
        </div>
        <div class="header-info">
            <h1><?= htmlspecialchars($order['admin_name']) ?></h1>
            <p><?= htmlspecialchars($order['admin_email']) ?></p>
            <p><a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['admin_whatsapp']) ?>" target="_blank">📱 <?= htmlspecialchars($order['admin_whatsapp']) ?></a></p>
        </div>
    </div>
</header>

<main class="container">

    <div class="section">
        <div class="section-title">Detalle del pedido</div>
        <div class="section-content">
            <strong>Nombre:</strong> <?= htmlspecialchars($order["name"]) ?> <?= htmlspecialchars($order["lastname"]) ?><br>
            <strong>Domicilio:</strong> <?= htmlspecialchars($order["address"]) ?><br>
            <strong>Teléfono:</strong> <?= htmlspecialchars($order["phone"]) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($order["email"]) ?><br>
            <strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($order["created_at"])) ?><br>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Texto del sello</div>
        <div class="section-content">
            <div class="preview">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <?php
                        $text = htmlspecialchars($order["text_line$i"]);
                        if (empty($text)) continue;
                        $font = $order["fuente_linea_$i"];
                        $size = $order["tamano_linea_$i"];
                        $bold = $order["bold_linea_$i"] ? "bold" : "normal";
                        $align = $order["alineacion_linea_$i"];
                        $margen = (int)($order["margen_top_linea_$i"]);
                    ?>
                    <div class="linea-prev" style="
                        font-family: '<?= $font ?>', sans-serif;
                        font-size: <?= $size ?>px;
                        font-weight: <?= $bold ?>;
                        text-align: <?= $align ?>;
                        margin-top: <?= $margen ?>px;
                    "><?= $text ?></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Modelo elegido</div>
        <div class="section-content modelo">
            <h4 style="margin: 10px 0; color: var(--color-secundario);"><?= htmlspecialchars($order['model_title']) ?></h4>
            <img src="../assets/images/<?= htmlspecialchars($order['model_image']) ?>" alt="<?= htmlspecialchars($order['model_title']) ?>">
            <p style="font-size: 14px; color: #555;"><?= htmlspecialchars($order['model_description']) ?></p>
        </div>
    </div>


    <div class="section2" style="text-align: center; margin-top: 30px;">
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['admin_whatsapp']) ?>?text=Ya%20realice%20el%20pedido%20con%20el%20numero%20<?= $order['id'] ?>" 
           class="whatsapp-button" target="_blank">
           Volver al Whatsapp
        </a>
    </div>

</main>


    <footer>
        <?= $order['admin_footer'] ?>
    </footer>


<!-- html2canvas para exportar el preview en alta calidad -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function descargarPNG() {
  const preview = document.querySelector(".preview");
  if (!preview) {
    alert("No se encontró el preview.");
    return;
  }
  html2canvas(preview, { scale: 3 }).then(canvas => {
    const link = document.createElement('a');
    link.download = 'sello.png';
    link.href = canvas.toDataURL("image/png");
    link.click();
  });
}
</script>
<script src="../assets/js/preview.js"></script>

</body>
</html>
