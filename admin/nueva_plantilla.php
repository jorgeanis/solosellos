<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// --- INICIALIZACIÓN DE VALORES POR DEFECTO ---
$plantilla = [
    'nombre' => '',
    'category_id' => null,
    'fuente_linea_1' => 'Roboto',
    'fuente_linea_2' => 'Roboto',
    'fuente_linea_3' => 'Roboto',
    'fuente_linea_4' => 'Roboto',
    'tamano_linea_1' => 16,
    'tamano_linea_2' => 12,
    'tamano_linea_3' => 10,
    'tamano_linea_4' => 8,
    'bold_linea_1' => 1,
    'bold_linea_2' => 0,
    'bold_linea_3' => 0,
    'bold_linea_4' => 0,
    'alineacion_linea_1' => 'center',
    'alineacion_linea_2' => 'center',
    'alineacion_linea_3' => 'center',
    'alineacion_linea_4' => 'center',
    'margen_top_linea_1' => 0,
    'margen_top_linea_2' => 2,
    'margen_top_linea_3' => 2,
    'margen_top_linea_4' => 2,
];
$content_decoded = [
    'linea1' => 'Juan J. Gonzalez',
    'linea2' => 'Prof. Nivel Inicial',
    'linea3' => 'Leg. Num: 000000',
    'linea4' => 'Colegio Nacional B. Mitre',
];

// --- LÓGICA DE INSERCIÓN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $category_id = $_POST['categoria'];
    $nombre = $_POST['nombre'];
    $content = json_encode([
        'linea1' => $_POST['linea1'],
        'linea2' => $_POST['linea2'],
        'linea3' => $_POST['linea3'],
        'linea4' => $_POST['linea4']
    ]);

    try {
        $stmt = $pdo->prepare("INSERT INTO templates (
            category_id, content, user_id, nombre,
            fuente_linea_1, tamano_linea_1, bold_linea_1, alineacion_linea_1, margen_top_linea_1,
            fuente_linea_2, tamano_linea_2, bold_linea_2, alineacion_linea_2, margen_top_linea_2,
            fuente_linea_3, tamano_linea_3, bold_linea_3, alineacion_linea_3, margen_top_linea_3,
            fuente_linea_4, tamano_linea_4, bold_linea_4, alineacion_linea_4, margen_top_linea_4,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $category_id, $content, $user_id, $nombre,
            $_POST['fuente1'], $_POST['tamano1'], $_POST['negrita1_hidden'], $_POST['alineacion1'], $_POST['margen_top1'],
            $_POST['fuente2'], $_POST['tamano2'], $_POST['negrita2_hidden'], $_POST['alineacion2'], $_POST['margen_top2'],
            $_POST['fuente3'], $_POST['tamano3'], $_POST['negrita3_hidden'], $_POST['alineacion3'], $_POST['margen_top3'],
            $_POST['fuente4'], $_POST['tamano4'], $_POST['negrita4_hidden'], $_POST['alineacion4'], $_POST['margen_top4']
        ]);

        echo "<script>window.location.href='plantillas.php?msg=created';</script>";
        exit;
    } catch (PDOException $e) {
        $error = "Error al crear plantilla: " . $e->getMessage();
    }
}

// Carga de categorías
$stmt_cats = $pdo->prepare("SELECT id, name FROM template_categories WHERE user_id = ?");
$stmt_cats->execute([$_SESSION['user']['id']]);
$categorias = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/fonts.php';
?>

<!-- AOS CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<!-- Estilos para Preview -->
<link rel="stylesheet" href="../assets/css/plantilla-preview.css">

<!-- Google Fonts Loader -->
<?php foreach ($todas_las_fuentes as $fuente): ?>
    <link href='https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $fuente) ?>:wght@400;700&display=swap' rel='stylesheet'>
<?php endforeach; ?>

