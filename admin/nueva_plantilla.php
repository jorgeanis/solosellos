<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// No se carga plantilla existente, se inicializa vacía
$plantilla = [
    'nombre' => '',
    'category_id' => null,
    'fuente_linea_1' => 'Roboto',
    'fuente_linea_2' => 'Roboto',
    'fuente_linea_3' => 'Roboto',
    'fuente_linea_4' => 'Roboto',
    'tamano_linea_1' => 20,
    'tamano_linea_2' => 20,
    'tamano_linea_3' => 20,
    'tamano_linea_4' => 20,
    'bold_linea_1' => 0,
    'bold_linea_2' => 0,
    'bold_linea_3' => 0,
    'bold_linea_4' => 0,
    'alineacion_linea_1' => 'center',
    'alineacion_linea_2' => 'center',
    'alineacion_linea_3' => 'center',
    'alineacion_linea_4' => 'center',
    'margen_top_linea_1' => 0,
    'margen_top_linea_2' => 0,
    'margen_top_linea_3' => 0,
    'margen_top_linea_4' => 0,
];
$content_decoded = [
    'linea1' => 'Juan J. Gonzalez',
    'linea2' => 'Prof. Nivel Inicial',
    'linea3' => 'Leg. Num: 000000',
    'linea4' => 'Colegio Nacional B. Mitre',
];

// Lógica de Inserción (POST)
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
            $_POST['fuente1'], $_POST['tamano1'], isset($_POST['negrita1']) ? 1 : 0, $_POST['alineacion1'], $_POST['margen_top1'],
            $_POST['fuente2'], $_POST['tamano2'], isset($_POST['negrita2']) ? 1 : 0, $_POST['alineacion2'], $_POST['margen_top2'],
            $_POST['fuente3'], $_POST['tamano3'], isset($_POST['negrita3']) ? 1 : 0, $_POST['alineacion3'], $_POST['margen_top3'],
            $_POST['fuente4'], $_POST['tamano4'], isset($_POST['negrita4']) ? 1 : 0, $_POST['alineacion4'], $_POST['margen_top4']
        ]);

        header("Location: plantillas.php");
        exit;
    } catch (PDOException $e) {
        echo "<div style='color:red'>❌ Error al crear plantilla: " . $e->getMessage() . "</div>";
    }
}

// Carga de categorías
$stmt_cats = $pdo->prepare("SELECT id, name FROM template_categories WHERE user_id = ?");
$stmt_cats->execute([$_SESSION['user']['id']]);
$categorias = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

// Cargar todas las fuentes de Google Fonts necesarias para los selectores
$todas_las_fuentes = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Merriweather', 'Nunito', 'Oswald', 'Ubuntu', 'PT Sans', 'Quicksand', 'Work Sans', 'Bebas Neue', 'Archivo', 'Fira Sans', 'Playfair Display', 'Rubik', 'Caveat', 'Dancing Script', 'Shadows Into Light', 'Satisfy', 'Great Vibes', 'Permanent Marker', 'Patrick Hand', 'Gloria Hallelujah', 'Indie Flower', 'Fredoka', 'Josefin Sans', 'Amatic SC'];
foreach ($todas_las_fuentes as $fuente) {
    $nombre_fuente = str_replace(' ', '+', $fuente);
    echo "<link href='https://fonts.googleapis.com/css2?family={$nombre_fuente}&display=swap' rel='stylesheet'>\n";
}
?>

<link rel="stylesheet" href="../assets/css/plantilla-preview.css">
<link rel="stylesheet" href="../assets/css/plantilla-editor.css">

<h2>Nueva plantilla</h2>

