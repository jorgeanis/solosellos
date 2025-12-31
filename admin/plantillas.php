<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Lógica para gestionar categorías (sin cambios en la lógica PHP)
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

require_once 'includes/header.php';
?>

<!-- Estilos específicos para Previsualización (Legacy Support) -->
<link rel="stylesheet" href="../assets/css/plantilla-preview.css">
<?php
// Cargar dinámicamente las fuentes de Google Fonts
$fuentes_usadas = [];
foreach ($plantillas as $p) {
    for ($i = 1; $i <= 4; $i++) {
        $f = trim($p["fuente_linea_$i"] ?? '');
        if ($f && !in_array($f, $fuentes_usadas)) {
            $fuentes_usadas[] = $f;
        }
    }
}
foreach ($fuentes_usadas as $fuente): ?>
    <link href='https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $fuente) ?>:wght@400;700&display=swap' rel='stylesheet'>
<?php endforeach; ?>

<style>
    /* Estilos replicados de index.php para garantizar visualización idéntica */
    .plantilla-preview-container-scaled {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.65);
        width: 380px; /* Ancho original del contenido */
        height: 140px; /* Alto original del contenido */
        pointer-events: none; /* Evitar interacción con el preview escalado */
    }
</style>

<!-- Header de Página & Filtros -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div class="flex flex-wrap gap-2 p-1 bg-gray-200 rounded-xl overflow-x-auto max-w-full">
        <button onclick="switchTab('all')" data-cat-id="all" class="tab-btn px-4 py-2 rounded-lg text-sm font-semibold transition-all bg-white text-brand-600 shadow-sm">
            Todas
        </button>
        <?php foreach ($categorias as $cat): ?>
            <button onclick="switchTab(<?= $cat['id'] ?>)" data-cat-id="<?= $cat['id'] ?>" class="tab-btn px-4 py-2 rounded-lg text-sm font-semibold text-gray-500 hover:text-gray-700 transition-all">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="flex gap-2 w-full md:w-auto">
        <button onclick="abrirCategoriasModal()" class="flex-1 md:flex-none px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium shadow-sm flex items-center justify-center gap-2">
            <i class="fas fa-tags text-brand-500"></i> Categorías
        </button>
        <a href="nueva_plantilla.php" class="flex-1 md:flex-none px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors text-sm font-bold shadow-sm shadow-brand-100 flex items-center justify-center gap-2">
            <i class="fas fa-plus"></i> Nueva
        </a>
    </div>
</div>

<!-- Grid de Plantillas -->
<div id="grid-plantillas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
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
        <div class="plantilla-card bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group" 
             data-cat-id="<?= $plantilla['category_id'] ?>" 
             data-template-id="<?= $plantilla['id'] ?>"
             data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
            
            <!-- Preview Container Replicado de Index.php -->
            <div class="relative w-full h-[160px] bg-gray-50 border-b border-gray-100 overflow-hidden">
                <div class="plantilla-preview-container-scaled">
                    <div class="plantilla-preview-wrapper" id="preview-container-<?= $plantilla['id'] ?>">
                        <?php include 'includes/_plantilla_preview.php'; ?>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="p-4 flex-1 flex flex-col justify-between">
                <h4 class="text-sm font-bold text-gray-800 text-center mb-4 line-clamp-1 group-hover:text-brand-600 transition-colors">
                    <?= htmlspecialchars($plantilla['nombre']) ?>
                </h4>
                
                <div class="flex items-center justify-center gap-1">
                    <a href="editar_plantilla.php?id=<?= $plantilla['id'] ?>" class="flex-1 flex flex-col items-center p-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors" title="Editar">
                        <i class="fas fa-pen mb-1.5 text-base"></i>
                        <span>Editar</span>
                    </a>
                    <a href="duplicar_plantilla.php?id=<?= $plantilla['id'] ?>" class="flex-1 flex flex-col items-center p-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-brand-50 hover:text-brand-600 transition-colors" title="Duplicar">
                        <i class="fas fa-copy mb-1.5 text-base"></i>
                        <span>Duplicar</span>
                    </a>
                    <a href="eliminar_plantilla.php?id=<?= $plantilla['id'] ?>" onclick="return confirm('¿Eliminar esta plantilla?')" class="flex-1 flex flex-col items-center p-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors" title="Eliminar">
                        <i class="fas fa-trash-alt mb-1.5 text-base"></i>
                        <span>Eliminar</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Administrar Categorías -->
<div id="categoriasModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-bounce-in">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h5 class="text-lg font-bold text-gray-800">Administrar Categorías</h5>
            <button onclick="cerrarCategoriasModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <!-- Formulario para agregar nueva categoría -->
            <form action="plantillas.php" method="post" class="mb-6">
                <div class="flex gap-2">
                    <input type="text" name="nueva_categoria" placeholder="Nueva categoría..." required
                        class="flex-1 border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 bg-gray-50 outline-none">
                    <button type="submit" name="agregar_cat" class="bg-brand-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-brand-700 transition-colors">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </form>

            <div class="max-h-60 overflow-y-auto custom-scroll pr-1">
                <ul class="space-y-2">
                    <?php foreach ($categorias as $cat): ?>
                        <li class="flex justify-between items-center p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($cat['name']) ?></span>
                            <a href="plantillas.php?del_cat=<?= $cat['id'] ?>" onclick="return confirm('¿Seguro?')" class="text-red-400 hover:text-red-600 p-1.5 transition-colors">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-right">
            <button onclick="cerrarCategoriasModal()" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-800 transition-colors">Cerrar</button>
        </div>
    </div>
</div>

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
// Manejo de Filtros por Categoría
function switchTab(categoriaId) {
    document.querySelectorAll('.plantilla-card').forEach(card => {
        const match = (categoriaId === 'all' || card.dataset.catId == categoriaId);
        if (match) {
            card.classList.remove('hidden');
            card.classList.add('flex');
        } else {
            card.classList.add('hidden');
            card.classList.remove('flex');
        }
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.catId === String(categoriaId);
        if (isActive) {
            btn.classList.add('bg-white', 'text-brand-600', 'shadow-sm');
            btn.classList.remove('text-gray-500', 'hover:text-gray-700');
        } else {
            btn.classList.remove('bg-white', 'text-brand-600', 'shadow-sm');
            btn.classList.add('text-gray-500', 'hover:text-gray-700');
        }
    });
}

// Manejo del Modal
function abrirCategoriasModal() {
    const modal = document.getElementById('categoriasModal');
    modal.classList.remove('hidden');
}

function cerrarCategoriasModal() {
    const modal = document.getElementById('categoriasModal');
    modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    // Renderizar Previsualizaciones
    document.querySelectorAll('.plantilla-card[data-template-data]').forEach(card => {
        const templateDataAttr = card.getAttribute('data-template-data');
        const templateId = card.getAttribute('data-template-id');
        if (templateDataAttr && templateId) {
            try {
                const templateData = JSON.parse(templateDataAttr);
                const previewContainer = document.getElementById('preview-container-' + templateId);
                if (previewContainer) {
                    window.renderizarPlantilla(previewContainer, templateData, 'black');
                }
            } catch (e) {
                console.error('Error al renderizar la plantilla:', templateId, e);
            }
        }
    });

    // Activar primer tab
    switchTab('all');

    // Cerrar modal al clickear fuera
    const modal = document.getElementById('categoriasModal');
    modal.addEventListener('click', (e) => {
        if(e.target === modal) cerrarCategoriasModal();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>