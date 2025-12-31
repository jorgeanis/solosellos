<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

if (isset($_GET['eliminar_id'])) {
  $id = intval($_GET['eliminar_id']);
  $stmt = $pdo->prepare("DELETE FROM models WHERE id = ? AND user_id = ?");
  $stmt->execute([$id, $_SESSION['user']['id']]);
  header("Location: modelos.php?msg=deleted");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] == 'crear') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = isset($_POST['stock']) ? 1 : 0;
    $image = '';

    if (!empty($_FILES["image"]["name"])) {
      $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
      $new_name = uniqid('modelo_') . '.' . $ext;
      $target = "../assets/images/" . $new_name;
      move_uploaded_file($_FILES["image"]["tmp_name"], $target);
      $image = $new_name;
    }
    
    $stmt = $pdo->prepare("INSERT INTO models (user_id, title, description, price, image, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user']['id'], $title, $desc, $price, $image, $stock]);
    header("Location: modelos.php?msg=created");
    exit;
  }

  if ($_POST['action'] == 'editar') {
    $id = intval($_POST['editar_id']);
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = isset($_POST['stock']) ? 1 : 0;

    if (!empty($_FILES["image"]["name"])) {
      $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
      $new_name = uniqid('modelo_') . '.' . $ext;
      $target = "../assets/images/" . $new_name;
      move_uploaded_file($_FILES["image"]["tmp_name"], $target);
      $stmt = $pdo->prepare("UPDATE models SET title = ?, description = ?, price = ?, image = ?, stock = ? WHERE id = ? AND user_id = ?");
      $stmt->execute([$title, $desc, $price, $new_name, $stock, $id, $_SESSION['user']['id']]);
    } else {
      $stmt = $pdo->prepare("UPDATE models SET title = ?, description = ?, price = ?, stock = ? WHERE id = ? AND user_id = ?");
      $stmt->execute([$title, $desc, $price, $stock, $id, $_SESSION['user']['id']]);
    }
    header("Location: modelos.php?msg=updated");
    exit;
  }
}