<form method="POST">
    <div class="editor-wrapper">
        <div class="controles-col">
            <div class="form-top">
                <label>Nombre: <input type="text" name="nombre" value="<?= htmlspecialchars($plantilla["nombre"]) ?>" required></label>
                <label>Categoría:
                    <select name="categoria" required>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $plantilla["category_id"] ? "selected" : "" ?>> 
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="tab-buttons">
                <button type="button" class="tab-btn active" onclick="showTab(1)">Línea 1</button>
                <button type="button" class="tab-btn" onclick="showTab(2)">Línea 2</button>
                <button type="button" class="tab-btn" onclick="showTab(3)">Línea 3</button>
                <button type="button" class="tab-btn" onclick="showTab(4)">Línea 4</button>
            </div>

            <?php for ($i = 1; $i <= 4; $i++):
                $line_number = $i;
                $selected_font = $plantilla["fuente_linea_$i"];
            ?>
            <div class="tab-content <?= $i == 1 ? 'active' : '' ?>" id="tab<?= $i ?>">
                <div class="line-controls">
                    <label>Texto:</label>
                    <input type="text" name="linea<?= $i ?>" value="<?= htmlspecialchars($content_decoded["linea$i"] ?? '') ?>">
                    
                    <label>Fuente:</label>
                    <?php 
                        include 'includes/_font_selector.php'; 
                    ?>

                    <label>Tamaño:</label>
                    <input type="number" name="tamano<?= $i ?>" value="<?= $plantilla["tamano_linea_$i"] ?>" min="8" max="100">
                    
                    <label>Margen Superior:</label>
                    <input type="number" name="margen_top<?= $i ?>" value="<?= $plantilla["margen_top_linea_$i"] ?>">

                    <label>Alineación:</label>
                    <input type="hidden" name="alineacion<?= $i ?>" id="alineacion<?= $i ?>" value="<?= $plantilla["alineacion_linea_$i"] ?>">
                    <div class="alineacion-btns" data-linea="<?= $i ?>">
                        <button type="button" onclick="setAlign(<?= $i ?>, 'left', this)">Izquierda</button>
                        <button type="button" onclick="setAlign(<?= $i ?>, 'center', this)">Centro</button>
                        <button type="button" onclick="setAlign(<?= $i ?>, 'right', this)">Derecha</button>
                    </div>

                    <label><input type="checkbox" name="negrita<?= $i ?>" <?= !empty($plantilla["bold_linea_$i"]) ? "checked" : "" ?>> Negrita</label>
                </div>
            </div>
            <?php endfor; ?>
            
            <button type="submit">Guardar Cambios</button>
        </div>

        <div class="preview-col">
            <div id="preview-container">
                <?php include 'includes/_plantilla_preview.php'; ?>
            </div>
        </div>
    </div>
</form>

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const previewContainer = document.getElementById('preview-container');

    function actualizarVistaPrevia() {
        const datos = {
            linea1: {},
            linea2: {},
            linea3: {},
            linea4: {}
        };

        for (let i = 1; i <= 4; i++) {
            const negritaCheckbox = form.querySelector(`input[type="checkbox"][name="negrita${i}"]`);
            datos['linea' + i] = {
                texto: form.querySelector(`[name="linea${i}"]`).value,
                fuente: form.querySelector(`[name="fuente${i}"]`).value,
                tamano: form.querySelector(`[name="tamano${i}"]`).value,
                negrita: negritaCheckbox ? negritaCheckbox.checked : false,
                alineacion: form.querySelector(`[name="alineacion${i}"]`).value,
                margen: form.querySelector(`[name="margen_top${i}"]`).value
            };
        }
        window.renderizarPlantilla(previewContainer, datos);
    }

    // Event listener para todos los cambios en el formulario
    form.addEventListener('input', actualizarVistaPrevia);
    form.addEventListener('change', actualizarVistaPrevia); // Para selects y checkboxes

    // Funciones auxiliares para botones
    window.showTab = function(n) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.getElementById('tab' + n).classList.add('active');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        form.querySelector(`.tab-buttons button:nth-child(${n})`).classList.add('active');
        actualizarVistaPrevia(); // Asegura que la vista previa se actualice al cambiar de pestaña
    }

    window.setAlign = function(linea, direccion, btn) {
        document.getElementById('alineacion' + linea).value = direccion;
        // Quitar clase activa de botones hermanos
        const parent = btn.parentElement;
        parent.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        actualizarVistaPrevia(); // Actualizar la vista previa
    }

    // Renderizado inicial
    actualizarVistaPrevia();

    // Seteado inicial de botones de alineación
    for (let i = 1; i <= 4; i++) {
        const alignValue = document.getElementById('alineacion' + i).value;
        const alignButton = form.querySelector(`.alineacion-btns[data-linea="${i}"] button[onclick*="'${alignValue}'"]`);
        if (alignButton) {
            alignButton.classList.add('active');
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>