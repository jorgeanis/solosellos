

<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

if (isset($_GET['del_cat'])) {
  $cat_id = intval($_GET['del_cat']);
  $stmt = $pdo->prepare("DELETE FROM template_categories WHERE id = ? AND user_id = ?");
  $stmt->execute([$cat_id, $_SESSION['user']['id']]);
  header("Location: plantillas.php");
  exit;
}

if (isset($_POST['agregar_cat'])) {
  $nueva = trim($_POST['nueva_categoria']);
  if (!empty($nueva)) {
    $stmt = $pdo->prepare("INSERT INTO template_categories (name, user_id) VALUES (?, ?)");
    $stmt->execute([$nueva, $_SESSION['user']['id']]);
    header("Location: plantillas.php");
    exit;
  }
}

// Obtener plantillas del usuario actual
$stmt = $pdo->prepare("SELECT templates.*, template_categories.name AS categoria FROM templates 
                       LEFT JOIN template_categories ON templates.category_id = template_categories.id
                       WHERE templates.user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);

$plantillas = $stmt->fetchAll();

// Cargar fuentes desde Google Fonts
$fuentes = [];
foreach ($plantillas as $p) {
  for ($i = 1; $i <= 4; $i++) {
    $f = trim($p["fuente_linea_$i"] ?? '');
    if ($f && !in_array($f, $fuentes)) {
      $fuentes[] = $f;
    }
  }
}
foreach ($fuentes as $fuente) {
  $nombre = str_replace(' ', '+', $fuente);
  echo "<link href=\"https://fonts.googleapis.com/css2?family={$nombre}:wght@400;700&display=swap\" rel=\"stylesheet\">\n";
}


// Obtener categorías
$cats = $pdo->prepare("SELECT * FROM template_categories WHERE user_id = ?");
$cats->execute([$_SESSION['user']['id']]);
$categorias = $cats->fetchAll();
?>

<h2>Mis plantillas</h2>

