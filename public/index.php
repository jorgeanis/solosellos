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

// Cargar modelos del admin
$stmt = $pdo->prepare("SELECT * FROM models WHERE user_id = ?");
$stmt->execute([$user_id]);
$models = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<style>
    :root {
      --color-principal: <?= $user['color_primary'] ?>;
      --fuente-principal: '<?= $user['font_family'] ?>', sans-serif;
    }
    body {
      margin: 0;
      font-family: var(--fuente-principal);
      background: #f5f5f5;
      padding: 20px;
    }
    .container { max-width: 500px; margin: 0 auto; }
    .titulo {
              background-color: #e1f5fe;
              padding: 14px 18px;
              margin-bottom: 20px;
              text-align: center;
              font-size: 20px;
              font-weight: 600;
              border-radius: 10px;
              box-shadow: 0 2px 6px rgba(0,0,0,0.1);
              color: #01579b;
              width: 90%;
            }
    .modelo-lista {
      display: flex;flex-wrap: wrap; gap: 15px; justify-content: center;
    }
    .modelo-card {
      background: white;
      border: 2px solid transparent;
      border-radius: 12px;
      width: 100%;
      max-width: 200px;
      padding: 10px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      cursor: pointer;
      transition: 0.3s;
    }
    .modelo-card img {
      max-width: 100%; max-height: 100px; object-fit: contain; margin-bottom: 8px;
    }
    .modelo-card.active {
      border-color: var(--color-principal);
      background: #e9f0ff;
      outline: 3px solid #3366ff;
      outline-offset: 0px;
      border-radius: 8px;
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
      display: none; /* este se activa con .active */
    }

    .boton-siguiente.active { display: block; }
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: var(--fuente-principal);
      background-image: url('<?php echo $bg; ?>');
      background-size: 100% 100%;
      background-repeat: no-repeat;
      background-position: center;
      background-attachment: fixed;
      min-height: 100vh;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
    }

    input, select, textarea {
        border-radius: 10px;
        padding: 10px;
        border: 1px solid #ccc;
        width: 100%;
        box-sizing: border-box;
        font-size: 15px;
        margin-top: 5px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s ease-in-out;
    }
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--color-principal);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }
    button, .boton-siguiente {
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-weight: bold;
        cursor: pointer;
    }
    button:hover, .boton-siguiente:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }
    .modelo-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .modelo-card:hover {
        transform: scale(1.03);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .form-group {
        margin-bottom: 20px;
    }
    
.input-wrapper {
    text-align: center;
    position: relative;
    margin-bottom: 20px;
}
.input-wrapper input[type="text"] {
    padding-right: 40px;
}
.input-wrapper input[type="checkbox"] {
    position: absolute;
    top: 8px;
    right: 12px;
    z-index: 10;
    margin: 0;
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--color-principal);
}

footer {
    margin-top: auto;
    text-align: center;
}

</style>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedido de Sello</title>
  <link href='https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Poppins&family=Oswald&family=Raleway&family=Nunito&family=Playfair+Display&family=Merriweather&family=Abril+Fatface&family=Anton&family=Bebas+Neue&family=Caveat&family=Dancing+Script&family=Fjalla+One&family=Great+Vibes&family=Indie+Flower&family=Josefin+Sans&family=Libre+Baskerville&family=Lilita+One&family=Lobster&family=Merienda&family=Pacifico&family=Patua+One&family=Permanent+Marker&family=Quicksand&family=Satisfy&family=Teko&family=Ubuntu&display=swap' rel='stylesheet'>
    
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto+Slab&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Caveat&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Fredericka+the+Great&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Indie+Flower&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Amatic+SC&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Shadows+Into+Light&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Raleway&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Gloria+Hallelujah&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">

