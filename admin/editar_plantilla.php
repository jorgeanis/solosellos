<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p style='color:red'>ID de plantilla no especificado.</p>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user']['id']]);
$plantilla = $stmt->fetch();

if (!$plantilla) {
    echo "<p style='color:red'>Plantilla no encontrada.</p>";
    exit;
}

$stmt2 = $pdo->prepare("SELECT id, name FROM template_categories WHERE user_id = ?");
$stmt2->execute([$_SESSION['user']['id']]);
$categorias = $stmt2->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $categoria_id = $_POST['categoria'];
    $fuentes = [$_POST['fuente1'], $_POST['fuente2'], $_POST['fuente3'], $_POST['fuente4']];
    $tamanos = [$_POST['tamano1'], $_POST['tamano2'], $_POST['tamano3'], $_POST['tamano4']];
    $negritas = [
        isset($_POST['negrita1']) ? 1 : 0,
        isset($_POST['negrita2']) ? 1 : 0,
        isset($_POST['negrita3']) ? 1 : 0,
        isset($_POST['negrita4']) ? 1 : 0
    ];
    $alineaciones = [$_POST['alineacion1'], $_POST['alineacion2'], $_POST['alineacion3'], $_POST['alineacion4']];
    $margenes = [$_POST['margen_top1'], $_POST['margen_top2'], $_POST['margen_top3'], $_POST['margen_top4']];
    $lineas = [$_POST['linea1'], $_POST['linea2'], $_POST['linea3'], $_POST['linea4']];
    $content = json_encode([
        'linea1' => $lineas[0],
        'linea2' => $lineas[1],
        'linea3' => $lineas[2],
        'linea4' => $lineas[3]
    ]);

    try {
        $stmt = $pdo->prepare("UPDATE templates SET
            nombre = ?, category_id = ?, content = ?,
            fuente_linea_1 = ?, fuente_linea_2 = ?, fuente_linea_3 = ?, fuente_linea_4 = ?,
            tamano_linea_1 = ?, tamano_linea_2 = ?, tamano_linea_3 = ?, tamano_linea_4 = ?,
            bold_linea_1 = ?, bold_linea_2 = ?, bold_linea_3 = ?, bold_linea_4 = ?,
            alineacion_linea_1 = ?, alineacion_linea_2 = ?, alineacion_linea_3 = ?, alineacion_linea_4 = ?,
            margen_top_linea_1 = ?, margen_top_linea_2 = ?, margen_top_linea_3 = ?, margen_top_linea_4 = ?
            WHERE id = ? AND user_id = ?");

        $stmt->execute([
            $nombre, $categoria_id, $content,
            $fuentes[0], $fuentes[1], $fuentes[2], $fuentes[3],
            $tamanos[0], $tamanos[1], $tamanos[2], $tamanos[3],
            $negritas[0], $negritas[1], $negritas[2], $negritas[3],
            $alineaciones[0], $alineaciones[1], $alineaciones[2], $alineaciones[3],
            $margenes[0], $margenes[1], $margenes[2], $margenes[3],
            $id, $_SESSION['user']['id']
        ]);

        header("Location: plantillas.php");
        exit;
    } catch (PDOException $e) {
        echo "<div style='color:red'>❌ Error al actualizar: " . $e->getMessage() . "</div>";
    }
}
?>
<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

