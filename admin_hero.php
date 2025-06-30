<?php
$folder = __DIR__ . '/assets/images/hero/';
$json_path = __DIR__ . '/assets/images/hero/orden.json';

if (!file_exists($folder)) mkdir($folder, 0777, true);

// Cargar datos del JSON
$orden = [];
$tiempo = 4;

if (file_exists($json_path)) {
    $data = json_decode(file_get_contents($json_path), true);
    if (isset($data['orden'])) {
        $orden = $data['orden'];
        $tiempo = $data['tiempo'] ?? 4;
    } elseif (is_array($data)) {
        $orden = $data;
    }
}

// Subir imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {
    $img = $_FILES['imagen'];
    if ($img['error'] === 0) {
        $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
        $newName = uniqid('hero_') . '.' . $ext;
        move_uploaded_file($img['tmp_name'], $folder . $newName);
        $orden[] = $newName;
        $final = ['orden' => $orden, 'tiempo' => $tiempo];
        file_put_contents($json_path, json_encode($final));
    }
    header('Location: admin_hero.php');
    exit;
}

// Eliminar imagen
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $fullPath = $folder . $file;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        $orden = array_values(array_filter($orden, fn($img) => $img !== $file));
        $final = ['orden' => $orden, 'tiempo' => $tiempo];
        file_put_contents($json_path, json_encode($final));
    }
    header('Location: admin_hero.php');
    exit;
}

// Guardar tiempo
if (isset($_POST['tiempo'])) {
    $tiempo = intval($_POST['tiempo']);
    $final = ['orden' => $orden, 'tiempo' => $tiempo];
    file_put_contents($json_path, json_encode($final));
    header('Location: admin_hero.php'); exit;
}

// Guardar nuevo orden via fetch
if (isset($_POST['orden'])) {
    $orden = json_decode($_POST['orden'], true);
    $final = ['orden' => $orden, 'tiempo' => $tiempo];
    file_put_contents($json_path, json_encode($final));
    exit;
}

$imagenes = array_values(array_filter($orden, fn($f) => file_exists($folder . $f)));
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Gestión de Hero</title>
  <style>
    body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
    .grid { display: flex; flex-wrap: wrap; gap: 15px; }
    .card { background: white; padding: 10px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); width: 150px; text-align: center; cursor: grab; }
    .card img { width: 100%; border-radius: 8px; }
    form { margin-bottom: 20px; }
    button, input[type=file], input[type=number] { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; margin-top: 10px; }
    .dragging { opacity: 0.5; }
  </style>
</head>
<body>
  <h2>Gestión de imágenes del Hero</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="imagen" required accept=".jpg,.jpeg,.png,.webp">
    <br>
    <button type="submit">Subir imagen</button>
  </form>

  <form method="POST" style="margin-bottom:20px;">
    <label>Tiempo entre imágenes (segundos):</label><br>
    <input type="number" name="tiempo" min="1" value="<?= $tiempo ?>" style="width:80px;">
    <button type="submit">Guardar tiempo</button>
  </form>

  <div class="grid" id="sortable">
    <?php foreach ($imagenes as $img): ?>
      <div class="card" draggable="true" data-name="<?= $img ?>">
        <img src="assets/images/hero/<?= $img ?>" alt="">
        <form method="GET" onsubmit="return confirm('¿Eliminar esta imagen?')">
          <input type="hidden" name="delete" value="<?= $img ?>">
          <button type="submit">Eliminar</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
    const container = document.getElementById('sortable');
    let dragged;

    container.addEventListener('dragstart', e => {
      dragged = e.target;
      e.target.classList.add('dragging');
    });

    container.addEventListener('dragend', e => {
      e.target.classList.remove('dragging');
      const orden = Array.from(container.children).map(el => el.dataset.name);
      fetch('admin_hero.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'orden=' + encodeURIComponent(JSON.stringify(orden))
      });
    });

    container.addEventListener('dragover', e => {
      e.preventDefault();
      const after = Array.from(container.children).find(child => {
        const box = child.getBoundingClientRect();
        return e.clientY < box.top + box.height / 2;
      });
      if (after) container.insertBefore(dragged, after);
      else container.appendChild(dragged);
    });
  </script>
</body>
</html>