</head>
<?php $bg = isset($user['background_image']) ? '../assets/images/bg/' . $user['background_image'] : ''; ?>
<body style="<?= $bg ? 'background-image: url(' . $bg . '); background-size: cover; background-position: center;' : '' ?>">
<header style="display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; background-color: #fff; border-bottom: 1px solid #ccc; font-family: Roboto, sans-serif; border-radius: 8px; max-width: 95%;">
  <div style="display: flex; align-items: center;">
    <img src="../assets/images/<?= $user['logo'] ?>" alt="Logo" style="max-height: 50px; margin-right: 15px;">
    <span style="font-size: 20px; font-weight: bold;"><?= $user['name'] ?></span>
  </div>
  <div style="text-align: right;">
    <span style="display: block; font-size: 16px;">WhatsApp:</span>
    <a href="https://wa.me/+549<?= preg_replace('/\D/', '', $user['whatsapp']) ?>" target="_blank" style="color: #0F7033FF; font-size: 16px; text-decoration: none; font-weight: bold;">
      <?= $user['whatsapp'] ?>
    </a>
  </div>
</header>

<div class="container">
<?php if (!$step): ?>
  <div class="bienvenida" style="text-align: center;">
    <h1>¡Bienvenido!</h1>
<div class="bienvenida" style="text-align: center; margin-bottom: 20px;">
  <?= !empty($user['welcome']) ? $user['welcome'] : 'Estás por realizar un pedido de sello personalizado para ' . $user['name'] . '.' ?>
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
          <img src="../assets/images/<?= $model['image'] ?>" alt="<?= $model['title'] ?>">
          <br>
          <small>$<?= number_format($model['price'], 0) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" id="btn-next" class="boton-siguiente">Siguiente</button>
  </form>


<?php elseif ($step == 2):
  $model_id = $_GET['model_id'] ?? null;
  if (!$model_id) {
    die("Modelo no especificado.");
  }

  $stmt = $pdo->prepare("SELECT * FROM templates WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $plantillas = $stmt->fetchAll();

  $fuentes = [];
  foreach ($plantillas as $p) {
    for ($i = 1; $i <= 4; $i++) {
      $f = $p["fuente_linea_$i"];
      if ($f && !in_array($f, $fuentes)) {
        $fuentes[] = $f;
      }
    }
  }
  $google_fonts = implode('|', array_map(fn($f) => str_replace(" ", "+", $f), $fuentes));
  echo "<link href='https://fonts.googleapis.com/css2?family=$google_fonts&display=swap' rel='stylesheet'>";
?>
<h2 class="titulo">2. Ingresá el texto de tu sello</h2>
<form method="get" action="index.php">
  <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
  <input type="hidden" name="step" value="3">
  <input type="hidden" name="model_id" value="<?= htmlspecialchars($model_id) ?>">

  <input type="text" name="linea1" placeholder="Línea 1" oninput="updatePreview(event)" required style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; margin: 0 auto auto; display: block;"><br>
    <div class="input-wrapper"><input type="text" name="linea2" placeholder="Línea 2" oninput="updatePreview(event)" style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; margin: 0 auto; display: block;"><input type="checkbox" id="chk_linea2" checked onchange="updatePreview()"></div>
    <div class="input-wrapper"><input type="text" name="linea3" placeholder="Línea 3" oninput="updatePreview(event)" style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; margin: 0 auto; display: block;"><input type="checkbox" id="chk_linea3" checked onchange="updatePreview()"></div>
    <div class="input-wrapper"><input type="text" name="linea4" placeholder="Línea 4" oninput="updatePreview(event)" style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; margin: 0 auto; display: block;"><input type="checkbox" id="chk_linea4" checked onchange="updatePreview()"></div>

  <div class="modelo-lista">
    <?php foreach ($plantillas as $plantilla): ?>
      <?php $contenido = json_decode($plantilla["content"], true); ?>
      <label class="modelo-card" style="display:block; border-radius: 8px; padding:10px; width: 200px;  background: #fff; margin-bottom: 10px;" style="zoom: 0.5; transform-origin: top left; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 48%; margin: 1%; float: left; background: #fff; border-radius: 8px; padding: 10px;">
        <input type="radio" name="template_id" value="<?= $plantilla['id'] ?>" required style="display:none;">
        <div>
          <div class="preview" style="width: 380px; height: 180px; zoom: 0.5; transform-origin: top left; display: flex; flex-direction: column; justify-content: center; align-items: center; background: white; border: 1px dashed #ccc; margin: 0 auto; overflow: hidden; padding-top: 10px; padding-bottom: 10px;">
            <?php for ($i = 1; $i <= 4; $i++):
              $texto = $contenido["linea$i"] ?? "";
              $fuente = $plantilla["fuente_linea_$i"] ?? 'Arial';
              $tamano = $plantilla["tamano_linea_$i"] ?? 28;
              $margen = (int)($plantilla["margen_top_linea_$i"] ?? 0);
              $alineacion = $plantilla["alineacion_linea_$i"] ?? 'center';
              $bold = !empty($plantilla["bold_linea_$i"]) ? 'bold' : 'normal';
            ?>
              <div class="linea-prev" style="
                font-family: '<?= $fuente ?>', sans-serif;
                font-size: <?= $tamano ?>px;
                font-weight: <?= $bold ?>;
                text-align: <?= $alineacion ?>;
                margin-top: <?= $margen ?>px;
                margin-bottom: 5px;
                white-space: nowrap;
                color: black;
                width: 100%;
              "><?= htmlspecialchars($texto) ?></div>
            <?php endfor; ?>
          </div></div></label>
    <?php endforeach; ?>
  </div>
  <button type="submit" class="boton-siguiente active" style="margin-top: 20px;">Siguiente</button>
</form>
<?php endif; ?>


</div>









<script>
function seleccionarModelo(el, id) {
  document.querySelectorAll('.modelo-card').forEach(card => card.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('model_id').value = id;
  const nextBtn = document.getElementById('btn-next');
  if (nextBtn) {
    nextBtn.classList.add('active');
  }
}
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".modelo-card").forEach(card => {
    card.addEventListener("click", () => {
      document.querySelectorAll(".modelo-card").forEach(c => c.classList.remove("active"));
      card.classList.add("active");
    });
  });
  updatePreview();
});
</script>
<script src="../assets/js/preview.js"></script>