<!-- Preloader -->
<div id="preloader" class="fixed inset-0 bg-gray-100 z-[9999] flex flex-col items-center justify-center transition-opacity duration-500">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
    <h2 class="text-xl font-semibold text-gray-700 animate-pulse">Cargando Editor...</h2>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                <i class="fas fa-plus-circle text-brand-600 mr-2"></i>Nueva Plantilla
            </h1>
            <p class="text-gray-500 text-sm mt-1">Crea un diseño desde cero para tus sellos</p>
        </div>
        <div class="flex gap-3">
            <a href="plantillas.php" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors shadow-sm font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
            <button type="submit" form="createForm" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors shadow-lg shadow-brand-500/30 font-bold">
                <i class="fas fa-check mr-2"></i>Crear Plantilla
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm" role="alert" data-aos="fade-in">
            <p class="font-bold">Error</p>
            <p><?= $error ?></p>
        </div>
    <?php endif; ?>

    <form id="createForm" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- COLUMNA IZQUIERDA: CONTROLES -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Configuración General -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" data-aos="fade-up" data-aos-delay="100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Información Básica</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Plantilla</label>
                        <input type="text" name="nombre" placeholder="Ej: Sello Personal" required
                            class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="categoria" required class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 transition-colors">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pestañas de Líneas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                
                <!-- Tab Navigation -->
                <div class="flex overflow-x-auto border-b border-gray-100 bg-gray-50/50 p-2 gap-2" id="tab-buttons">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <button type="button" onclick="showTab(<?= $i ?>)" 
                            class="tab-btn flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all duration-200 <?= $i == 1 ? 'bg-white text-brand-600 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' ?>"
                            data-tab="<?= $i ?>">
                            Línea <?= $i ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <!-- Tab Contents -->
                <div class="p-6">
                    <?php for ($i = 1; $i <= 4; $i++): 
                        $is_bold = !empty($plantilla["bold_linea_$i"]);
                        // Variables requeridas por _font_selector.php
                        $line_number = $i;
                        $selected_font = $plantilla["fuente_linea_$i"];
                    ?>
                    <div id="tab-content-<?= $i ?>" class="tab-content <?= $i == 1 ? 'block' : 'hidden' ?> space-y-5">
                        
                        <!-- Texto y Negrita -->
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Texto por defecto</label>
                                <input type="text" name="linea<?= $i ?>" value="<?= htmlspecialchars($content_decoded["linea$i"] ?? '') ?>" 
                                    class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-gray-800">
                            </div>
                            <div>
                                <input type="hidden" name="negrita<?= $i ?>_hidden" id="negrita<?= $i ?>" value="<?= $is_bold ? '1' : '0' ?>">
                                <button type="button" onclick="toggleBold(<?= $i ?>, this)" 
                                    class="h-[42px] px-4 rounded-lg border font-bold transition-all flex items-center gap-2 <?= $is_bold ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' ?>">
                                    <i class="fas fa-bold"></i>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Fuente -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tipografía</label>
                                <div class="relative">
                                    <div class="custom-select-wrapper">
                                        <?php include 'includes/_font_selector.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Tamaño -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tamaño (px)</label>
                                <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-white">
                                    <button type="button" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 border-r border-gray-300 text-gray-600" onclick="adjustValue('tamano<?= $i ?>', -1)">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" name="tamano<?= $i ?>" id="tamano<?= $i ?>" value="<?= $plantilla["tamano_linea_$i"] ?>" min="8" max="100"
                                        class="w-full text-center border-none focus:ring-0 p-2 text-gray-800 font-semibold appearance-none">
                                    <button type="button" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 border-l border-gray-300 text-gray-600" onclick="adjustValue('tamano<?= $i ?>', 1)">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Alineación -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Alineación</label>
                                <input type="hidden" name="alineacion<?= $i ?>" id="alineacion<?= $i ?>" value="<?= $plantilla["alineacion_linea_$i"] ?>">
                                <div class="flex rounded-lg bg-gray-100 p-1 gap-1 alineacion-group" data-linea="<?= $i ?>">
                                    <button type="button" onclick="setAlign(<?= $i ?>, 'left', this)" class="flex-1 py-2 rounded-md text-sm transition-all <?= $plantilla["alineacion_linea_$i"] == 'left' ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                                        <i class="fas fa-align-left"></i>
                                    </button>
                                    <button type="button" onclick="setAlign(<?= $i ?>, 'center', this)" class="flex-1 py-2 rounded-md text-sm transition-all <?= $plantilla["alineacion_linea_$i"] == 'center' ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                                        <i class="fas fa-align-center"></i>
                                    </button>
                                    <button type="button" onclick="setAlign(<?= $i ?>, 'right', this)" class="flex-1 py-2 rounded-md text-sm transition-all <?= $plantilla["alineacion_linea_$i"] == 'right' ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                                        <i class="fas fa-align-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Margen Superior -->
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Margen Superior (px)</label>
                                <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-white">
                                    <button type="button" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 border-r border-gray-300 text-gray-600" onclick="adjustValue('margen_top<?= $i ?>', -1)">
                                        <i class="fas fa-arrow-up text-xs"></i>
                                    </button>
                                    <input type="number" name="margen_top<?= $i ?>" id="margen_top<?= $i ?>" value="<?= $plantilla["margen_top_linea_$i"] ?>"
                                        class="w-full text-center border-none focus:ring-0 p-2 text-gray-800 font-semibold appearance-none">
                                    <button type="button" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 border-l border-gray-300 text-gray-600" onclick="adjustValue('margen_top<?= $i ?>', 1)">
                                        <i class="fas fa-arrow-down text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: PREVIEW -->
        <div class="lg:col-span-5">
            <div class="sticky top-6 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden" data-aos="fade-left" data-aos-delay="300">
                <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">Vista Previa</h3>
                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">En vivo</span>
                </div>
                
                <div class="p-8 flex justify-center bg-gray-100 min-h-[300px] items-center">
                    <!-- Contenedor del Preview -->
                    <div id="preview-wrapper" class="transform transition-transform duration-300 hover:scale-105">
                        <div id="preview-container">
                            <?php include 'includes/_plantilla_preview.php'; ?>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-gray-100 text-center text-sm text-gray-500">
                    Así se verá el sello final
                </div>
            </div>
        </div>

    </form>
