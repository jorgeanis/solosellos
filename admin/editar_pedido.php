<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/fonts.php';

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    die("ID de pedido no especificado.");
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $lastname = $_POST['lastname'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $comments = $_POST['comments'];
        $model_id = $_POST['model_id']; // Aunque generalmente no cambiamos el modelo aquí, lo dejamos por si acaso
        $template_id = $_POST['template_id'];

        // Procesar estilos y textos
        $styles_data = [
            'fuente' => [], 'tamano' => [], 'bold' => [], 
            'alineacion' => [], 'margen_top' => [], 'mayuscula' => []
        ];
        
        $text_lines = [];

        for ($i = 1; $i <= 4; $i++) {
            $text_lines[$i] = $_POST["linea{$i}_texto"];
            $styles_data['fuente'][$i-1] = $_POST["linea{$i}_fuente"];
            $styles_data['tamano'][$i-1] = $_POST["linea{$i}_tamano"];
            $styles_data['bold'][$i-1] = isset($_POST["linea{$i}_bold"]);
            $styles_data['alineacion'][$i-1] = $_POST["linea{$i}_alineacion"];
            $styles_data['margen_top'][$i-1] = $_POST["linea{$i}_margen"];
            $styles_data['mayuscula'][$i-1] = isset($_POST["linea{$i}_mayuscula"]);
        }

        $estilos_json = json_encode($styles_data);

        $stmt = $pdo->prepare("UPDATE orders SET 
            name = ?, lastname = ?, phone = ?, address = ?, comments = ?,
            text_line1 = ?, text_line2 = ?, text_line3 = ?, text_line4 = ?,
            styles = ?
            WHERE id = ? AND user_id = ?");
        
        $stmt->execute([
            $name, $lastname, $phone, $address, $comments,
            $text_lines[1], $text_lines[2], $text_lines[3], $text_lines[4],
            $estilos_json,
            $order_id, $_SESSION['user']['id']
        ]);

        header("Location: pedidos.php?msg=pedido_actualizado");
        exit;

    } catch (Exception $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// Obtener datos del pedido
$stmt = $pdo->prepare("
    SELECT o.*, t.nombre as template_name, m.title as model_name, m.image as model_image
    FROM orders o
    JOIN templates t ON o.template_id = t.id
    JOIN models m ON o.model_id = m.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user']['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pedido no encontrado o no tienes permiso para editarlo.");
}

// Verificar estado
if (strtolower($order['status']) !== 'pendiente') {
    echo "<div style='padding: 20px; color: red; font-family: sans-serif;'>Atención: Estás editando un pedido que no está en estado 'Pendiente' (Estado actual: <strong>{$order['status']}</strong>). Ten cuidado si ya fue procesado.</div>";
}

$styles = json_decode($order['styles'], true);

// Construir datos iniciales para JS
$initial_data = [
    'id' => $order['template_id'],
    'nombre' => $order['template_name']
];
for ($i = 1; $i <= 4; $i++) {
    $initial_data["linea$i"] = [
        'texto' => $order["text_line$i"],
        'fuente' => $styles['fuente'][$i-1] ?? 'Arial',
        'tamano' => $styles['tamano'][$i-1] ?? 16,
        'negrita' => $styles['bold'][$i-1] ?? false,
        'alineacion' => $styles['alineacion'][$i-1] ?? 'center',
        'margen' => $styles['margen_top'][$i-1] ?? 0,
        'mayuscula' => $styles['mayuscula'][$i-1] ?? false
    ];
}

require_once 'includes/header.php';
?>

<!-- Cargar Fuentes -->
<?php
foreach ($todas_las_fuentes as $fuente) {
    $nombre_fuente = str_replace(' ', '+', $fuente);
    echo "<link href='https://fonts.googleapis.com/css2?family={$nombre_fuente}&display=swap' rel='stylesheet'>\n";
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/css/plantilla-preview.css">

<style>
    .edit-container {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 30px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .edit-container { grid-template-columns: 1fr; }
        .preview-col { position: static !important; width: 100%; order: -1; margin-bottom: 20px;}
    }
    
    .card {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        /* Tab content visibility managed by JS */
    }
    .card h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #2c3e50; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
    .form-group input, .form-group textarea, .form-group select {
        width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
    }

    /* Toolbar Styles */
    .linea-editor {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    .linea-header { font-weight: bold; margin-bottom: 10px; color: #34495e; }
    .toolbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 8px;
    }
    .btn-tool {
        background: #fff; border: 1px solid #ccc; padding: 5px 10px; border-radius: 4px; cursor: pointer;
        color: #333; /* Texto negro para contraste */
    }
    .btn-tool:hover { background: #eee; }
    .btn-tool.active { background: #3498db; color: white; border-color: #3498db; }
    
    .preview-col {
        position: sticky;
        top: 20px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    .plantilla-preview-wrapper {
        transform: scale(0.8); /* Scale down */
        transform-origin: center top;
        margin-bottom: -30px; /* Compensate whitespace */
    }

    /* Tabs Styling */
    .edit-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }
    .edit-tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        font-size: 16px;
        font-weight: 600;
        color: #7f8c8d;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .edit-tab-btn:hover { color: #3498db; }
    .edit-tab-btn.active {
        color: #3498db;
        border-bottom-color: #3498db;
    }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    /* FAB Save Button */
    .fab-save {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: #1abc9c;
        color: white;
        border-radius: 30px; /* Circle initially */
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(26, 188, 156, 0.4);
        cursor: pointer;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        z-index: 1000;
        border: none;
        font-family: inherit;
    }
    .fab-save .icon {
        font-size: 24px;
        transition: transform 0.4s ease;
    }
    .fab-save .text {
        width: 0;
        opacity: 0;
        white-space: nowrap;
        margin-left: 0;
        font-weight: bold;
        font-size: 16px;
        transition: all 0.4s ease;
    }
    .fab-save:hover {
        width: 220px; /* Expand width */
        border-radius: 30px;
        background: #16a085;
        padding-right: 15px; /* Add padding for text */
    }
    .fab-save:hover .icon {
        transform: rotate(360deg); /* Rotate icon */
    }
    .fab-save:hover .text {
        width: auto;
        opacity: 1;
        margin-left: 10px;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2><i class="fa-solid fa-pen-to-square"></i> Editar Pedido #<?= $order['id'] ?></h2>
    <a href="pedidos.php" style="color: #e74c3c; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<form method="POST">
    <!-- Campos ocultos necesarios -->
    <input type="hidden" name="model_id" value="<?= $order['model_id'] ?>">
    <input type="hidden" name="template_id" value="<?= $order['template_id'] ?>">

    <div class="edit-container">
        <!-- Columna Izquierda: Formulario -->
        <div>
            <!-- Tabs Navigation -->
            <div class="edit-tabs">
                <button type="button" class="edit-tab-btn active" onclick="switchEditTab('diseno')">🎨 Personalización</button>
                <button type="button" class="edit-tab-btn" onclick="switchEditTab('cliente')">👤 Datos Cliente</button>
            </div>

            <!-- Editor del Sello -->
            <div id="tab-diseno" class="tab-pane active card">
                <h3>Personalización del Sello</h3>
                <?php for($i=1; $i<=4; $i++): 
                    $data = $initial_data["linea$i"];
                ?>
                <div class="linea-editor">
                    <div class="linea-header">Línea <?= $i ?></div>
                    <div class="form-group" style="margin-bottom: 5px;">
                        <input type="text" name="linea<?= $i ?>_texto" id="linea<?= $i ?>_texto" value="<?= htmlspecialchars($data['texto']) ?>" class="live-update" placeholder="Texto de la línea <?= $i ?>">
                    </div>
                    
                    <div class="toolbar">
                        <!-- Fuente -->
                        <select name="linea<?= $i ?>_fuente" id="linea<?= $i ?>_fuente" class="live-update" style="width: auto;">
                            <?php foreach($todas_las_fuentes as $fuente): ?>
                                <option value="<?= $fuente ?>" <?= $data['fuente'] == $fuente ? 'selected' : '' ?>><?= $fuente ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Tamaño -->
                        <div style="display:flex; align-items:center; gap:2px;">
                            <button type="button" class="btn-tool" onclick="adjustSize(<?= $i ?>, -1)">-</button>
                            <input type="number" name="linea<?= $i ?>_tamano" id="linea<?= $i ?>_tamano" value="<?= $data['tamano'] ?>" class="live-update" style="width: 50px; text-align: center;">
                            <button type="button" class="btn-tool" onclick="adjustSize(<?= $i ?>, 1)">+</button>
                        </div>

                        <!-- Alineación -->
                        <div style="display:flex; gap:2px;">
                            <input type="hidden" name="linea<?= $i ?>_alineacion" id="linea<?= $i ?>_alineacion" value="<?= $data['alineacion'] ?>">
                            <button type="button" class="btn-tool <?= $data['alineacion']=='left'?'active':'' ?>" onclick="setAlign(<?= $i ?>, 'left', this)"><i class="fas fa-align-left"></i></button>
                            <button type="button" class="btn-tool <?= $data['alineacion']=='center'?'active':'' ?>" onclick="setAlign(<?= $i ?>, 'center', this)"><i class="fas fa-align-center"></i></button>
                            <button type="button" class="btn-tool <?= $data['alineacion']=='right'?'active':'' ?>" onclick="setAlign(<?= $i ?>, 'right', this)"><i class="fas fa-align-right"></i></button>
                        </div>

                        <!-- Margen (Mover) -->
                        <div style="display:flex; align-items:center; gap:2px;">
                            <button type="button" class="btn-tool" onclick="adjustMargin(<?= $i ?>, -2)"><i class="fas fa-arrow-up"></i></button>
                            <input type="number" name="linea<?= $i ?>_margen" id="linea<?= $i ?>_margen" value="<?= $data['margen'] ?>" class="live-update" style="width: 50px; text-align: center;" title="Margen Superior">
                            <button type="button" class="btn-tool" onclick="adjustMargin(<?= $i ?>, 2)"><i class="fas fa-arrow-down"></i></button>
                        </div>

                        <!-- Estilos -->
                        <label class="btn-tool" style="display:flex;align-items:center;gap:5px;cursor:pointer;">
                            <input type="checkbox" name="linea<?= $i ?>_bold" id="linea<?= $i ?>_bold" class="live-update" <?= $data['negrita'] ? 'checked' : '' ?>> 
                            <b>B</b>
                        </label>
                        <label class="btn-tool" style="display:flex;align-items:center;gap:5px;cursor:pointer;">
                            <input type="checkbox" name="linea<?= $i ?>_mayuscula" id="linea<?= $i ?>_mayuscula" class="live-update" <?= $data['mayuscula'] ? 'checked' : '' ?>> 
                            AA
                        </label>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Datos del Cliente -->
            <div id="tab-cliente" class="tab-pane card">
                <h3>Datos del Cliente</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($order['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($order['lastname']) ?>" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($order['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($order['email']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($order['address']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Indicaciones / Comentarios</label>
                    <textarea name="comments" rows="2"><?= htmlspecialchars($order['comments']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Vista Previa -->
        <div class="preview-col">
            <h4 style="margin-top: 0; color: #7f8c8d;">Vista Previa</h4>
            <div style="background: #eee; padding: 20px; border-radius: 8px; display: flex; justify-content: center;">
                <div class="plantilla-preview-wrapper" id="preview-wrapper">
                    <!-- El JS inyectará el contenido aquí -->
                </div>
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 10px;">Esta vista previa refleja los cambios actuales en el formulario.</p>
            
            <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                <p><strong>Modelo:</strong> <?= htmlspecialchars($order['model_name']) ?></p>
                <img src="../assets/images/<?= htmlspecialchars($order['model_image']) ?>" style="max-width: 100px;">
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button type="submit" class="fab-save" title="Guardar Cambios">
        <span class="icon">💾</span>
        <span class="text">Guardar Cambios</span>
    </button>
</form>

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
function switchEditTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
    // Deactivate all buttons
    document.querySelectorAll('.edit-tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show target content
    document.getElementById('tab-' + tabName).classList.add('active');
    // Activate target button
    document.querySelector(`.edit-tab-btn[onclick="switchEditTab('${tabName}')"]`).classList.add('active');
}

// Datos iniciales pasados desde PHP
const initialData = <?= json_encode($initial_data) ?>;

// Función para recolectar datos del formulario y renderizar
function refreshPreview() {
    const data = {
        id: initialData.id,
        nombre: initialData.nombre,
    };

    for(let i=1; i<=4; i++) {
        data['linea'+i] = {
            texto: document.getElementById('linea'+i+'_texto').value,
            fuente: document.getElementById('linea'+i+'_fuente').value,
            tamano: parseInt(document.getElementById('linea'+i+'_tamano').value),
            negrita: document.getElementById('linea'+i+'_bold').checked,
            alineacion: document.getElementById('linea'+i+'_alineacion').value,
            margen: parseInt(document.getElementById('linea'+i+'_margen').value),
            mayuscula: document.getElementById('linea'+i+'_mayuscula').checked
        };
    }

    const container = document.getElementById('preview-wrapper');
    
    // Ensure the inner structure exists for the renderer
    if (!container.querySelector('.plantilla-preview-container')) {
        container.innerHTML = `
            <div class="plantilla-preview-container">
                <div class="plantilla-linea plantilla-linea-1"></div>
                <div class="plantilla-linea plantilla-linea-2"></div>
                <div class="plantilla-linea plantilla-linea-3"></div>
                <div class="plantilla-linea plantilla-linea-4"></div>
            </div>
        `;
    }

    // La librería plantilla-renderer suele necesitar el contenedor
    window.renderizarPlantilla(container.querySelector('.plantilla-preview-container'), data);
}

// Helpers para botones
function adjustSize(line, amount) {
    const input = document.getElementById('linea'+line+'_tamano');
    let val = parseInt(input.value) + amount;
    if(val < 5) val = 5;
    input.value = val;
    refreshPreview();
}

function adjustMargin(line, amount) {
    const input = document.getElementById('linea'+line+'_margen');
    input.value = parseInt(input.value) + amount;
    refreshPreview();
}

function setAlign(line, align, btn) {
    document.getElementById('linea'+line+'_alineacion').value = align;
    // Actualizar clases visuales de botones
    const parent = btn.parentElement;
    parent.querySelectorAll('.btn-tool').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    refreshPreview();
}

// Event Listeners
document.querySelectorAll('.live-update').forEach(el => {
    el.addEventListener('input', refreshPreview);
    el.addEventListener('change', refreshPreview);
});

// Init
document.addEventListener('DOMContentLoaded', refreshPreview);
</script>

<?php require_once 'includes/footer.php'; ?>