<?php if ($step == 3): ?>
  <h2 class="titulo">3. Ingresá tus datos</h2>
  <form method="post" action="submit_order.php">
<input type="hidden" name="model_id" value="<?php echo htmlspecialchars($_GET['model_id'] ?? ''); ?>">
    <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
    <input type="hidden" name="model_id" value="<?= htmlspecialchars($_GET['model_id'] ?? '') ?>">
    <input type="hidden" name="template_id" value="<?= htmlspecialchars($_GET['template_id'] ?? '') ?>">

    <div style="text-align: center;">
  <input type="text" name="nombre" placeholder="🧑 Nombre y Apellido" required style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; display:block; margin: 0 auto;"><br>
    <input type="text" name="dni" placeholder="📝 DNI" required style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; display:block; margin: 0 auto;"><br>
    <input type="text" name="domicilio" placeholder="🏠 Domicilio" required style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; display:block; margin: 0 auto;"><br>
    <input type="tel" name="telefono" placeholder="📱 Whatsapp" required style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; display:block; margin: 0 auto;"><br>
    <input type="email" name="email" placeholder="✉️ Correo electrónico" required style="margin-bottom: 10px; width:90%; max-width: 650px; padding: 10px; display:block; margin: 0 auto;"><br>

    <div style="display:flex; gap:10px;">
    <button type="submit" class="boton-siguiente active" style="background:var(--color-principal); color:white; padding:10px 15px; border:none; border-radius:5px;">Finalizar pedido</button>
    </div>
  
  <input type="hidden" name="linea1" value="<?= htmlspecialchars($_GET['linea1'] ?? '') ?>">
  <input type="hidden" name="linea2" value="<?= htmlspecialchars($_GET['linea2'] ?? '') ?>">
  <input type="hidden" name="linea3" value="<?= htmlspecialchars($_GET['linea3'] ?? '') ?>">
  <input type="hidden" name="linea4" value="<?= htmlspecialchars($_GET['linea4'] ?? '') ?>">
</form>
<?php endif; ?>
</div>
<footer style="background-color: #fff; border: 1px solid #ccc; padding: 10px 20px; font-family: Roboto, sans-serif; border-radius: 8px; width: calc(85% - 20px); max-width: 95%; margin-top: auto; text-align: center;">
  <?= isset($user['footer']) ? $user['footer'] : '' ?>
</footer>
</body>
</html>