$stmt = $pdo->prepare("SELECT id, name FROM template_categories WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$categorias = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $category_id = $_POST['categoria'];
    $nombre = $_POST['nombre'];

    $fuentes = [$_POST['fuente1'], $_POST['fuente2'], $_POST['fuente3'], $_POST['fuente4']];
    $tamanos = [$_POST['tamano1'], $_POST['tamano2'], $_POST['tamano3'], $_POST['tamano4']];
    $negritas = [
        isset($_POST['negrita1']) ? 1 : 0,
        isset($_POST['negrita2']) ? 1 : 0,
        isset($_POST['negrita3']) ? 1 : 0,
        isset($_POST['negrita4']) ? 1 : 0
    ];
    $alineaciones = [$_POST['alineacion1'], $_POST['alineacion2'], $_POST['alineacion3'], $_POST['alineacion4']];
    $margenes_top = [$_POST['margen_top1'], $_POST['margen_top2'], $_POST['margen_top3'], $_POST['margen_top4']];

    $lineas = [$_POST['linea1'], $_POST['linea2'], $_POST['linea3'], $_POST['linea4']];
    $content = json_encode([
        'linea1' => $lineas[0],
        'linea2' => $lineas[1],
        'linea3' => $lineas[2],
        'linea4' => $lineas[3]
    ]);
    $font_family = '';

    try {
        $stmt = $pdo->prepare("INSERT INTO templates (
            category_id, content, font_family, user_id, nombre,
            fuente_linea_1, fuente_linea_2, fuente_linea_3, fuente_linea_4,
            created_at,
            tamano_linea_1, tamano_linea_2, tamano_linea_3, tamano_linea_4,
            bold_linea_1, bold_linea_2, bold_linea_3, bold_linea_4,
            alineacion_linea_1, alineacion_linea_2, alineacion_linea_3, alineacion_linea_4,
            margen_top_linea_1, margen_top_linea_2, margen_top_linea_3, margen_top_linea_4
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $category_id, $content, $font_family, $user_id, $nombre,
            $fuentes[0], $fuentes[1], $fuentes[2], $fuentes[3],
            $tamanos[0], $tamanos[1], $tamanos[2], $tamanos[3],
            $negritas[0], $negritas[1], $negritas[2], $negritas[3],
            $alineaciones[0], $alineaciones[1], $alineaciones[2], $alineaciones[3],
            $margenes_top[0], $margenes_top[1], $margenes_top[2], $margenes_top[3]
        ]);

        header("Location: plantillas.php");
        exit;
    } catch (PDOException $e) {
        echo "<div style='color:red'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Poppins&family=Raleway&family=Merriweather&family=Nunito&family=Oswald&family=Ubuntu&family=PT+Sans&family=Quicksand&family=Work+Sans&family=Bebas+Neue&family=Archivo&family=Fira+Sans&family=Playfair+Display&family=Rubik&family=Caveat&family=Dancing+Script&family=Shadows+Into+Light&family=Satisfy&family=Great+Vibes&family=Permanent+Marker&family=Patrick+Hand&family=Gloria+Hallelujah&family=Indie+Flower&family=Fredoka&family=Josefin+Sans&family=Amatic+SC&display=swap');
option.font-Roboto { font-family: "Roboto", sans-serif; }
option.font-Open-Sans { font-family: "Open Sans", sans-serif; }
option.font-Lato { font-family: "Lato", sans-serif; }
option.font-Montserrat { font-family: "Montserrat", sans-serif; }
option.font-Poppins { font-family: "Poppins", sans-serif; }
option.font-Raleway { font-family: "Raleway", sans-serif; }
option.font-Merriweather { font-family: "Merriweather", sans-serif; }
option.font-Nunito { font-family: "Nunito", sans-serif; }
option.font-Oswald { font-family: "Oswald", sans-serif; }
option.font-Ubuntu { font-family: "Ubuntu", sans-serif; }
option.font-PT-Sans { font-family: "PT Sans", sans-serif; }
option.font-Quicksand { font-family: "Quicksand", sans-serif; }
option.font-Work-Sans { font-family: "Work Sans", sans-serif; }
option.font-Bebas-Neue { font-family: "Bebas Neue", sans-serif; }
option.font-Archivo { font-family: "Archivo", sans-serif; }
option.font-Fira-Sans { font-family: "Fira Sans", sans-serif; }
option.font-Playfair-Display { font-family: "Playfair Display", sans-serif; }
option.font-Rubik { font-family: "Rubik", sans-serif; }
option.font-Caveat { font-family: "Caveat", sans-serif; }
option.font-Dancing-Script { font-family: "Dancing Script", sans-serif; }
option.font-Shadows-Into-Light { font-family: "Shadows Into Light", sans-serif; }
option.font-Satisfy { font-family: "Satisfy", sans-serif; }
option.font-Great-Vibes { font-family: "Great Vibes", sans-serif; }
option.font-Permanent-Marker { font-family: "Permanent Marker", sans-serif; }
option.font-Patrick-Hand { font-family: "Patrick Hand", sans-serif; }
option.font-Gloria-Hallelujah { font-family: "Gloria Hallelujah", sans-serif; }
option.font-Indie-Flower { font-family: "Indie Flower", sans-serif; }
option.font-Fredoka { font-family: "Fredoka", sans-serif; }
option.font-Josefin-Sans { font-family: "Josefin Sans", sans-serif; }
option.font-Amatic-SC { font-family: "Amatic SC", sans-serif; }

body {
  font-family: 'Segoe UI', sans-serif;
  background: #f5f5f5;
  margin: 0;
}
.header-welcome {
  background: #009688;
  color: white;
  padding: 12px 25px;
  font-size: 16px;
  width: 100%;
  box-sizing: border-box;
}
form {
  padding: 30px;
  margin: 0 auto;
  max-width: 980px;
}
.editor-wrapper {
  display: flex;
  gap: 40px;
}
.tab-buttons {
  display: flex;
  gap: 10px;
  margin: 15px 0;
}
.tab-buttons button {
  padding: 6px 12px;
  border: none;
  background: #ddd;
  cursor: pointer;
  border-radius: 5px;
}
.tab-buttons button.active {
  background: #3f51b5;
  color: #fff;
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
.line-controls {
  background: #fff;
  border: 1px solid #ccc;
  padding: 10px;
  border-radius: 6px;
}
.line-controls .fila1,
.line-controls .fila2 {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.line-controls select,
.line-controls input[type="number"] {
  height: 30px;
  padding: 4px;
}
.line-controls button {
  padding: 4px 8px;
  font-size: 14px;
  cursor: pointer;
  width: auto;
  display: inline-block;
}
.line-controls .tiny-btn {
  padding: 3px 6px;
  font-size: 13px;
}
.alineacion-btns button {
  padding: 4px 6px;
  border-radius: 4px;
  background: #eee;
  border: 1px solid #aaa;
  cursor: pointer;
}
.alineacion-btns button.active {
  background: #3f51b5;
  color: white;
}
#preview {
  width: 380px;
  height: 140px;
  border: 2px dashed #999;
  background: linear-gradient(to bottom, #fafafa, #f0f0f0);
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-radius: 6px;
 overflow: hidden;}
.preview-line {
  width: 100%;
  font-size: 20px;
  margin-bottom: 5px;
  white-space: nowrap;
}

.form-top {
  display: flex;
  gap: 20px;
  margin-bottom: 25px;
}
.editor-wrapper {
  display: grid;
  grid-template-columns: 40% 60%;
  align-items: center;
  gap: 40px;
  margin-top: 20px;
}
.preview-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.tab-buttons button,
.alineacion-btns button {
  padding: 6px 12px;
  border: none;
  background: #e0e0e0;
  color: #333;
  cursor: pointer;
  border-radius: 5px;
  font-weight: 500;
  transition: all 0.3s ease;
}
.tab-buttons button.active,
.alineacion-btns button.active {
  background: #3f51b5;
  color: #fff;
}
</style>



<h2 style="margin-bottom: 10px;">Editando plantilla: 
<span style="font-family: 'Roboto', sans-serif; font-weight: bold; font-size: 24px;">
<?= $plantilla["nombre"] ?>
</span>
</h2>

<form method="POST">
<input type="hidden" id="margen_top1" name="margen_top1" value="<?= $plantilla['margen_top_linea_1'] ?>">
<input type="hidden" id="margen_top2" name="margen_top2" value="<?= $plantilla['margen_top_linea_2'] ?>">
<input type="hidden" id="margen_top3" name="margen_top3" value="<?= $plantilla['margen_top_linea_3'] ?>">
<input type="hidden" id="margen_top4" name="margen_top4" value="<?= $plantilla['margen_top_linea_4'] ?>">
<input type="hidden" name="alineacion1" value="<?= $plantilla["alineacion_linea_1"] ?>" id="alineacion1" value="center">
<input type="hidden" name="alineacion2" value="<?= $plantilla["alineacion_linea_2"] ?>" id="alineacion2" value="center">
<input type="hidden" name="alineacion3" value="<?= $plantilla["alineacion_linea_3"] ?>" id="alineacion3" value="center">
<input type="hidden" name="alineacion4" value="<?= $plantilla["alineacion_linea_4"] ?>" id="alineacion4" value="center">
<div class="form-top"><label>Nombre:<br><input type="text" name="nombre" value="<?= htmlspecialchars($plantilla["nombre"]) ?>" style="width:250px" required></label>
<label>Categoría:<br>
  <select name="categoria" required>
<?php foreach ($categorias as $cat): ?>
  <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $plantilla["category_id"] ? "selected" : "" ?>>
    <?= htmlspecialchars($cat['name']) ?>
  </option>
<?php endforeach; ?>
</select></label></div>

<div class="editor-wrapper">
  <div>
    <div class="tab-buttons">
      <button type="button" class="tab-btn active" onclick="showTab(1)">Línea 1</button>
      <button type="button" class="tab-btn" onclick="showTab(2)">Línea 2</button>
      <button type="button" class="tab-btn" onclick="showTab(3)">Línea 3</button>
      <button type="button" class="tab-btn" onclick="showTab(4)">Línea 4</button>
    </div>

<div class="tab-content active" id="tab1">
  <div class="line-controls">
    <div class="fila0">
      <label>Texto Línea 1:</label>
      <input type="text" name="linea1" value="<?= htmlspecialchars($plantilla["content"] ? json_decode($plantilla["content"], true)["linea1"] : "") ?>" id="linea_input_1" value="Juan J. Gonzalez" oninput="updatePreview()">
    </div>
    
    <div class="fila1">
      <select name="fuente1" onchange="updatePreview()">
<option value="Roboto" class="font-Roboto" <?= $plantilla["fuente_linea_1"] == "Roboto" ? "selected" : "" ?>>Roboto</option>
<option value="Open Sans" class="font-Open-Sans" <?= $plantilla["fuente_linea_1"] == "Open Sans" ? "selected" : "" ?>>Open Sans</option>
<option value="Lato" class="font-Lato" <?= $plantilla["fuente_linea_1"] == "Lato" ? "selected" : "" ?>>Lato</option>
<option value="Montserrat" class="font-Montserrat" <?= $plantilla["fuente_linea_1"] == "Montserrat" ? "selected" : "" ?>>Montserrat</option>
<option value="Poppins" class="font-Poppins" <?= $plantilla["fuente_linea_1"] == "Poppins" ? "selected" : "" ?>>Poppins</option>
<option value="Raleway" class="font-Raleway" <?= $plantilla["fuente_linea_1"] == "Raleway" ? "selected" : "" ?>>Raleway</option>
<option value="Merriweather" class="font-Merriweather" <?= $plantilla["fuente_linea_1"] == "Merriweather" ? "selected" : "" ?>>Merriweather</option>
<option value="Nunito" class="font-Nunito" <?= $plantilla["fuente_linea_1"] == "Nunito" ? "selected" : "" ?>>Nunito</option>
<option value="Oswald" class="font-Oswald" <?= $plantilla["fuente_linea_1"] == "Oswald" ? "selected" : "" ?>>Oswald</option>
<option value="Ubuntu" class="font-Ubuntu" <?= $plantilla["fuente_linea_1"] == "Ubuntu" ? "selected" : "" ?>>Ubuntu</option>
<option value="PT Sans" class="font-PT-Sans" <?= $plantilla["fuente_linea_1"] == "PT Sans" ? "selected" : "" ?>>PT Sans</option>
<option value="Quicksand" class="font-Quicksand" <?= $plantilla["fuente_linea_1"] == "Quicksand" ? "selected" : "" ?>>Quicksand</option>
<option value="Work Sans" class="font-Work-Sans" <?= $plantilla["fuente_linea_1"] == "Work Sans" ? "selected" : "" ?>>Work Sans</option>
<option value="Bebas Neue" class="font-Bebas-Neue" <?= $plantilla["fuente_linea_1"] == "Bebas Neue" ? "selected" : "" ?>>Bebas Neue</option>
<option value="Archivo" class="font-Archivo" <?= $plantilla["fuente_linea_1"] == "Archivo" ? "selected" : "" ?>>Archivo</option>
<option value="Fira Sans" class="font-Fira-Sans" <?= $plantilla["fuente_linea_1"] == "Fira Sans" ? "selected" : "" ?>>Fira Sans</option>
<option value="Playfair Display" class="font-Playfair-Display" <?= $plantilla["fuente_linea_1"] == "Playfair Display" ? "selected" : "" ?>>Playfair Display</option>
<option value="Rubik" class="font-Rubik" <?= $plantilla["fuente_linea_1"] == "Rubik" ? "selected" : "" ?>>Rubik</option>
<option value="Caveat" class="font-Caveat" <?= $plantilla["fuente_linea_1"] == "Caveat" ? "selected" : "" ?>>Caveat</option>
<option value="Dancing Script" class="font-Dancing-Script" <?= $plantilla["fuente_linea_1"] == "Dancing Script" ? "selected" : "" ?>>Dancing Script</option>
<option value="Shadows Into Light" class="font-Shadows-Into-Light" <?= $plantilla["fuente_linea_1"] == "Shadows Into Light" ? "selected" : "" ?>>Shadows Into Light</option>
<option value="Satisfy" class="font-Satisfy" <?= $plantilla["fuente_linea_1"] == "Satisfy" ? "selected" : "" ?>>Satisfy</option>
<option value="Great Vibes" class="font-Great-Vibes" <?= $plantilla["fuente_linea_1"] == "Great Vibes" ? "selected" : "" ?>>Great Vibes</option>
<option value="Permanent Marker" class="font-Permanent-Marker" <?= $plantilla["fuente_linea_1"] == "Permanent Marker" ? "selected" : "" ?>>Permanent Marker</option>
<option value="Patrick Hand" class="font-Patrick-Hand" <?= $plantilla["fuente_linea_1"] == "Patrick Hand" ? "selected" : "" ?>>Patrick Hand</option>
<option value="Gloria Hallelujah" class="font-Gloria-Hallelujah" <?= $plantilla["fuente_linea_1"] == "Gloria Hallelujah" ? "selected" : "" ?>>Gloria Hallelujah</option>
<option value="Indie Flower" class="font-Indie-Flower" <?= $plantilla["fuente_linea_1"] == "Indie Flower" ? "selected" : "" ?>>Indie Flower</option>
<option value="Fredoka" class="font-Fredoka" <?= $plantilla["fuente_linea_1"] == "Fredoka" ? "selected" : "" ?>>Fredoka</option>
<option value="Josefin Sans" class="font-Josefin-Sans" <?= $plantilla["fuente_linea_1"] == "Josefin Sans" ? "selected" : "" ?>>Josefin Sans</option>
<option value="Amatic SC" class="font-Amatic-SC" <?= $plantilla["fuente_linea_1"] == "Amatic SC" ? "selected" : "" ?>>Amatic SC</option>
</select>
      <button type="button" onclick="document.querySelector('[name=negrita1]').checked = !document.querySelector('[name=negrita1]').checked; updatePreview();"><b>B</b></button>
      
      <input type="number" name="tamano1" value="<?= $plantilla["tamano_linea_1"] ?>" id="tamano1" value="20" min="8" max="100" onchange="updatePreview()" style="width: 60px;">
    <div class="alineacion-btns" data-linea="1">
      <button type="button" onclick="setAlign(1, 'left', this)">🡸</button>
      <button type="button" onclick="setAlign(1, 'center', this)" class="active">🡺🡸</button>
      <button type="button" onclick="setAlign(1, 'right', this)">🡺</button>
    </div>
<div class="padding-btns"><button type="button" onclick="cambiarMargen(1, -5)">➖</button>
<button type="button" onclick="cambiarMargen(1, 5)">➕</button></div>
    
      
      <input type="checkbox" name="negrita1" <?= $plantilla["bold_linea_1"] ? "checked" : "" ?> style="display:none">
    </div>
    
    
  </div>
</div>
<div class="tab-content " id="tab2">
  <div class="line-controls">
    <div class="fila0">
      <label>Texto Línea 2:</label>
      <input type="text" name="linea2" value="<?= htmlspecialchars($plantilla["content"] ? json_decode($plantilla["content"], true)["linea2"] : "") ?>" id="linea_input_2" value="Prof. Nivel Inicial" oninput="updatePreview()">
    </div>
    
    <div class="fila1">
      <select name="fuente2" onchange="updatePreview()">
<option value="Roboto" class="font-Roboto" <?= $plantilla["fuente_linea_2"] == "Roboto" ? "selected" : "" ?>>Roboto</option>
<option value="Open Sans" class="font-Open-Sans" <?= $plantilla["fuente_linea_2"] == "Open Sans" ? "selected" : "" ?>>Open Sans</option>
<option value="Lato" class="font-Lato" <?= $plantilla["fuente_linea_2"] == "Lato" ? "selected" : "" ?>>Lato</option>
<option value="Montserrat" class="font-Montserrat" <?= $plantilla["fuente_linea_2"] == "Montserrat" ? "selected" : "" ?>>Montserrat</option>
<option value="Poppins" class="font-Poppins" <?= $plantilla["fuente_linea_2"] == "Poppins" ? "selected" : "" ?>>Poppins</option>
<option value="Raleway" class="font-Raleway" <?= $plantilla["fuente_linea_2"] == "Raleway" ? "selected" : "" ?>>Raleway</option>
<option value="Merriweather" class="font-Merriweather" <?= $plantilla["fuente_linea_2"] == "Merriweather" ? "selected" : "" ?>>Merriweather</option>
<option value="Nunito" class="font-Nunito" <?= $plantilla["fuente_linea_2"] == "Nunito" ? "selected" : "" ?>>Nunito</option>
<option value="Oswald" class="font-Oswald" <?= $plantilla["fuente_linea_2"] == "Oswald" ? "selected" : "" ?>>Oswald</option>
<option value="Ubuntu" class="font-Ubuntu" <?= $plantilla["fuente_linea_2"] == "Ubuntu" ? "selected" : "" ?>>Ubuntu</option>
<option value="PT Sans" class="font-PT-Sans" <?= $plantilla["fuente_linea_2"] == "PT Sans" ? "selected" : "" ?>>PT Sans</option>
<option value="Quicksand" class="font-Quicksand" <?= $plantilla["fuente_linea_2"] == "Quicksand" ? "selected" : "" ?>>Quicksand</option>
<option value="Work Sans" class="font-Work-Sans" <?= $plantilla["fuente_linea_2"] == "Work Sans" ? "selected" : "" ?>>Work Sans</option>
<option value="Bebas Neue" class="font-Bebas-Neue" <?= $plantilla["fuente_linea_2"] == "Bebas Neue" ? "selected" : "" ?>>Bebas Neue</option>
<option value="Archivo" class="font-Archivo" <?= $plantilla["fuente_linea_2"] == "Archivo" ? "selected" : "" ?>>Archivo</option>
<option value="Fira Sans" class="font-Fira-Sans" <?= $plantilla["fuente_linea_2"] == "Fira Sans" ? "selected" : "" ?>>Fira Sans</option>
<option value="Playfair Display" class="font-Playfair-Display" <?= $plantilla["fuente_linea_2"] == "Playfair Display" ? "selected" : "" ?>>Playfair Display</option>
<option value="Rubik" class="font-Rubik" <?= $plantilla["fuente_linea_2"] == "Rubik" ? "selected" : "" ?>>Rubik</option>
<option value="Caveat" class="font-Caveat" <?= $plantilla["fuente_linea_2"] == "Caveat" ? "selected" : "" ?>>Caveat</option>
<option value="Dancing Script" class="font-Dancing-Script" <?= $plantilla["fuente_linea_2"] == "Dancing Script" ? "selected" : "" ?>>Dancing Script</option>
<option value="Shadows Into Light" class="font-Shadows-Into-Light" <?= $plantilla["fuente_linea_2"] == "Shadows Into Light" ? "selected" : "" ?>>Shadows Into Light</option>
<option value="Satisfy" class="font-Satisfy" <?= $plantilla["fuente_linea_2"] == "Satisfy" ? "selected" : "" ?>>Satisfy</option>
<option value="Great Vibes" class="font-Great-Vibes" <?= $plantilla["fuente_linea_2"] == "Great Vibes" ? "selected" : "" ?>>Great Vibes</option>
<option value="Permanent Marker" class="font-Permanent-Marker" <?= $plantilla["fuente_linea_2"] == "Permanent Marker" ? "selected" : "" ?>>Permanent Marker</option>
<option value="Patrick Hand" class="font-Patrick-Hand" <?= $plantilla["fuente_linea_2"] == "Patrick Hand" ? "selected" : "" ?>>Patrick Hand</option>
<option value="Gloria Hallelujah" class="font-Gloria-Hallelujah" <?= $plantilla["fuente_linea_2"] == "Gloria Hallelujah" ? "selected" : "" ?>>Gloria Hallelujah</option>
<option value="Indie Flower" class="font-Indie-Flower" <?= $plantilla["fuente_linea_2"] == "Indie Flower" ? "selected" : "" ?>>Indie Flower</option>
<option value="Fredoka" class="font-Fredoka" <?= $plantilla["fuente_linea_2"] == "Fredoka" ? "selected" : "" ?>>Fredoka</option>
<option value="Josefin Sans" class="font-Josefin-Sans" <?= $plantilla["fuente_linea_2"] == "Josefin Sans" ? "selected" : "" ?>>Josefin Sans</option>
<option value="Amatic SC" class="font-Amatic-SC" <?= $plantilla["fuente_linea_2"] == "Amatic SC" ? "selected" : "" ?>>Amatic SC</option>
</select>
      <button type="button" onclick="document.querySelector('[name=negrita2]').checked = !document.querySelector('[name=negrita2]').checked; updatePreview();"><b>B</b></button>
      
      <input type="number" name="tamano2" value="<?= $plantilla["tamano_linea_2"] ?>" id="tamano2" value="20" min="8" max="100" onchange="updatePreview()" style="width: 60px;">
    <div class="alineacion-btns" data-linea="2">
      <button type="button" onclick="setAlign(2, 'left', this)">🡸</button>
      <button type="button" onclick="setAlign(2, 'center', this)" class="active">🡺🡸</button>
      <button type="button" onclick="setAlign(2, 'right', this)">🡺</button>
    </div>
<div class="padding-btns"><button type="button" onclick="cambiarMargen(2, -5)">➖</button>
<button type="button" onclick="cambiarMargen(2, 5)">➕</button></div>
    
      
      <input type="checkbox" name="negrita2" <?= $plantilla["bold_linea_2"] ? "checked" : "" ?> style="display:none">
    </div>
    
    
  </div>
</div>
<div class="tab-content " id="tab3">
  <div class="line-controls">
    <div class="fila0">
      <label>Texto Línea 3:</label>
      <input type="text" name="linea3" value="<?= htmlspecialchars($plantilla["content"] ? json_decode($plantilla["content"], true)["linea3"] : "") ?>" id="linea_input_3" value="Leg. Num: 000000" oninput="updatePreview()">
    </div>
    
    <div class="fila1">
      <select name="fuente3" onchange="updatePreview()">
<option value="Roboto" class="font-Roboto" <?= $plantilla["fuente_linea_3"] == "Roboto" ? "selected" : "" ?>>Roboto</option>
<option value="Open Sans" class="font-Open-Sans" <?= $plantilla["fuente_linea_3"] == "Open Sans" ? "selected" : "" ?>>Open Sans</option>
<option value="Lato" class="font-Lato" <?= $plantilla["fuente_linea_3"] == "Lato" ? "selected" : "" ?>>Lato</option>
<option value="Montserrat" class="font-Montserrat" <?= $plantilla["fuente_linea_3"] == "Montserrat" ? "selected" : "" ?>>Montserrat</option>
<option value="Poppins" class="font-Poppins" <?= $plantilla["fuente_linea_3"] == "Poppins" ? "selected" : "" ?>>Poppins</option>
<option value="Raleway" class="font-Raleway" <?= $plantilla["fuente_linea_3"] == "Raleway" ? "selected" : "" ?>>Raleway</option>
<option value="Merriweather" class="font-Merriweather" <?= $plantilla["fuente_linea_3"] == "Merriweather" ? "selected" : "" ?>>Merriweather</option>
<option value="Nunito" class="font-Nunito" <?= $plantilla["fuente_linea_3"] == "Nunito" ? "selected" : "" ?>>Nunito</option>
<option value="Oswald" class="font-Oswald" <?= $plantilla["fuente_linea_3"] == "Oswald" ? "selected" : "" ?>>Oswald</option>
<option value="Ubuntu" class="font-Ubuntu" <?= $plantilla["fuente_linea_3"] == "Ubuntu" ? "selected" : "" ?>>Ubuntu</option>
<option value="PT Sans" class="font-PT-Sans" <?= $plantilla["fuente_linea_3"] == "PT Sans" ? "selected" : "" ?>>PT Sans</option>
<option value="Quicksand" class="font-Quicksand" <?= $plantilla["fuente_linea_3"] == "Quicksand" ? "selected" : "" ?>>Quicksand</option>
<option value="Work Sans" class="font-Work-Sans" <?= $plantilla["fuente_linea_3"] == "Work Sans" ? "selected" : "" ?>>Work Sans</option>
<option value="Bebas Neue" class="font-Bebas-Neue" <?= $plantilla["fuente_linea_3"] == "Bebas Neue" ? "selected" : "" ?>>Bebas Neue</option>
<option value="Archivo" class="font-Archivo" <?= $plantilla["fuente_linea_3"] == "Archivo" ? "selected" : "" ?>>Archivo</option>
<option value="Fira Sans" class="font-Fira-Sans" <?= $plantilla["fuente_linea_3"] == "Fira Sans" ? "selected" : "" ?>>Fira Sans</option>
<option value="Playfair Display" class="font-Playfair-Display" <?= $plantilla["fuente_linea_3"] == "Playfair Display" ? "selected" : "" ?>>Playfair Display</option>
<option value="Rubik" class="font-Rubik" <?= $plantilla["fuente_linea_3"] == "Rubik" ? "selected" : "" ?>>Rubik</option>
<option value="Caveat" class="font-Caveat" <?= $plantilla["fuente_linea_3"] == "Caveat" ? "selected" : "" ?>>Caveat</option>
<option value="Dancing Script" class="font-Dancing-Script" <?= $plantilla["fuente_linea_3"] == "Dancing Script" ? "selected" : "" ?>>Dancing Script</option>
<option value="Shadows Into Light" class="font-Shadows-Into-Light" <?= $plantilla["fuente_linea_3"] == "Shadows Into Light" ? "selected" : "" ?>>Shadows Into Light</option>
<option value="Satisfy" class="font-Satisfy" <?= $plantilla["fuente_linea_3"] == "Satisfy" ? "selected" : "" ?>>Satisfy</option>
<option value="Great Vibes" class="font-Great-Vibes" <?= $plantilla["fuente_linea_3"] == "Great Vibes" ? "selected" : "" ?>>Great Vibes</option>
<option value="Permanent Marker" class="font-Permanent-Marker" <?= $plantilla["fuente_linea_3"] == "Permanent Marker" ? "selected" : "" ?>>Permanent Marker</option>
<option value="Patrick Hand" class="font-Patrick-Hand" <?= $plantilla["fuente_linea_3"] == "Patrick Hand" ? "selected" : "" ?>>Patrick Hand</option>
<option value="Gloria Hallelujah" class="font-Gloria-Hallelujah" <?= $plantilla["fuente_linea_3"] == "Gloria Hallelujah" ? "selected" : "" ?>>Gloria Hallelujah</option>
<option value="Indie Flower" class="font-Indie-Flower" <?= $plantilla["fuente_linea_3"] == "Indie Flower" ? "selected" : "" ?>>Indie Flower</option>
<option value="Fredoka" class="font-Fredoka" <?= $plantilla["fuente_linea_3"] == "Fredoka" ? "selected" : "" ?>>Fredoka</option>
<option value="Josefin Sans" class="font-Josefin-Sans" <?= $plantilla["fuente_linea_3"] == "Josefin Sans" ? "selected" : "" ?>>Josefin Sans</option>
<option value="Amatic SC" class="font-Amatic-SC" <?= $plantilla["fuente_linea_3"] == "Amatic SC" ? "selected" : "" ?>>Amatic SC</option>
</select>
      <button type="button" onclick="document.querySelector('[name=negrita3]').checked = !document.querySelector('[name=negrita3]').checked; updatePreview();"><b>B</b></button>
      
      <input type="number" name="tamano3" value="<?= $plantilla["tamano_linea_3"] ?>" id="tamano3" value="20" min="8" max="100" onchange="updatePreview()" style="width: 60px;">
    <div class="alineacion-btns" data-linea="3">
      <button type="button" onclick="setAlign(3, 'left', this)">🡸</button>
      <button type="button" onclick="setAlign(3, 'center', this)" class="active">🡺🡸</button>
      <button type="button" onclick="setAlign(3, 'right', this)">🡺</button>
    </div>
<div class="padding-btns"><button type="button" onclick="cambiarMargen(3, -5)">➖</button>
<button type="button" onclick="cambiarMargen(3, 5)">➕</button></div>
    
      
      <input type="checkbox" name="negrita3" <?= $plantilla["bold_linea_3"] ? "checked" : "" ?> style="display:none">
    </div>
    
    
  </div>
</div>
<div class="tab-content " id="tab4">
  <div class="line-controls">
    <div class="fila0">
      <label>Texto Línea 4:</label>
      <input type="text" name="linea4" value="<?= htmlspecialchars($plantilla["content"] ? json_decode($plantilla["content"], true)["linea4"] : "") ?>" id="linea_input_4" value="Colegio Nacional B. Mitre" oninput="updatePreview()">
    </div>
    
    <div class="fila1">
      <select name="fuente4" onchange="updatePreview()">
<option value="Roboto" class="font-Roboto" <?= $plantilla["fuente_linea_4"] == "Roboto" ? "selected" : "" ?>>Roboto</option>
<option value="Open Sans" class="font-Open-Sans" <?= $plantilla["fuente_linea_4"] == "Open Sans" ? "selected" : "" ?>>Open Sans</option>
<option value="Lato" class="font-Lato" <?= $plantilla["fuente_linea_4"] == "Lato" ? "selected" : "" ?>>Lato</option>
<option value="Montserrat" class="font-Montserrat" <?= $plantilla["fuente_linea_4"] == "Montserrat" ? "selected" : "" ?>>Montserrat</option>
<option value="Poppins" class="font-Poppins" <?= $plantilla["fuente_linea_4"] == "Poppins" ? "selected" : "" ?>>Poppins</option>
<option value="Raleway" class="font-Raleway" <?= $plantilla["fuente_linea_4"] == "Raleway" ? "selected" : "" ?>>Raleway</option>
<option value="Merriweather" class="font-Merriweather" <?= $plantilla["fuente_linea_4"] == "Merriweather" ? "selected" : "" ?>>Merriweather</option>
<option value="Nunito" class="font-Nunito" <?= $plantilla["fuente_linea_4"] == "Nunito" ? "selected" : "" ?>>Nunito</option>
<option value="Oswald" class="font-Oswald" <?= $plantilla["fuente_linea_4"] == "Oswald" ? "selected" : "" ?>>Oswald</option>
<option value="Ubuntu" class="font-Ubuntu" <?= $plantilla["fuente_linea_4"] == "Ubuntu" ? "selected" : "" ?>>Ubuntu</option>
<option value="PT Sans" class="font-PT-Sans" <?= $plantilla["fuente_linea_4"] == "PT Sans" ? "selected" : "" ?>>PT Sans</option>
<option value="Quicksand" class="font-Quicksand" <?= $plantilla["fuente_linea_4"] == "Quicksand" ? "selected" : "" ?>>Quicksand</option>
<option value="Work Sans" class="font-Work-Sans" <?= $plantilla["fuente_linea_4"] == "Work Sans" ? "selected" : "" ?>>Work Sans</option>
<option value="Bebas Neue" class="font-Bebas-Neue" <?= $plantilla["fuente_linea_4"] == "Bebas Neue" ? "selected" : "" ?>>Bebas Neue</option>
<option value="Archivo" class="font-Archivo" <?= $plantilla["fuente_linea_4"] == "Archivo" ? "selected" : "" ?>>Archivo</option>
<option value="Fira Sans" class="font-Fira-Sans" <?= $plantilla["fuente_linea_4"] == "Fira Sans" ? "selected" : "" ?>>Fira Sans</option>
<option value="Playfair Display" class="font-Playfair-Display" <?= $plantilla["fuente_linea_4"] == "Playfair Display" ? "selected" : "" ?>>Playfair Display</option>
<option value="Rubik" class="font-Rubik" <?= $plantilla["fuente_linea_4"] == "Rubik" ? "selected" : "" ?>>Rubik</option>
<option value="Caveat" class="font-Caveat" <?= $plantilla["fuente_linea_4"] == "Caveat" ? "selected" : "" ?>>Caveat</option>
<option value="Dancing Script" class="font-Dancing-Script" <?= $plantilla["fuente_linea_4"] == "Dancing Script" ? "selected" : "" ?>>Dancing Script</option>
<option value="Shadows Into Light" class="font-Shadows-Into-Light" <?= $plantilla["fuente_linea_4"] == "Shadows Into Light" ? "selected" : "" ?>>Shadows Into Light</option>
<option value="Satisfy" class="font-Satisfy" <?= $plantilla["fuente_linea_4"] == "Satisfy" ? "selected" : "" ?>>Satisfy</option>
<option value="Great Vibes" class="font-Great-Vibes" <?= $plantilla["fuente_linea_4"] == "Great Vibes" ? "selected" : "" ?>>Great Vibes</option>
<option value="Permanent Marker" class="font-Permanent-Marker" <?= $plantilla["fuente_linea_4"] == "Permanent Marker" ? "selected" : "" ?>>Permanent Marker</option>
<option value="Patrick Hand" class="font-Patrick-Hand" <?= $plantilla["fuente_linea_4"] == "Patrick Hand" ? "selected" : "" ?>>Patrick Hand</option>
<option value="Gloria Hallelujah" class="font-Gloria-Hallelujah" <?= $plantilla["fuente_linea_4"] == "Gloria Hallelujah" ? "selected" : "" ?>>Gloria Hallelujah</option>
<option value="Indie Flower" class="font-Indie-Flower" <?= $plantilla["fuente_linea_4"] == "Indie Flower" ? "selected" : "" ?>>Indie Flower</option>
<option value="Fredoka" class="font-Fredoka" <?= $plantilla["fuente_linea_4"] == "Fredoka" ? "selected" : "" ?>>Fredoka</option>
<option value="Josefin Sans" class="font-Josefin-Sans" <?= $plantilla["fuente_linea_4"] == "Josefin Sans" ? "selected" : "" ?>>Josefin Sans</option>
<option value="Amatic SC" class="font-Amatic-SC" <?= $plantilla["fuente_linea_4"] == "Amatic SC" ? "selected" : "" ?>>Amatic SC</option>
</select>
      <button type="button" onclick="document.querySelector('[name=negrita4]').checked = !document.querySelector('[name=negrita4]').checked; updatePreview();"><b>B</b></button>
      
      <input type="number" name="tamano4" value="<?= $plantilla["tamano_linea_4"] ?>" id="tamano4" value="20" min="8" max="100" onchange="updatePreview()" style="width: 60px;">
    <div class="alineacion-btns" data-linea="4">
      <button type="button" onclick="setAlign(4, 'left', this)">🡸</button>
      <button type="button" onclick="setAlign(4, 'center', this)" class="active">🡺🡸</button>
      <button type="button" onclick="setAlign(4, 'right', this)">🡺</button>
    </div>
<div class="padding-btns"><button type="button" onclick="cambiarMargen(4, -5)">➖</button>
<button type="button" onclick="cambiarMargen(4, 5)">➕</button></div>
    
      
      <input type="checkbox" name="negrita4" <?= $plantilla["bold_linea_4"] ? "checked" : "" ?> style="display:none">
    </div>
    
    
  </div>
</div>
    <button type="submit">Guardar plantilla</button>
  </div>

  <div>
    <div class="preview-container"><div id="preview">
      <div id="preview_linea1" class="preview-line">Texto 1</div>
      <div id="preview_linea2" class="preview-line">Texto 2</div>
      <div id="preview_linea3" class="preview-line">Texto 3</div>
      <div id="preview_linea4" class="preview-line">Texto 4</div>
    </div>
  </div></div>
</div>
</form>









<script>


function updatePreview() {
  for (let i = 1; i <= 4; i++) {
    const preview = document.getElementById('preview_linea' + i);
    const fuente = document.querySelector('[name="fuente' + i + '"]')?.value;
    const size = document.getElementById('tamano' + i)?.value + "px";
    const bold = document.querySelector('[name="negrita' + i + '"]')?.checked;
    const italic = document.getElementById('cursiva' + i)?.checked;
    const alineacion = document.getElementById('alineacion' + i)?.value;
    const margen = parseInt(document.getElementById('margen_top' + i)?.value || 0);
    const texto = document.getElementById('linea_input_' + i)?.value || "";

    if (preview) {
      preview.style.fontFamily = fuente;
      preview.style.fontSize = size;
      preview.style.fontWeight = bold ? 'bold' : 'normal';
      preview.style.fontStyle = italic ? 'italic' : 'normal';
      preview.style.textAlign = alineacion;
      preview.style.marginTop = margen + "px";
      preview.textContent = texto;
    }
  }
}



function setAlign(i, direction, el) {
  const group = el.parentElement;
  group.querySelectorAll('button').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('alineacion' + i).value = direction;
  updatePreview();
}

function cambiarMargen(i, delta) {
  const input = document.getElementById('margen_top' + i);
  let valor = parseInt(input.value) || 0;
  valor = valor + delta;
  input.value = valor;
  updatePreview();
}

function showTab(n) {
  document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
  document.querySelectorAll('.tab-buttons button').forEach(btn => btn.classList.remove('active'));
  document.getElementById('tab' + n).classList.add('active');
  document.querySelectorAll('.tab-buttons button')[n - 1].classList.add('active');
  updatePreview();
}

document.addEventListener('DOMContentLoaded', updatePreview);
</script>
