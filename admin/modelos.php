<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

if (isset($_GET['eliminar_id'])) {
  $id = intval($_GET['eliminar_id']);
  $stmt = $pdo->prepare("DELETE FROM models WHERE id = ? AND user_id = ?");
  $stmt->execute([$id, $_SESSION['user']['id']]);
  header("Location: modelos.php");
  exit;
}

if (isset($_POST['crear_modelo'])) {
  $title = $_POST['title'];
  $desc = $_POST['description'];
  $price = $_POST['price'];
  $image = basename($_FILES["image"]["name"]);
  $target = "../assets/images/" . $image;
  move_uploaded_file($_FILES["image"]["tmp_name"], $target);
  $stmt = $pdo->prepare("INSERT INTO models (user_id, title, description, price, image, stock) VALUES (?, ?, ?, ?, ?, '$stock')");
  $stmt->execute([$_SESSION['user']['id'], $title, $desc, $price, $image]);
  header("Location: modelos.php");
  exit;
}

if (isset($_POST['editar_id'])) {
  $id = intval($_POST['editar_id']);
  $title = $_POST['title'];
  $desc = $_POST['description'];
  $price = $_POST['price'];

  if (!empty($_FILES["image"]["name"])) {
    $image = basename($_FILES["image"]["name"]);
    $target = "../assets/images/" . $image;
    move_uploaded_file($_FILES["image"]["tmp_name"], $target);
    $stmt = $pdo->prepare("UPDATE models SET title = ?, description = ?, price = ?, image = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $desc, $price, $image, $id, $_SESSION['user']['id']]);
  } else {
    $stmt = $pdo->prepare("UPDATE models SET title = ?, description = ?, price = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $desc, $price, $id, $_SESSION['user']['id']]);
  }
  header("Location: modelos.php");
  exit;
}

if (isset($_POST['editar_id'])) {
  $id = intval($_POST['editar_id']);
  $title = $_POST['title'];
  $desc = $_POST['description'];
  $price = $_POST['price'];

  if (!empty($_FILES["image"]["name"])) {
    $image = basename($_FILES["image"]["name"]);
    $target = "../assets/images/" . $image;
    move_uploaded_file($_FILES["image"]["tmp_name"], $target);
    $stmt = $pdo->prepare("UPDATE models SET title = ?, description = ?, price = ?, image = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $desc, $price, $image, $id, $_SESSION['user']['id']]);
  } else {
    $stmt = $pdo->prepare("UPDATE models SET title = ?, description = ?, price = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $desc, $price, $id, $_SESSION['user']['id']]);
  }
  header("Location: modelos.php");
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM models WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$models = $stmt->fetchAll();
?>

<h2>Modelos de Sellos</h2>

<div class="grid-modelos">
<?php foreach ($models as $model): ?>
  <div class="card">
    <div class="preview">
      <img src="../assets/images/<?= htmlspecialchars($model['image']) ?>" alt="<?= htmlspecialchars($model['title']) ?>" style="max-width:100%; max-height:100%;">
    </div>
    <strong><?= htmlspecialchars($model['title']) ?></strong>
    <small>$<?= number_format($model['price'], 2) ?></small>
    





<div class="acciones">
  <button class="btn editar" data-tooltip="Editar modelo" data-model='<?= json_encode($model) ?>' onclick="abrirModalEditar(this)">✏️</button>
  <button class="btn duplicar" data-tooltip="Duplicar modelo" data-model='<?= json_encode($model) ?>' onclick="abrirModalDuplicar(this)">📄</button>
  <button class="btn eliminar" data-tooltip="Eliminar modelo" onclick="if(confirm('¿Eliminar este modelo?')) window.location.href='?eliminar_id=<?= $model['id'] ?>'">🗑️</button>
</div>






  </div>
<?php endforeach; ?>
</div>

<a href="#" class="boton-flotante" onclick="abrirModalNuevo()">+</a>


<div id="modalNuevo" class="modal">
  <div class="modal-contenido">
    <span class="cerrar" onclick="cerrarModal('modalNuevo')">&times;</span>
    <h3 id="modalTitulo">Agregar nuevo modelo</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="editar_id" id="editar_id" value="">
      <input type="text" name="title" placeholder="Título" required><br>
      <input type="text" name="description" placeholder="Descripción"><br>
      <input type="number" step="0.01" name="price" placeholder="Precio" required><br>

<!-- Campo: Stock -->
<div class="form-group mt-2">
  <div class="form-check">
    <input type="checkbox" class="form-check-input" id="stock" name="stock" value="1" <?= (isset($modelo) && $modelo['stock']) ? 'checked' : '' ?>>
    <label class="form-check-label" for="stock">¿Hay stock?</label>
  </div>
</div>

      <input type="file" name="image"><br>
      <button type="submit" id="botonEnviar" name="crear_modelo">Crear modelo</button>
    </form>
  </div>
</div>

</div>




<style>
.grid-modelos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 10px;
  margin-top: 20px;
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
  min-height: 200px;
}
.card .preview {
  width: 100%;
  height: 120px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed #aaa;
}
.card .acciones {
  margin-top: 10px;
  display: flex;
  gap: 8px;
}
.btn {
  height: 40px;
  width: 40px;
  font-size: 18px;
  padding: 0;
  border-radius: 6px;
  text-decoration: none;
  color: #333;
  background: #f0f0f0;
  border: 1px solid #ccc;
  cursor: pointer;
  line-height: 1;
  text-align: center;
  position: relative;
}
.btn.editar { background: #cce5ff; border-color: #99ccff; }
.btn.duplicar { background: #d4edda; border-color: #a5d6a7; }
.btn.eliminar { background: #f8d7da; border-color: #f5c6cb; }

/* Tooltip visible con ::after */
.btn:hover::after {
  content: attr(data-tooltip);
  position: absolute;
  background: #333;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  transform: translateX(-50%);
  left: 50%;
  bottom: 110%;
  opacity: 1;
  pointer-events: none;
  z-index: 9999;
}
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
  z-index: 999;
}
.boton-flotante:hover {
  background-color: #303f9f;
}
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0; width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.6);
}
.modal-contenido {
  background: #fff;
  margin: 10% auto;
  padding: 20px;
  width: 90%;
  max-width: 400px;
  border-radius: 10px;
}
.cerrar {
  float: right;
  font-size: 24px;
  cursor: pointer;
}
</style>
<style>
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeOutUp {
  from {
    opacity: 1;
    transform: translateY(0);
  }
  to {
    opacity: 0;
    transform: translateY(-30px);
  }
}

.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0; width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.6);
}

.modal.mostrar {
  display: block;
}

.modal-contenido {
  background: #fff;
  margin: 10% auto;
  padding: 20px;
  width: 90%;
  max-width: 400px;
  border-radius: 10px;
  animation: fadeInDown 0.3s ease forwards;
}
.modal-cerrar {
  animation: fadeOutUp 0.3s ease forwards;
}
.cerrar {
  float: right;
  font-size: 24px;
  cursor: pointer;
}
</style>
<script>
function abrirModalNuevo() {
  const modal = document.getElementById("modalNuevo");
  const form = modal.querySelector("form");
  form.reset();
  modal.querySelector("#editar_id").value = "";
  modal.querySelector("input[name='title']").value = "";
  modal.querySelector("input[name='description']").value = "";
  modal.querySelector("input[name='price']").value = "";
  document.getElementById("modalTitulo").textContent = "Agregar nuevo modelo";
  const boton = document.getElementById("botonEnviar");
  boton.name = "crear_modelo";
  boton.textContent = "Crear modelo";
  modal.classList.add("mostrar");
  modal.querySelector(".modal-contenido").classList.remove("modal-cerrar");
}

function abrirModalDuplicar(btn) {
  const data = JSON.parse(btn.getAttribute("data-model"));
  const modal = document.getElementById("modalNuevo");
  const form = modal.querySelector("form");
  modal.querySelector("#editar_id").value = "";
  form.reset();
  form.title.value = data.title + " (copia)";
  form.description.value = data.description;
  form.price.value = data.price;
  document.getElementById("modalTitulo").textContent = "Duplicar modelo";
  const boton = document.getElementById("botonEnviar");
  boton.name = "crear_modelo";
  boton.textContent = "Crear modelo";
  modal.classList.add("mostrar");
  modal.querySelector(".modal-contenido").classList.remove("modal-cerrar");
}

function abrirModalEditar(btn) {
  const data = JSON.parse(btn.getAttribute("data-model"));
  const modal = document.getElementById("modalNuevo");
  const form = modal.querySelector("form");
  form.reset();
  modal.querySelector("#editar_id").value = data.id;
  form.title.value = data.title;
  form.description.value = data.description;
  form.price.value = data.price;
  document.getElementById("modalTitulo").textContent = "Editar modelo";
  const boton = document.getElementById("botonEnviar");
  boton.name = "editar_id";
  boton.textContent = "Guardar cambios";
  modal.classList.add("mostrar");
  modal.querySelector(".modal-contenido").classList.remove("modal-cerrar");
}

function cerrarModal(id) {
  const modal = document.getElementById(id);
  const contenido = modal.querySelector(".modal-contenido");
  contenido.classList.add("modal-cerrar");
  setTimeout(() => {
    modal.classList.remove("mostrar");
    contenido.classList.remove("modal-cerrar");
  }, 300);
}
</script>






<?php require_once 'includes/footer.php'; ?>  