</div>

<!-- Scripts Libraries -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="../assets/js/plantilla-renderer.js"></script>

<script>
    // Inicializar AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // Preloader
    window.addEventListener('load', () => {
        const preloader = document.getElementById('preloader');
        preloader.classList.add('opacity-0');
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('createForm');
        const previewContainer = document.getElementById('preview-container');

        // Estilizar los selects de fuentes
        document.querySelectorAll('select[name^="fuente"]').forEach(select => {
            select.classList.add('w-full', 'rounded-lg', 'border-gray-300', 'focus:border-brand-500', 'focus:ring-brand-500', 'transition-colors', 'text-sm');
        });

        // --- LÓGICA DE ACTUALIZACIÓN DE PREVIEW ---
        function actualizarVistaPrevia() {
            const datos = {
                linea1: {}, linea2: {}, linea3: {}, linea4: {}
            };

            for (let i = 1; i <= 4; i++) {
                const boldInput = document.getElementById(`negrita${i}`);
                const textoInput = form.querySelector(`[name="linea${i}"]`);
                const fuenteInput = form.querySelector(`[name="fuente${i}"]`);
                const tamanoInput = form.querySelector(`[name="tamano${i}"]`);
                const alineacionInput = form.querySelector(`[name="alineacion${i}"]`);
                const margenInput = form.querySelector(`[name="margen_top${i}"]`);

                // Seguridad: Verificar que los elementos existan
                datos['linea' + i] = {
                    texto: textoInput ? textoInput.value : '',
                    fuente: fuenteInput ? fuenteInput.value : 'Roboto',
                    tamano: tamanoInput ? tamanoInput.value : '12',
                    negrita: boldInput ? boldInput.value === '1' : false,
                    alineacion: alineacionInput ? alineacionInput.value : 'center',
                    margen: margenInput ? margenInput.value : '0'
                };
            }
            window.renderizarPlantilla(previewContainer, datos, 'black');
        }

        // Listeners
        form.addEventListener('input', actualizarVistaPrevia);
        form.addEventListener('change', actualizarVistaPrevia);

        // --- FUNCIONES UI ---

        // Cambiar Pestañas
        window.showTab = function(n) {
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('block');
            });
            document.getElementById('tab-content-' + n).classList.remove('hidden');
            document.getElementById('tab-content-' + n).classList.add('block');

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'text-brand-600', 'shadow-sm', 'ring-1', 'ring-gray-200');
                btn.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:bg-gray-100');
            });
            const activeBtn = document.querySelector(`.tab-btn[data-tab="${n}"]`);
            activeBtn.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:bg-gray-100');
            activeBtn.classList.add('bg-white', 'text-brand-600', 'shadow-sm', 'ring-1', 'ring-gray-200');
        }

        // Alineación (UI Logic)
        window.setAlign = function(linea, direccion, btn) {
            document.getElementById('alineacion' + linea).value = direccion;
            const group = document.querySelector(`.alineacion-group[data-linea="${linea}"]`);
            group.querySelectorAll('button').forEach(b => {
                b.classList.remove('bg-white', 'text-brand-600', 'shadow-sm');
                b.classList.add('text-gray-500', 'hover:text-gray-700');
            });
            btn.classList.remove('text-gray-500', 'hover:text-gray-700');
            btn.classList.add('bg-white', 'text-brand-600', 'shadow-sm');
            actualizarVistaPrevia();
        }

        // Negrita (UI Logic)
        window.toggleBold = function(linea, btn) {
            const input = document.getElementById('negrita' + linea);
            const isBold = input.value === '1';
            input.value = isBold ? '0' : '1';
            
            if (!isBold) {
                btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-300', 'hover:bg-gray-50');
                btn.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
            } else {
                btn.classList.remove('bg-gray-800', 'text-white', 'border-gray-800');
                btn.classList.add('bg-white', 'text-gray-600', 'border-gray-300', 'hover:bg-gray-50');
            }
            actualizarVistaPrevia();
        }

        // Ajustar Valores (+/- botones)
        window.adjustValue = function(inputId, delta) {
            const input = document.getElementById(inputId);
            let val = parseInt(input.value) || 0;
            val += delta;
            if (inputId.includes('tamano') && val < 8) val = 8;
            input.value = val;
            actualizarVistaPrevia();
        }

        // Renderizado Inicial
        actualizarVistaPrevia();
    });
</script>

<?php require_once 'includes/footer.php'; ?>