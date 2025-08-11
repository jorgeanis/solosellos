<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// Cargar CSS específico de la página
echo '<link rel="stylesheet" href="../assets/css/plantilla-preview.css">';
echo '<link rel="stylesheet" href="../assets/css/plantillas-page.css">';

// Lógica para gestionar categorías (sin cambios)
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

// Obtener plantillas y categorías
$stmt_templates = $pdo->prepare("
    SELECT 
        id, nombre, content, category_id,
        fuente_linea_1, tamano_linea_1, bold_linea_1, alineacion_linea_1, margen_top_linea_1,
        fuente_linea_2, tamano_linea_2, bold_linea_2, alineacion_linea_2, margen_top_linea_2,
        fuente_linea_3, tamano_linea_3, bold_linea_3, alineacion_linea_3, margen_top_linea_3,
        fuente_linea_4, tamano_linea_4, bold_linea_4, alineacion_linea_4, margen_top_linea_4
    FROM templates 
    WHERE user_id = ?
");
$stmt_templates->execute([$_SESSION['user']['id']]);
$plantillas = $stmt_templates->fetchAll();

$stmt_cats = $pdo->prepare("SELECT * FROM template_categories WHERE user_id = ?");
$stmt_cats->execute([$_SESSION['user']['id']]);
$categorias = $stmt_cats->fetchAll();

// Cargar dinámicamente las fuentes de Google Fonts necesarias
$fuentes_usadas = [];
foreach ($plantillas as $p) {
    for ($i = 1; $i <= 4; $i++) {
        $f = trim($p["fuente_linea_$i"] ?? '');
        if ($f && !in_array($f, $fuentes_usadas)) {
            $fuentes_usadas[] = $f;
        }
    }
}
foreach ($fuentes_usadas as $fuente) {
    $nombre_fuente = str_replace(' ', '+', $fuente);
    echo "<link href='https://fonts.googleapis.com/css2?family={$nombre_fuente}:wght@400;700&display=swap' rel='stylesheet'>\n";
}
?>

<link rel="stylesheet" href="../assets/css/plantilla-preview.css">
<link rel="stylesheet" href="../assets/css/plantillas-page.css"> <!-- CSS específico para esta página -->

<style>
    /* Estilos para el modal custom */
    .custom-modal {
        display: none; /* Oculto por defecto */
        position: fixed; /* Posición fija en la pantalla */
        z-index: 1000; /* Por encima de todo */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto; /* Habilitar scroll si el contenido es muy largo */
        background-color: rgba(0,0,0,0.4); /* Fondo semi-transparente */
        justify-content: center;
        align-items: center;
    }
    .custom-modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 8px;
        position: relative;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
        animation-name: animatetop;
        animation-duration: 0.4s
    }
    /* Animación del modal */
    @-webkit-keyframes animatetop {
        from {top: -300px; opacity: 0}
        to {top: 0; opacity: 1}
    }
    @keyframes animatetop {
        from {top: -300px; opacity: 0}
        to {top: 0; opacity: 1}
    }
    .custom-close-button {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        right: 10px;
        top: 5px;
    }
    .custom-close-button:hover,
    .custom-close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    .custom-modal-header {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        margin-bottom: 15px;
    }
    .custom-modal-header h5 {
        margin: 0;
        font-size: 20px;
    }
        .custom-modal-body .input-group {
        /* display: flex; */ /* Eliminamos flex para que los elementos se apilen */
        margin-bottom: 15px;
    }
    .custom-modal-body .input-group input {
        /* flex: 1; */ /* Ya no es necesario con display: block */
        width: 100%; /* Aseguramos que ocupe todo el ancho */
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-right: 0; /* Eliminamos el margen derecho */
        margin-bottom: 10px; /* Añadimos margen inferior para separar del botón */
    }
    .custom-modal-body .input-group button {
        width: 100%; /* Botón ocupa todo el ancho */
        padding: 8px 15px;
        background-color: #1abc9c;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: block; /* Aseguramos que el botón sea un bloque */
    }
    .custom-modal-body .list-group {
        list-style: none;
        padding: 0;
    }
    .custom-modal-body .list-group-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .custom-modal-body .list-group-item:last-child {
        border-bottom: none;
    }
    .custom-modal-body .list-group-item .btn-danger {
        background-color: #e74c3c;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }
</style>

<h2>Mis plantillas</h2>

<!-- Contenedor para los botones de filtro y el botón del modal -->
<div class="tabs-container">
    <div class="tabs">
        <button onclick="switchTab('all')" class="tab-btn active" data-cat-id="all">Todas</button>
        <?php foreach ($categorias as $cat): ?>
            <button onclick="switchTab(<?= $cat['id'] ?>)" data-cat-id="<?= $cat['id'] ?>" class="tab-btn">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
        </div>
</div>

<!-- Grid para mostrar las plantillas -->
<div class="grid-plantillas">
    <?php foreach ($plantillas as $plantilla): ?>
        <?php
            $content_decoded = json_decode($plantilla['content'], true);
            $plantilla_data = [
                'linea1' => ['texto' => $content_decoded['linea1'] ?? '', 'fuente' => $plantilla['fuente_linea_1'], 'tamano' => $plantilla['tamano_linea_1'], 'negrita' => !empty($plantilla['bold_linea_1']), 'alineacion' => $plantilla['alineacion_linea_1'], 'margen' => (int)($plantilla['margen_top_linea_1'] ?? 0)],
                'linea2' => ['texto' => $content_decoded['linea2'] ?? '', 'fuente' => $plantilla['fuente_linea_2'], 'tamano' => $plantilla['tamano_linea_2'], 'negrita' => !empty($plantilla['bold_linea_2']), 'alineacion' => $plantilla['alineacion_linea_2'], 'margen' => (int)($plantilla['margen_top_linea_2'] ?? 0)],
                'linea3' => ['texto' => $content_decoded['linea3'] ?? '', 'fuente' => $plantilla['fuente_linea_3'], 'tamano' => $plantilla['tamano_linea_3'], 'negrita' => !empty($plantilla['bold_linea_3']), 'alineacion' => $plantilla['alineacion_linea_3'], 'margen' => (int)($plantilla['margen_top_linea_3'] ?? 0)],
                'linea4' => ['texto' => $content_decoded['linea4'] ?? '', 'fuente' => $plantilla['fuente_linea_4'], 'tamano' => $plantilla['tamano_linea_4'], 'negrita' => !empty($plantilla['bold_linea_4']), 'alineacion' => $plantilla['alineacion_linea_4'], 'margen' => (int)($plantilla['margen_top_linea_4'] ?? 0)]
            ];
        ?>
        <div class="plantilla-card" 
             data-cat-id="<?= $plantilla['category_id'] ?>" 
             data-template-id="<?= $plantilla['id'] ?>"
             data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
            
            <div class="plantilla-preview-scale-wrapper" style="transform: scale(0.6); transform-origin: center center;">
                <div class="plantilla-preview-wrapper" id="preview-container-<?= $plantilla['id'] ?>">
                    <?php include 'includes/_plantilla_preview.php'; ?>
                </div>
            </div>

            <div class="titulo"><h4><?= htmlspecialchars($plantilla['nombre']) ?></h4></div>
            
            <div class="acciones">
                <a href="editar_plantilla.php?id=<?= $plantilla['id'] ?>"><span class="icon">✏️</span> <span class="text">Editar</span></a>
                <a href="duplicar_plantilla.php?id=<?= $plantilla['id'] ?>"><span class="icon">📄</span> <span class="text">Duplicar</span></a>
                <a href="eliminar_plantilla.php?id=<?= $plantilla['id'] ?>" onclick="return confirm('¿Seguro?')"><span class="icon">🗑️</span> <span class="text">Eliminar</span></a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="fab-container">
    <a href="#" id="openCategoriasModalBtn" class="boton-flotante"><span class="icon">⚙️</span><span class="text">Categorías</span></a>
    <a href="nueva_plantilla.php" class="boton-flotante"><span class="icon">+</span><span class="text">Agregar Plantilla</span></a>
</div>

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
// Script para la funcionalidad de los tabs
function switchTab(categoriaId) {
    document.querySelectorAll('.plantilla-card').forEach(card => {
        card.style.display = (categoriaId === 'all' || card.dataset.catId == categoriaId) ? 'flex' : 'none';
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.catId === String(categoriaId);
        btn.classList.toggle('active', isActive);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Renderizar todas las vistas previas de las plantillas al cargar la página
    document.querySelectorAll('.plantilla-card[data-template-data]').forEach(card => {
        const templateDataAttr = card.getAttribute('data-template-data');
        const templateId = card.getAttribute('data-template-id');
        if (templateDataAttr && templateId) {
            try {
                const templateData = JSON.parse(templateDataAttr);
                const previewContainer = document.getElementById('preview-container-' + templateId);
                if (previewContainer) {
                    window.renderizarPlantilla(previewContainer, templateData);
                }
            } catch (e) {
                console.error('Error al renderizar la plantilla:', templateId, e);
            }
        }
    });

    // Activar el primer tab por defecto
    switchTab('all');
});
</script>

<?php require_once 'includes/footer.php'; ?>

<!-- Modal Custom para Administrar Categorías -->
<div id="categoriasModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <h5 class="custom-modal-title">Administrar Categorías</h5>
            <span class="custom-close-button">&times;</span>
        </div>
        <div class="custom-modal-body">
            <!-- Formulario para agregar nueva categoría -->
            <form action="plantillas.php" method="post" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="nueva_categoria" placeholder="Nombre de la nueva categoría" required>
                    <button class="btn btn-primary" type="submit" name="agregar_cat">Agregar</button>
                </div>
            </form>

            <!-- Lista de categorías existentes -->
            <ul class="list-group">
                <?php foreach ($categorias as $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($cat['name']) ?>
                        <a href="plantillas.php?del_cat=<?= $cat['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar esta categoría?')">Eliminar</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
    // JavaScript para el modal custom
    const modal = document.getElementById('categoriasModal');
    const openBtn = document.getElementById('openCategoriasModalBtn'); // El botón que abre el modal
    const closeBtn = document.getElementsByClassName("custom-close-button")[0];

    if (openBtn) {
        openBtn.onclick = function(event) {
            event.preventDefault(); // Evita el comportamiento por defecto del enlace
            modal.style.display = "flex"; // Usar flex para centrar
        }
    }

    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