$stmt = $pdo->prepare("SELECT * FROM models WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$models = $stmt->fetchAll();
?>

<!-- AOS CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24">
  
  <!-- Page Header -->
  <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4" data-aos="fade-down">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
            <i class="fas fa-cubes text-brand-600 mr-2"></i>Modelos de Sellos
        </h1>
        <p class="text-gray-500 text-sm mt-1">Administra el catálogo de productos disponibles</p>
    </div>
    <button onclick="abrirModalNuevo()" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors shadow-lg shadow-brand-500/30 font-bold flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo Modelo
    </button>
  </div>

  <!-- Models Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="grid-modelos">
    <?php foreach ($models as $index => $model): ?>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group relative" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
        
        <!-- Image Container -->
        <div class="h-48 bg-gray-50 flex items-center justify-center p-4 relative overflow-hidden">
             <?php if ($model['image']): ?>
                 <img src="../assets/images/<?= htmlspecialchars($model['image']) ?>" alt="<?= htmlspecialchars($model['title']) ?>" class="max-h-full max-w-full object-contain transform transition-transform duration-500 group-hover:scale-110">
             <?php else: ?>
                 <div class="text-gray-300 text-6xl"><i class="fas fa-image"></i></div>
             <?php endif; ?>
             
             <!-- Badge Stock -->
             <div class="absolute top-3 right-3">
                 <?php if ($model['stock']): ?>
                     <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-md shadow-sm border border-green-200">En Stock</span>
                 <?php else: ?>
                     <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-md shadow-sm border border-red-200">Sin Stock</span>
                 <?php endif; ?>
             </div>
        </div>

        <!-- Content -->
        <div class="p-4 flex-1 flex flex-col">
            <h3 class="font-bold text-gray-800 mb-1 text-lg line-clamp-1"><?= htmlspecialchars($model['title']) ?></h3>
            <p class="text-gray-500 text-sm mb-3 line-clamp-2 min-h-[40px]"><?= htmlspecialchars($model['description']) ?></p>
            
            <div class="mt-auto flex justify-between items-center pt-3 border-t border-gray-100">
                <span class="text-xl font-extrabold text-brand-600">$<?= number_format($model['price'], 0, ',', '.') ?></span>
                
                <!-- Actions -->
                <div class="flex gap-2">
                    <button onclick='abrirModalEditar(this)' data-model='<?= json_encode($model) ?>' class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button onclick='abrirModalDuplicar(this)' data-model='<?= json_encode($model) ?>' class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Duplicar">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button onclick="confirmarEliminar(<?= $model['id'] ?>)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal Formulario -->
<div id="modalModelos" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>
    
    <!-- Modal Panel -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="modalPanel">
                
                <!-- Header -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold leading-6 text-gray-900" id="modalTitulo">Nuevo Modelo</h3>
                    <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-gray-500 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                    <input type="hidden" name="action" id="form_action" value="crear">
                    <input type="hidden" name="editar_id" id="editar_id" value="">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                        <input type="text" name="title" required class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="description" rows="2" class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 transition-colors"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio ($)</label>
                            <input type="number" step="0.01" name="price" required class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imagen</label>
                            <input type="file" name="image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition-colors">
                        </div>
                    </div>

                    <div class="flex items-center mt-2">
                        <input type="checkbox" id="stock" name="stock" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <label for="stock" class="ml-2 block text-sm text-gray-900">Disponible en Stock</label>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                        <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors">Cancelar</button>
                        <button type="submit" id="botonEnviar" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-bold shadow-md shadow-brand-500/20 transition-colors">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });

    const modal = document.getElementById('modalModelos');
    const backdrop = document.getElementById('modalBackdrop');
    const panel = document.getElementById('modalPanel');
    const form = modal.querySelector('form');

    function abrirModal() {
        modal.classList.remove('hidden');
        // Animation in
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
        }, 10);
    }

    function cerrarModal() {
        // Animation out
        backdrop.classList.add('opacity-0');
        panel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
        panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function abrirModalNuevo() {
        form.reset();
        document.getElementById("form_action").value = "crear";
        document.getElementById("editar_id").value = "";
        document.getElementById("modalTitulo").textContent = "Nuevo Modelo";
        document.getElementById("botonEnviar").textContent = "Crear Modelo";
        // Default stock checked for new items
        form.stock.checked = true;
        abrirModal();
    }

    function abrirModalEditar(btn) {
        const data = JSON.parse(btn.getAttribute("data-model"));
        form.reset();
        document.getElementById("form_action").value = "editar";
        document.getElementById("editar_id").value = data.id;
        form.title.value = data.title;
        form.description.value = data.description;
        form.price.value = data.price;
        form.stock.checked = data.stock == 1;
        document.getElementById("modalTitulo").textContent = "Editar Modelo";
        document.getElementById("botonEnviar").textContent = "Guardar Cambios";
        abrirModal();
    }

    function abrirModalDuplicar(btn) {
        const data = JSON.parse(btn.getAttribute("data-model"));
        form.reset();
        document.getElementById("form_action").value = "crear";
        document.getElementById("editar_id").value = "";
        form.title.value = data.title + " (copia)";
        form.description.value = data.description;
        form.price.value = data.price;
        form.stock.checked = data.stock == 1;
        document.getElementById("modalTitulo").textContent = "Duplicar Modelo";
        document.getElementById("botonEnviar").textContent = "Crear Modelo";
        abrirModal();
    }

    function confirmarEliminar(id) {
        if(confirm('¿Estás seguro de que deseas eliminar este modelo?')) {
            window.location.href = '?eliminar_id=' + id;
        }
    }

    // Close on click outside
    modal.addEventListener('click', (e) => {
        if (e.target.closest('#modalPanel') === null) {
            cerrarModal();
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>