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
    .plantilla-item {
        border: 2px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        padding: 0;
        width: 160px; /* Ancho fijo para el contenedor */
        height: 70px; /* Alto fijo para el contenedor */
        overflow: hidden; /* Ocultar cualquier contenido que se desborde */
        box-sizing: border-box; /* Asegura que el padding y borde no afecten el tamaño final */
    }
    .plantilla-preview-container-scaled {
        transform: scale(0.38);
        transform-origin: top left;
        width: 380px; /* Ancho original del contenido */
        height: 140px; /* Alto original del contenido */
    }
    .plantilla-item.active {
        border-color: var(--color-principal);
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
        margin-bottom: 10px;
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
  <div class="bienvenida" style="text-align: center;">
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
        <div class="modelo-card" onclick="seleccionarModelo(this, <?= $model['id'] ?>)">
          <img src="../assets/images/<?= htmlspecialchars($model['image']) ?>" alt="<?= htmlspecialchars($model['title']) ?>">
          <br>
          <small>$<?= number_format($model['price'], 0) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" id="btn-next" class="boton-siguiente">Siguiente</button>
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
  <input type="radio" name="template_id" value="" required style="display:none;">

  <div class="form-container">
    <div class="input-line-item">
        <input type="text" name="linea1" placeholder="Línea 1" id="linea1_input" value="<?= htmlspecialchars($_GET['linea1'] ?? '') ?>" required>
        <input type="checkbox" id="chk_linea1" name="chk_linea1" checked>
    </div>
    <div class="input-line-item">
        <input type="text" name="linea2" placeholder="Línea 2" id="linea2_input" value="<?= htmlspecialchars($_GET['linea2'] ?? '') ?>">
        <input type="checkbox" id="chk_linea2" name="chk_linea2" checked>
    </div>
    <div class="input-line-item">
        <input type="text" name="linea3" placeholder="Línea 3" id="linea3_input" value="<?= htmlspecialchars($_GET['linea3'] ?? '') ?>">
        <input type="checkbox" id="chk_linea3" name="chk_linea3" checked>
    </div>
    <div class="input-line-item">
        <input type="text" name="linea4" placeholder="Línea 4" id="linea4_input" value="<?= htmlspecialchars($_GET['linea4'] ?? '') ?>">
        <input type="checkbox" id="chk_linea4" name="chk_linea4" checked>
    </div>
  
    <hr style="margin: 20px 0;">

    <div class="modelo-lista">
        <?php foreach ($plantillas as $plantilla): ?>
          <?php $contenido = json_decode($plantilla["content"], true); ?>
          <label class="plantilla-item" onclick="seleccionarPlantilla(this, <?= $plantilla['id'] ?>)"
                 data-template-id="<?= $plantilla['id'] ?>"
                 data-template-data='<?= htmlspecialchars(json_encode([
                     'id' => $plantilla['id'],
                     'linea1' => ['texto' => $contenido['linea1'] ?? '', 'fuente' => $plantilla['fuente_linea_1'], 'tamano' => $plantilla['tamano_linea_1'], 'negrita' => !empty($plantilla['bold_linea_1']), 'alineacion' => $plantilla['alineacion_linea_1'], 'margen' => $plantilla['margen_top_linea_1']],
                     'linea2' => ['texto' => $contenido['linea2'] ?? '', 'fuente' => $plantilla['fuente_linea_2'], 'tamano' => $plantilla['tamano_linea_2'], 'negrita' => !empty($plantilla['bold_linea_2']), 'alineacion' => $plantilla['alineacion_linea_2'], 'margen' => $plantilla['margen_top_linea_2']],
                     'linea3' => ['texto' => $contenido['linea3'] ?? '', 'fuente' => $plantilla['fuente_linea_3'], 'tamano' => $plantilla['tamano_linea_3'], 'negrita' => !empty($plantilla['bold_linea_3']), 'alineacion' => $plantilla['alineacion_linea_3'], 'margen' => $plantilla['margen_top_linea_3']],
                     'linea4' => ['texto' => $contenido['linea4'] ?? '', 'fuente' => $plantilla['fuente_linea_4'], 'tamano' => $plantilla['tamano_linea_4'], 'negrita' => !empty($plantilla['bold_linea_4']), 'alineacion' => $plantilla['alineacion_linea_4'], 'margen' => $plantilla['margen_top_linea_4']]
                 ]), ENT_QUOTES, 'UTF-8') ?>'>
            <div class="plantilla-preview-container-scaled">
              <div class="plantilla-preview-wrapper" id="template-preview-<?= $plantilla['id'] ?>">
                  <?php include '../admin/includes/_plantilla_preview.php'; ?>
              </div>
            </div>
          </label>
        <?php endforeach; ?>
    </div>
  </div>
  <button type="submit" class="boton-siguiente active">Siguiente</button>
</form>
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
    document.querySelectorAll('.plantilla-item').forEach(card => card.classList.remove('active'));
    el.classList.add('active');
    document.querySelector('input[name="template_id"]').value = templateId;
    
    const templateDataAttr = el.getAttribute('data-template-data');
    if (templateDataAttr) {
        currentTemplateData = JSON.parse(templateDataAttr);
        
        const hasPreviousData = <?= (isset($_GET['linea1']) && $_GET['linea1'] !== '') ? 'true' : 'false' ?>;

        if (!hasPreviousData) {
            for (let i = 1; i <= 4; i++) {
                const inputElement = document.getElementById(`linea${i}_input`);
                const chkElement = document.getElementById(`chk_linea${i}`);
                if (inputElement && chkElement) {
                    const texto = currentTemplateData['linea' + i] ? currentTemplateData['linea' + i].texto : '';
                    inputElement.value = texto;
                    chkElement.checked = (texto !== '');
                }
            }
        }
        updateRealtimePreview();
    }
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
            const chkElement = document.getElementById(`chk_linea${i}`);
            datosParaRender[`linea${i}`].texto = inputElement ? inputElement.value : '';
            if (chkElement && !chkElement.checked) {
                datosParaRender[`linea${i}`].texto = '';
            }
        }
        
        window.renderizarPlantilla(previewContainer, datosParaRender);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const currentStep = "<?= htmlspecialchars($step ?? '') ?>";
    if (currentStep !== '2') return;

    document.querySelectorAll('input[id^="linea"], input[id^="chk_linea"]').forEach(input => {
        input.addEventListener('input', updateRealtimePreview);
        input.addEventListener('change', updateRealtimePreview);
    });

    const preselectedTemplateId = "<?= htmlspecialchars($_GET['template_id'] ?? '') ?>";
    let cardToSelect = document.querySelector(`.plantilla-item[data-template-id="${preselectedTemplateId}"]`);
    if (!cardToSelect) {
        cardToSelect = document.querySelector('.plantilla-item[data-template-id]');
    }

    if (cardToSelect) {
        seleccionarPlantilla(cardToSelect, cardToSelect.dataset.templateId);
    } else {
        updateRealtimePreview();
    }
});
</script>
</body>
</html>