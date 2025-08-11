<?php
require_once '../admin/includes/db.php';

if (!isset($_GET['u'])) {
    die('Enlace inválido');
}

$link_code = $_GET['u'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE link_code = ? AND active = 1");
$stmt->execute([$link_code]);
$user = $stmt->fetch();

if (!$user) {
    die("El enlace no es válido o el usuario fue desactivado.");
}

$user_id = $user['id'];
$step = $_GET['step'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM models WHERE user_id = ?");
$stmt->execute([$user_id]);
$models = $stmt->fetchAll();

$todas_las_fuentes = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Merriweather', 'Nunito', 'Oswald', 'Ubuntu', 'PT Sans', 'Quicksand', 'Work Sans', 'Bebas Neue', 'Archivo', 'Fira Sans', 'Playfair Display', 'Rubik', 'Caveat', 'Dancing Script', 'Shadows Into Light', 'Satisfy', 'Great Vibes', 'Permanent Marker', 'Patrick Hand', 'Gloria Hallelujah', 'Indie Flower', 'Fredoka', 'Josefin Sans', 'Amatic SC'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido de Sello</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/plantilla-preview.css">
    <?php
    foreach ($todas_las_fuentes as $fuente) {
        $nombre_fuente = str_replace(' ', '+', $fuente);
        echo "<link href='https://fonts.googleapis.com/css2?family={$nombre_fuente}&display=swap' rel='stylesheet'>\n";
    }
    ?>
<style>
    :root {
      --color-principal: <?= htmlspecialchars($user['color_primary']) ?>;
      --fuente-principal: '<?= htmlspecialchars($user['font_family']) ?>', sans-serif;
    }
    body {
      margin: 0;
      font-family: var(--fuente-principal);
      background: #f5f5f5;
      padding: 0; /* Eliminado el padding */
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      justify-content: space-between;
      overflow-x: hidden;
      <?php $bg = isset($user['background_image']) ? '../assets/images/bg/' . $user['background_image'] : ''; ?>
      <?= $bg ? 'background-image: url(' . $bg . '); background-size: cover; background-position: center; background-attachment: fixed;' : '' ?>
    }
    .container { 
        max-width: 500px; 
        margin: 0 auto; 
        width: 100%;
        padding-top: 20px; /* Añadido para compensar */
    }
    .titulo {
        background-color: #e1f5fe;
        padding: 14px 18px;
        text-align: center;
        font-size: 20px;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        color: #01579b;
        width: 90%;
        margin: 0 auto 20px;
    }
    .modelo-lista {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      justify-content: center;
      max-width: 360px;
      margin: 0 auto;
    }
    .modelo-card {
      background: white;
      border: 2px solid transparent;
      border-radius: 12px;
      width: 100%;
      max-width: 200px;
      padding: 5px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
    }
    .modelo-card:hover {
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .modelo-card img {
      max-width: 100%; max-height: 100px; object-fit: contain; margin-bottom: 8px;
    }
    .modelo-card.active {
      border-color: var(--color-principal);
    }
    .modelo-card.sin-stock {
      position: relative;
      cursor: not-allowed;
      opacity: 0.7;
    }
    .modelo-card.sin-stock img {
      filter: blur(2px) grayscale(80%);
    }
    .stock-overlay {
      position: absolute;
      top: 40%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: rgba(217, 30, 24, 0.85);
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      font-weight: bold;
      font-size: 14px;
      z-index: 1;
      text-transform: uppercase;
    }

    .plantilla-item {
        border: 2px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        padding: 0;
        position: relative; /* Add this */
        width: calc(100% - 10px); /* Ancho flexible para el contenedor */
        max-width: 300px; /* Limitar el ancho máximo */
        height: 165px; /* Alto fijo para el contenedor */
        overflow: hidden; /* Ocultar cualquier contenido que se desborde */
        box-sizing: border-box; /* Asegura que el padding y borde no afecten el tamaño final */
    }
    .plantilla-preview-container-scaled {
        position: absolute;
        top: 40%;
        left: 40%;
        transform: translate(-50%, -50%) scale(0.68);
        width: 380px; /* Ancho original del contenido */
        height: 140px; /* Alto original del contenido */
    }
    .plantilla-item.active {
        border-color: var(--color-principal);
    }
    .btn-elegir {
      position: absolute;
      bottom: 5px;
      left: 50%;
      transform: translateX(-50%);
      background-color: var(--color-principal);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      z-index: 10; /* Ensure it's above the preview */
      transition: background-color 0.3s ease;
    }
    .btn-elegir:hover {
      background-color: #0056b3; /* Darker shade of primary color */
    }
    .boton-siguiente {
      margin: 25px auto 0 auto;
      width: 60%;
      padding: 14px;
      background: var(--color-principal);
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      display: none;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      font-weight: bold;
      cursor: pointer;
    }
    .boton-siguiente.active { display: block; }
    .boton-siguiente:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }
    input, select, textarea {
        border-radius: 8px;
        padding: 8px;
        border: 1px solid #ccc;
        width: 100%;
        box-sizing: border-box;
        font-size: 14px;
        margin-top: 5px;
        margin-bottom: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s ease-in-out;
        text-align: center;
    }
    input:focus, select, textarea:focus {
        outline: none;
        border-color: var(--color-principal);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }
    .form-container {
        background: #ffffff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 25px;
    }
    .input-line-item {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    .input-line-item input[type="text"] {
        flex-grow: 1;
        margin-right: 15px;
        margin-bottom: 0;
    }
    .input-line-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin: 0;
        flex-shrink: 0;
        accent-color: var(--color-principal);
    }
    .input-line-item input[type="checkbox"]:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* Estilos para el Editor del Cliente (Paso 3) */
    .editor-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .controles-col {
        flex: 1 1 100%; /* Allow it to shrink and take full width when wrapped */
        min-width: 0; /* Allow it to shrink below 280px if needed */
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .preview-col {
        flex: 1 1 100%; /* Allow it to shrink and take full width when wrapped */
        min-width: 0; /* Allow it to shrink below its content width if needed */
        display: flex;
        justify-content: center;
        align-items: center;
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        min-height: 200px;
    }
    .tab-buttons {
        display: flex;
        gap: 5px;
        margin-bottom: 15px;
    }
    .tab-btn {
        padding: 8px 12px;
        border: 1px solid #ccc;
        background-color: #f0f0f0;
        cursor: pointer;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    .tab-btn.active {
        background-color: var(--color-principal);
        color: white;
        border-color: var(--color-principal);
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .line-controls label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }
    .line-controls input[type="text"], .line-controls input[type="number"], .line-controls select {
        width: 100%;
    }
    select[name^="fuente"] {
        display: none;
    }
    .font-size-control {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
        margin-bottom: 10px;
    }
    .btn-font-size {
        background-color: #f0f0f0;
        border: 1px solid #ccc;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-font-size:hover {
        background-color: #e0e0e0;
    }
    .btn-margin-top {
        background-color: #f0f0f0;
        border: 1px solid #ccc;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-margin-top:hover {
        background-color: #e0e0e0;
    }
    .current-font-size {
        flex-grow: 1;
        text-align: center;
        font-weight: bold;
        font-size: 1.1em;
    }
    .alineacion-btns {
        display: flex;
        gap: 5px;
        margin-top: 5px;
    }
    .alineacion-btns button {
        flex: 1;
        padding: 6px;
        border: 1px solid #ccc;
        background: #f0f0f0;
        cursor: pointer;
    }
    .alineacion-btns button.active {
        background-color: #4CAF50;
        color: white;
    }
</style>
</head>
<body>
<header style="display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; background-color: #fff; border-bottom: 1px solid #ccc; font-family: Roboto, sans-serif; width: 100%; box-sizing: border-box;">
  <div style="display: flex; align-items: center;">
    <img src="../assets/images/<?= htmlspecialchars($user['logo']) ?>" alt="Logo" style="max-height: 50px; margin-right: 15px;">
    <span style="font-size: 20px; font-weight: bold;"><?= htmlspecialchars($user['name']) ?></span>
  </div>
  <div style="text-align: right;">
    <span style="display: block; font-size: 16px;">WhatsApp:</span>
    <a href="https://wa.me/+549<?= preg_replace('/[^0-9]/i', '', $user['whatsapp']) ?>" target="_blank" style="color: #0F7033FF; font-size: 16px; text-decoration: none; font-weight: bold;">
      <?= htmlspecialchars($user['whatsapp']) ?>
    </a>
  </div>
</header>

<div class="container">
<?php if (!$step): ?>
  <div class="bienvenida" style="text-align: center; margin: 0 20px;">
    <h1>¡Bienvenido!</h1>
    <div class="bienvenida" style="text-align: center; margin-bottom: 20px;">
      <?= !empty($user['welcome']) ? $user['welcome'] : 'Estás por realizar un pedido de sello personalizado para ' . htmlspecialchars($user['name']) . '.' ?>
    </div>
    <form action="index.php" method="get">
      <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
      <input type="hidden" name="step" value="1">
      <button type="submit" style="padding:12px 20px; background:var(--color-principal); color:white; border:none; border-radius:8px;">Comenzar pedido</button>
    </form>
  </div>
<?php elseif ($step == 1): ?>
  <h2 class="titulo">1. Elegí un modelo</h2>
  <form method="get" action="index.php">
    <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="model_id" id="model_id">
    <div class="modelo-lista">
      <?php foreach ($models as $model): ?>
        <?php $sin_stock = $model['stock'] == 0; ?>
        <div class="modelo-card <?= $sin_stock ? 'sin-stock' : '' ?>" <?= !$sin_stock ? 'onclick="seleccionarModelo(this, ' . $model['id'] . ')"' : '' ?>>
          <?php if ($sin_stock): ?>
            <div class="stock-overlay">SIN STOCK</div>
          <?php endif; ?>
          <img src="../assets/images/<?= htmlspecialchars($model['image']) ?>" alt="<?= htmlspecialchars($model['title']) ?>">
          <br>
          <small>$<?= number_format($model['price'], 0) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    
  </form>

<?php elseif ($step == 2):
  $model_id = $_GET['model_id'] ?? null;
  if (!$model_id) die("Modelo no especificado.");

  $stmt = $pdo->prepare("SELECT * FROM templates WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $plantillas = $stmt->fetchAll();
?>
<h2 class="titulo">2. Ingresá el texto y elegí un diseño</h2>
<form method="get" action="index.php">
  <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
  <input type="hidden" name="step" value="3">
  <input type="hidden" name="model_id" value="<?= htmlspecialchars($model_id) ?>">
  <input type="hidden" name="template_id" value="" required>

  <div class="form-container">
    <div class="input-line-item">
        <input type="text" name="linea1" placeholder="Línea 1" id="linea1_input" required>
        <input type="checkbox" id="chk_linea1" checked disabled>
    </div>
    <div class="input-line-item">
        <input type="text" name="linea2" placeholder="Línea 2" id="linea2_input">
        <input type="checkbox" id="chk_linea2" checked>
    </div>
    <div class="input-line-item">
        <input type="text" name="linea3" placeholder="Línea 3" id="linea3_input">
        <input type="checkbox" id="chk_linea3" checked>
    </div>
    <div class="input-line-item">
        <input type="text" name="linea4" placeholder="Línea 4" id="linea4_input">
        <input type="checkbox" id="chk_linea4" checked>
    </div>
  
    <hr style="margin: 20px 0;">

    <div class="modelo-lista">
        <?php foreach ($plantillas as $plantilla): ?>
          <?php $contenido = json_decode($plantilla["content"], true); ?>
          <label class="plantilla-item"
                 data-template-id="<?= $plantilla['id'] ?>"
                 data-template-data='<?= htmlspecialchars(json_encode([
                     'id' => $plantilla['id'],
                     'linea1' => ['texto' => $contenido['linea1'] ?? '', 'fuente' => $plantilla['fuente_linea_1'], 'tamano' => $plantilla['tamano_linea_1'], 'negrita' => !empty($plantilla['bold_linea_1']), 'alineacion' => $plantilla['alineacion_linea_1'], 'margen' => $plantilla['margen_top_linea_1'], 'mayuscula' => !empty($plantilla['mayus_linea_1'])],
                     'linea2' => ['texto' => $contenido['linea2'] ?? '', 'fuente' => $plantilla['fuente_linea_2'], 'tamano' => $plantilla['tamano_linea_2'], 'negrita' => !empty($plantilla['bold_linea_2']), 'alineacion' => $plantilla['alineacion_linea_2'], 'margen' => $plantilla['margen_top_linea_2'], 'mayuscula' => !empty($plantilla['mayus_linea_2'])],
                     'linea3' => ['texto' => $contenido['linea3'] ?? '', 'fuente' => $plantilla['fuente_linea_3'], 'tamano' => $plantilla['tamano_linea_3'], 'negrita' => !empty($plantilla['bold_linea_3']), 'alineacion' => $plantilla['alineacion_linea_3'], 'margen' => $plantilla['margen_top_linea_3'], 'mayuscula' => !empty($plantilla['mayus_linea_3'])],
                     'linea4' => ['texto' => $contenido['linea4'] ?? '', 'fuente' => $plantilla['fuente_linea_4'], 'tamano' => $plantilla['tamano_linea_4'], 'negrita' => !empty($plantilla['bold_linea_4']), 'alineacion' => $plantilla['alineacion_linea_4'], 'margen' => $plantilla['margen_top_linea_4'], 'mayuscula' => !empty($plantilla['mayus_linea_4'])]
                 ]), ENT_QUOTES, 'UTF-8') ?>'>
            <div class="plantilla-preview-container-scaled">
              <div class="plantilla-preview-wrapper" id="template-preview-<?= $plantilla['id'] ?>">
                  <?php include '../admin/includes/_plantilla_preview.php'; ?>
              </div>
            </div>
            <button type="button" class="btn-elegir" style="display: none;" data-template-id="<?= $plantilla['id'] ?>">ELEGIR</button>
          </label>
        <?php endforeach; ?>
    </div>
  </div>
  <button type="submit" class="boton-siguiente active">Siguiente</button>
</form>
<?php elseif ($step == 3):
  // 1. Recuperar datos del paso anterior
  $model_id = $_GET['model_id'] ?? null;
  $template_id = $_GET['template_id'] ?? null;
  if (!$model_id || !$template_id) die("Faltan datos para continuar.");

  // 2. Obtener datos de la plantilla base
  $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
  $stmt->execute([$template_id]);
  $plantilla_base = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$plantilla_base) die("Plantilla no encontrada.");

  // 3. Recuperar texto del usuario
  $lineas_texto = [
      1 => $_GET['linea1'] ?? '',
      2 => $_GET['linea2'] ?? '',
      3 => $_GET['linea3'] ?? '',
      4 => $_GET['linea4'] ?? ''
  ];
?>
<h2 class="titulo">3. Personalizá tu diseño</h2>

<form method="post" action="submit_order.php">
    <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ">
    <input type="hidden" name="model_id" value="<?= htmlspecialchars($model_id) ">
    <input type="hidden" name="template_id" value="<?= htmlspecialchars($template_id) ">
    <?php foreach ($lineas_texto as $num => $texto): ?>
        <input type="hidden" name="linea<?= $num ">" value="<?= htmlspecialchars($texto) ">
    <?php endforeach; ?>

    <div class="editor-wrapper">
        <!-- Columna de Controles -->
        <div class="controles-col">
            <div class="form-group">
                <label for="font-size-slider">Tamaño del Texto:</label>
                <input type="range" class="form-control-range" id="font-size-slider" name="global_font_size" min="10" max="50" value="24" step="1">
                <span id="font-size-value">24px</span>
            </div>
            <div class="form-group">
                <label for="line-spacing-slider">Espaciado entre Líneas:</label>
                <input type="range" class="form-control-range" id="line-spacing-slider" name="global_line_spacing" min="0.5" max="2.5" value="1.2" step="0.1">
                <span id="line-spacing-value">1.2</span>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="bold-checkbox" name="global_bold">
                <label class="form-check-label" for="bold-checkbox">Negrita</label>
            </div>
        </div>

        <!-- Columna de Vista Previa -->
        <div class="preview-col">
            <div id="editor-preview-container" style="transform: scale(0.8); transform-origin: center;">
                <?php
                    // Pasar los datos combinados a la plantilla de vista previa
                    $plantilla = $datos_plantilla_para_preview;
                    include '../admin/includes/_plantilla_preview.php';
                ?>
            </div>
        </div>
    </div>
    <button type="submit" class="boton-siguiente active" style="width:100%; margin-top: 20px;">Finalizar y Pedir</button>
</form>

<?php endif; ?>

<?php endif; ?>
</div>

<footer style="background-color: #fff; border: 1px solid #ccc; padding: 10px 20px; font-family: Roboto, sans-serif; border-radius: 8px; width: calc(85% - 20px); max-width: 95%; text-align: center; margin: 40px auto 20px auto;">
  <?= isset($user['footer']) ? $user['footer'] : '' ?>
</footer>

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
let currentTemplateData = null;

function seleccionarModelo(el, modelId) {
    document.querySelectorAll('.modelo-card').forEach(card => card.classList.remove('active'));
    el.classList.add('active');
    const modelIdInput = document.getElementById('model_id');
    if (modelIdInput) modelIdInput.value = modelId;
    const nextBtn = document.getElementById('btn-next');
    if (nextBtn) nextBtn.classList.add('active');
}

function seleccionarPlantilla(el, templateId) {
    document.querySelectorAll('.plantilla-item').forEach(card => {
        card.classList.remove('active');
        const elegirBtn = card.querySelector('.btn-elegir');
        if (elegirBtn) elegirBtn.style.display = 'none'; // Hide all buttons
    });
    el.classList.add('active');
    const selectedElegirBtn = el.querySelector('.btn-elegir');
    if (selectedElegirBtn) selectedElegirBtn.style.display = 'block'; // Show selected button
    document.querySelector('input[name="template_id"]').value = templateId;
}

function updateRealtimePreview() {
    document.querySelectorAll('.plantilla-item[data-template-id]').forEach(card => {
        const templateId = card.getAttribute('data-template-id');
        const templateDataAttr = card.getAttribute('data-template-data');
        if (!templateDataAttr) return;

        const templateData = JSON.parse(templateDataAttr);
        const previewWrapper = document.getElementById('template-preview-' + templateId);
        const previewContainer = previewWrapper ? previewWrapper.querySelector('.plantilla-preview-container') : null;
        if (!previewContainer) return;

        const datosParaRender = JSON.parse(JSON.stringify(templateData));

        for (let i = 1; i <= 4; i++) {
            const inputElement = document.getElementById(`linea${i}_input`);
            const checkboxElement = document.getElementById(`chk_linea${i}`);

            // Si el checkbox existe y no está marcado, la línea se oculta (texto vacío).
            if (checkboxElement && !checkboxElement.checked) {
                datosParaRender[`linea${i}`].texto = '';
            } else if (inputElement && inputElement.value) {
                // Si el campo de texto tiene valor, se usa ese valor.
                datosParaRender[`linea${i}`].texto = inputElement.value;
            }
            // Si ninguna de las condiciones anteriores se cumple, se usa el texto por defecto de la plantilla,
            // que ya está cargado en datosParaRender.
        }
        
        window.renderizarPlantilla(previewContainer, datosParaRender);
    });
}

function updateDisabledStates() {
    for (let i = 2; i <= 4; i++) {
        const chk = document.getElementById(`chk_linea${i}`);
        if (!chk) continue;

        const prevChk = document.getElementById(`chk_linea${i - 1}`);
        const nextChk = document.getElementById(`chk_linea${i + 1}`);

        let shouldBeDisabled = false;
        if (chk.checked) {
            // Si está marcado, no se puede desmarcar si el siguiente está marcado.
            if (nextChk && nextChk.checked) {
                shouldBeDisabled = true;
            }
        } else {
            // Si está desmarcado, no se puede marcar si el anterior no está marcado.
            if (prevChk && !prevChk.checked) {
                shouldBeDisabled = true;
            }
        }
        chk.disabled = shouldBeDisabled;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const currentStep = "<?= htmlspecialchars($step ?? '') ?>";

    if (currentStep === '2') {
        // Initial render of previews with default text
        document.querySelectorAll('.plantilla-item[data-template-id]').forEach(card => {
            const templateDataAttr = card.getAttribute('data-template-data');
            if (!templateDataAttr) return;
            const templateData = JSON.parse(templateDataAttr);
            const previewWrapper = document.getElementById('template-preview-' + templateData.id);
            const previewContainer = previewWrapper ? previewWrapper.querySelector('.plantilla-preview-container') : null;
            if (previewContainer) {
                window.renderizarPlantilla(previewContainer, templateData);
            }
        });

        document.querySelectorAll('input[id^="linea"]').forEach(input => {
            input.addEventListener('input', updateRealtimePreview);
        });

        document.querySelectorAll('input[id^="chk_linea"]').forEach(checkbox => {
            if (!checkbox.disabled) {
                checkbox.addEventListener('change', () => {
                    updateDisabledStates();
                    updateRealtimePreview();
                });
            }
        });

        // Initial state setup
        updateDisabledStates();

        document.querySelectorAll('.plantilla-item').forEach(card => {
            card.addEventListener('click', (event) => {
                // Prevent default form submission if the click is not on the button
                if (!event.target.classList.contains('btn-elegir')) {
                    event.preventDefault(); // Add this line
                    seleccionarPlantilla(card, card.dataset.templateId);
                }
            });
        });

        // Pre-select the first template without affecting inputs
        const firstTemplate = document.querySelector('.plantilla-item');
        if (firstTemplate) {
            seleccionarPlantilla(firstTemplate, firstTemplate.dataset.templateId);
        }

        // Event listener for ELEGIR buttons
        document.querySelectorAll('.btn-elegir').forEach(button => {
            button.addEventListener('click', () => {
                const templateId = button.dataset.templateId;
                document.querySelector('input[name="template_id"]').value = templateId;
                // Submit the form
                button.closest('form').submit();
            });
        });
    }

    // --- LÓGICA DEL EDITOR (PASO 3) ---
    if (currentStep === '3') {
        const form = document.querySelector('form');
        const previewContainer = document.getElementById('editor-preview-container');

        window.actualizarVistaPreviaEditor = function() {
            const datos = { linea1: {}, linea2: {}, linea3: {}, linea4: {} };

            for (let i = 1; i <= 4; i++) {
                const negritaCheckbox = form.querySelector(`input[type="checkbox"][name="negrita${i}"]`);
                datos['linea' + i] = {
                    texto: form.querySelector(`[name="linea${i}_texto"]`).value,
                    fuente: form.querySelector(`[name="fuente${i}"]`).value,
                    tamano: form.querySelector(`[name="tamano${i}"]`).value,
                    negrita: negritaCheckbox ? negritaCheckbox.checked : false,
                    alineacion: form.querySelector(`[name="alineacion${i}"]`).value,
                    margen: form.querySelector(`[name="margen_top${i}"]`).value
                };
            }
            console.log('previewContainer:', previewContainer);
            console.log('datos:', datos);
            window.renderizarPlantilla(previewContainer, datos);
        }

        form.addEventListener('input', actualizarVistaPreviaEditor);
        form.addEventListener('change', actualizarVistaPreviaEditor);

        // Font size controls
        document.querySelectorAll('.btn-font-size').forEach(button => {
            button.addEventListener('click', (event) => {
                const action = button.dataset.action;
                const line = button.dataset.line;
                const hiddenInput = form.querySelector(`input[name="tamano${line}"]`);
                const currentSizeSpan = document.getElementById(`current_font_size_${line}`);
                let currentSize = parseInt(hiddenInput.value);

                if (action === 'increase') {
                    currentSize++;
                } else {
                    currentSize--;
                }

                // Clamp values between 8 and 100
                currentSize = Math.max(8, Math.min(100, currentSize));

                hiddenInput.value = currentSize;
                currentSizeSpan.textContent = `${currentSize}px`;
                actualizarVistaPreviaEditor();
            });
        });

        // Margin Top controls
        document.querySelectorAll('.btn-margin-top').forEach(button => {
            button.addEventListener('click', (event) => {
                const action = button.dataset.action;
                const line = button.dataset.line;
                const hiddenInput = form.querySelector(`input[name="margen_top${line}"]`);
                let currentMargin = parseInt(hiddenInput.value);

                if (action === 'increase') {
                    currentMargin++;
                } else {
                    currentMargin--;
                }

                // Clamp values (e.g., between -50 and 50, adjust as needed)
                currentMargin = Math.max(-50, Math.min(50, currentMargin)); // Example range

                hiddenInput.value = currentMargin;
                actualizarVistaPreviaEditor();
            });
        });

        window.showTab = function(n) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.getElementById('tab' + n).classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            form.querySelector(`.tab-buttons button:nth-child(${n})`).classList.add('active');
        }

        window.setAlign = function(linea, direccion, btn) {
            document.getElementById('alineacion' + linea).value = direccion;
            const parent = btn.parentElement;
            parent.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            actualizarVistaPreviaEditor();
        }

        actualizarVistaPreviaEditor();

        for (let i = 1; i <= 4; i++) {
            const alignValue = document.getElementById('alineacion' + i).value;
            if (alignValue) {
                const alignButton = form.querySelector(`.alineacion-btns[data-linea="${i}"] button[onclick*="'${alignValue}'"]`);
                if (alignButton) alignButton.classList.add('active');
            }
        }
    }
});

</script>
</body>
</html>