<style>
#panelCategorias {
    transition: transform 0.3s ease, opacity 0.3s ease;
    transform: translateY(-100%);
    opacity: 0;
    border-bottom-left-radius: 16px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    background: #f1f1f1;
    padding: 10px;
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    max-width: 400px;
    display: none;
    z-index: 99;
    border-bottom: 2px solid #ccc;
}
#panelCategorias.open {
    transform: translateY(0);
    opacity: 99;
    display: block;
}
#btnCategorias {
    position: fixed;
    top: 0px;
    right: 10px;
    background: #3f51b5;
    color: white;
    padding: 8px 12px;
    border-radius: 0px 0px 8px 8px;
    cursor: pointer;
    transform: rotate(0deg);
}
.grid-plantillas {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 5px;
    margin-top: 40px;
}
.card {
    border: 1px solid #ccc;
    border-radius: 10px;
    padding: 10px;
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 6px rgba(0,0,0,0.08);
    width: 240px;
}
.preview {
    width: 238px;
    height: 126px;
    border: 1px dashed #aaa;
    background-color: #eee;
    position: relative;
    margin-bottom: 10px;
}
.preview span {
    position: absolute;
    font-family: Arial, sans-serif;
    font-size: 22px;
    color: black;
    left: 50%;
    transform: translateX(-50%);
}
.card .acciones {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.card {
  opacity: 0;
}



</style>

<div id="btnCategorias" onclick="toggleCategorias()">📁 Categorías</div>


<div id="panelCategorias">
  <div style="text-align: right;">
    <button onclick="toggleCategorias()" style="background: none; border: none; font-size: 18px; cursor: pointer;">❌</button>
  </div>

  <h3>Categorías</h3>
  <form method="post">
    <input type="text" name="nueva_categoria" placeholder="Nueva categoría..." required>
    <button type="submit" name="agregar_cat">➕</button>
  </form>
  <hr>
  <?php foreach ($categorias as $cat): ?>
    <div class="categoria-row">
      <?= htmlspecialchars($cat['name']) ?>
      <a href="?del_cat=<?= $cat['id'] ?>" onclick="return confirm('¿Eliminar esta categoría?')">🗑</a>
    </div>
  <?php endforeach; ?>
</div>


<div class="tabs" style="margin-top: 60px; text-align: left; width: 300">
  <?php foreach ($categorias as $cat): ?>
    <button onclick="switchTab(<?= $cat['id'] ?>)" data-cat-id="<?= $cat['id'] ?>" class="tab-btn">
      <?= htmlspecialchars($cat['name']) ?>
    </button>
  <?php endforeach; ?>
</div>

<?php foreach ($categorias as $cat): ?>
  <div class="grid-plantillas tab-content" data-cat-id="<?= $cat['id'] ?>" style="display: none;">
    <?php foreach ($plantillas as $plantilla): ?>
      <?php if ($plantilla['category_id'] == $cat['id']): ?>
      <div class="card">
        
<div class="preview" style="margin: 0 auto; width: 380px; height: 140px; border: 2px dashed #aaa; background-color: #fff; overflow: hidden; transform: scale(0.6); transform-origin: top left; display: flex; flex-direction: column; justify-content: flex-start; align-items: center;">
  <?php $contenido = json_decode($plantilla["content"], true); ?>
  <?php for ($i = 1; $i <= 4; $i++):
    $texto = $contenido["linea$i"] ?? "";
    $fuente = $plantilla["fuente_linea_$i"] ?? 'Arial';
    $tamano = $plantilla["tamano_linea_$i"] ?? 28;
    $margen = (int)($plantilla["margen_top_linea_$i"] ?? 0);
    $alineacion = $plantilla["alineacion_linea_$i"] ?? 'center';
    $bold = !empty($plantilla["bold_linea_$i"]) ? 'bold' : 'normal';
  ?>
    <div style="
      font-family: '<?= $fuente ?>', sans-serif;
      font-size: <?= $tamano ?>px;
      font-weight: <?= $bold ?>;
      text-align: <?= $alineacion ?>;
      margin-top: <?= $margen ?>px;
      margin-bottom: 5px;
      white-space: nowrap;
      color: black;
      width: 100%;
    ">
      <?= htmlspecialchars($texto) ?>
    </div>
  <?php endfor; ?>
</div>
  <div class="titulo" style="margin-top: -75px;">
        <h4><?= htmlspecialchars($plantilla['nombre']) ?></h4>
</div>
  <div class="acciones" style="display: flex; gap: 6px; margin-top: -28px;">
  <form method="GET" action="editar_plantilla.php">
    <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
    <button type="submit" style="padding: 4px 8px;">✏️ Editar</button>
  </form>
  <form method="GET" action="duplicar_plantilla.php">
    <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
    <button type="submit" style="padding: 4px 8px;">📄 Duplicar</button>
  </form>
  <form method="GET" action="eliminar_plantilla.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta plantilla?')">
    <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
    <button type="submit" style="padding: 4px 8px;">🗑️ Eliminar</button>
  </form>
</div>

      </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<script>
function switchTab(categoriaId) {
  document.querySelectorAll(".tab-content").forEach(tab => {
    const visible = (categoriaId === "all" || tab.dataset.catId == categoriaId);
    tab.style.display = visible ? "grid" : "none";

    if (visible) {
      // Aplica animación a cada tarjeta dentro del tab
      const cards = tab.querySelectorAll(".card");
      cards.forEach(card => {
        card.style.animation = "none";
        void card.offsetWidth; // reinicia animación
        card.style.animation = "fadeInUp 0.4s ease forwards";
      });
    }
  });

  document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.classList.toggle("active", btn.dataset.catId == categoriaId || (categoriaId === "all" && btn.dataset.catId === "all"));
  });
}

window.addEventListener("DOMContentLoaded", () => {
  const tabsContainer = document.querySelector(".tabs");
  if (tabsContainer && !tabsContainer.querySelector('[data-cat-id="all"]')) {
    const todasBtn = document.createElement("button");
    todasBtn.textContent = "Todas";
    todasBtn.className = "tab-btn active";
    todasBtn.setAttribute("data-cat-id", "all");
    todasBtn.onclick = () => switchTab("all");
    tabsContainer.prepend(todasBtn);
  }

  switchTab("all");
});

function toggleCategorias() {
  const panel = document.getElementById("panelCategorias");
  panel.classList.toggle("open");
}
</script>



<?php require_once 'includes/footer.php'; ?>

<style>
.boton-flotante {
  position: fixed;
  bottom: 25px;
  right: 25px;
  background-color: #3f51b5;
  color: white;
  font-size: 32px;
  font-weight: bold;
  text-align: center;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  text-decoration: none;
  line-height: 60px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.3);
  transition: background 0.3s;
  z-index: 999;
}
.boton-flotante:hover {
  background-color: #303f9f;
}
</style>

<a href="nueva_plantilla.php" class="boton-flotante">+</a>



<style>
.tab-btn,
.tab-btn.active,
.tab-btn.inactive {
  background: #3f51b5 !important;
  color: white !important;
  width: inherit;
  font-weight: bold;
  border: none;
  padding: 10px 20px;
  margin: 5px;
  border-radius: 8px;
  cursor: pointer;
}
</style>
