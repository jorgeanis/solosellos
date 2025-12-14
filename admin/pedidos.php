<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/auth.php';

$filtro = $_GET['estado'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';
$fecha = $_GET['fecha'] ?? '';

$condiciones = "orders.user_id = ?";
$params = [$_SESSION['user']['id']];

if ($filtro !== 'todos') {
    $condiciones .= " AND status = ?";
    $params[] = $filtro;
}
if (!empty($fecha)) {
    $condiciones .= " AND DATE(created_at) = ?";
    $params[] = $fecha;
}
if (!empty($busqueda)) {
    $palabras = explode(" ", $busqueda);
    foreach ($palabras as $palabra) {
        $condiciones .= " AND (
            name LIKE ? OR lastname LIKE ? OR
            text_line1 LIKE ? OR text_line2 LIKE ? OR text_line3 LIKE ? OR text_line4 LIKE ?
        )";
        for ($i = 0; $i < 6; $i++) $params[] = "%" . $palabra . "%";
    }
}

require_once 'includes/db.php';

// --- PAGINATION ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, [10, 25, 50, 100])) $per_page = 10;

// --- GET TOTAL COUNT for pagination ---
$count_sql = "SELECT COUNT(*) FROM orders JOIN users ON orders.user_id = users.id WHERE $condiciones";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

$offset = ($page - 1) * $per_page;

// --- GET ORDERS for current page ---
$sql = "SELECT orders.*, users.whatsapp, users.link_code, models.image AS model_image FROM orders JOIN models ON orders.model_id = models.id
JOIN users ON orders.user_id = users.id WHERE $condiciones ORDER BY id DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fonts = [];
foreach ($orders as $order) {
    $styles = json_decode($order["styles"], true);
    if (is_array($styles) && isset($styles["fuente"])) {
        foreach ($styles["fuente"] as $font) {
            if (!in_array($font, $fonts)) {
                $fonts[] = $font;
            }
        }
    }
}

require_once 'includes/header.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND orders.user_id = ?");
    $stmt->execute([$_POST['nuevo_estado'], $_POST['pedido_id'], $_SESSION['user']['id']]);
    header("Location: pedidos.php?" . http_build_query($_GET));
    exit;
}
?>

<?php foreach ($fonts as $font): ?>
<link href="https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $font) ?>:wght@400;700&display=swap" rel="stylesheet">
<?php endforeach; ?>

<link rel="stylesheet" href="../assets/css/plantilla-preview.css">

<style>
.preview-section {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.plantilla-preview-wrapper {
    position: relative;
    width: 380px;
    height: 100px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #000; /* Fondo negro */
    transform: scale(0.6); /* Ajustado para caber en la tabla */
    transform-origin: top left; /* Necesario para la escala */
}
.plantilla-preview-wrapper .plantilla-preview-container {
    border-color: #ccc; /* Borde gris claro */
    background: #000; /* Fondo negro para el contenedor interior */
}
.preview {
    transform: scale(0.5);
    transform-origin: top left;
    width: 420px;
    height: 180px;
    background: black;
    border: 1px dashed #aaa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    text-align: center;
    line-height: 1;
}
.linea-prev {
    width: 100%;
    margin-bottom: 5px;
}

.preview-wrapper {
    width: 270px; /* To contain the scaled plantilla-preview-wrapper (266px) */
    height: 100px; /* To contain the scaled plantilla-preview-wrapper (98px) */
    /* Removed overflow: hidden; */
    justify-content: center;
    align-items: center;
}
#sidebarWidget {
    position: fixed;
    top: 0;
    right: -350px;
    width: 320px;
    height: 100%;
    background: white;
    box-shadow: -2px 0 6px rgba(0,0,0,0.1);
    z-index: 999;
    padding: 20px;
    transition: right 0.3s ease;
}
#sidebarWidget.active {
    right: 0;
}
#sidebarWidget .close {
    position: absolute;
    top: 10px; right: 10px;
    border: none;
    background: #ccc;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
}
#openSidebarBtn {
    position: fixed;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    background: #2c3e50;
    color: white;
    width: 40px;
    height: 80px;
    border-radius: 10px 0 0 10px;
    font-size: 20px;
    cursor: pointer;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}
#sidebarWidget a.download {
    background: #3498db;
    display: block;
    color: white;
    text-align: center;
    text-decoration: none;
    padding: 7px 0;
    border-radius: 5px;
    margin-top: 10px;
}

</style>

<!-- Botón tipo pestaña -->
<div id="openSidebarBtn" onclick="abrirSidebar()">🔍</div>

<!-- Sidebar deslizante -->
<div id="sidebarWidget">
    <button class="close" onclick="cerrarSidebar()">×</button>
    <form method="GET" style="display:flex; flex-direction:column; gap:10px; margin-top:30px;">
        <label><strong>Estado:</strong></label>
        <select name="estado">
            <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="En preparación" <?= $filtro === 'En preparación' ? 'selected' : '' ?>>En preparación</option>
            <option value="Entregado" <?= $filtro === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
            <option value="Pagado" <?= $filtro === 'Pagado' ? 'selected' : '' ?>>Pagado</option>
        </select>
        <label><strong>Texto o nombre:</strong></label>
        <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
        <label><strong>Fecha:</strong></label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <div style="display: flex; gap: 10px;">
            <button type="submit" style="flex:1; font-size:14px; display:flex; align-items:center; justify-content:center;">🔍 Buscar</button>
            <button type="button" onclick="window.location.href='pedidos.php'" style="flex:1; background:#e74c3c; color:white; padding:7px 0; border-radius:5px; font-size:14px; font-family:inherit; display:flex; align-items:center; justify-content:center;">🧹 Limpiar</button>
        </div>
    </form>
    <a class="download" href="pedidos.php?<?= http_build_query(array_merge($_GET, ['exportar' => 'csv'])) ?>">📥 Descargar CSV</a>
</div>

<script>
function abrirSidebar() {
    document.getElementById('sidebarWidget').classList.add('active');
}
function cerrarSidebar() {
    document.getElementById('sidebarWidget').classList.remove('active');
}
</script>


<h2>Pedidos Recibidos</h2>
<button id="exportarPDF" style="margin-bottom: 15px; padding: 8px 12px;">📄 Exportar seleccionados a PDF</button>

<table>
    <thead>
        <tr>
            <th>⬇️</th>
            <th>#</th>
            <th>Cliente</th>
            <th>📱</th>
            <th>Email</th>
            <th>Modelo</th>
            <th>Preview</th>
            <th>Ver</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <?php
            $bg = ($order['status'] === 'Entregado') ? '#e6f9ec' : (($order['status'] === 'Pagado') ? '#e6f0ff' : '#fff9e6');
            $styles = json_decode($order["styles"], true);
        ?>
        <?php
    $bg = ($order['status'] === 'Entregado') ? '#e6f9ec' :
          (($order['status'] === 'Pagado') ? '#e6f0ff' : '#fff9e6');
?>
<tr style="background-color: <?= $bg ?>;">
            <td><input type="checkbox" class="select-preview" value="<?= $order['id'] ?>"></td>
            <td><?= $order['id'] ?></td>
            <td><?= $order['name'] ?> <?= $order['lastname'] ?></td>
            <?php
            $cleaned_phone = preg_replace('/[^0-9]/', '', $order['phone']);
            $whatsapp_link_number = '';
            if (strlen($cleaned_phone) == 10) { // e.g., 381xxxxxxx
                $whatsapp_link_number = '+549' . $cleaned_phone;
            } elseif (strlen($cleaned_phone) == 12 && substr($cleaned_phone, 0, 3) == '549') { // e.g., 549381xxxxxxx
                $whatsapp_link_number = '+' . $cleaned_phone;
            } else { // Fallback, assume it might be an international number or already has '+'
                $whatsapp_link_number = '+' . $cleaned_phone;
            }
            ?>
            <td><a href="https://wa.me/<?= $whatsapp_link_number ?>" target="_blank" style="font-size:20px; text-decoration:none;">📱</a></td>
            <td><?= $order['email'] ?></td>
            <td><img src="../assets/images/<?= $order['model_image'] ?>" style="max-height:40px; display:block; margin:auto;"></td>
            <td>
                    <?php
                    // Preparar datos para el renderizador (esto ya estaba bien, lo mantengo)
                    $plantilla_data = [
                        'id' => $order['template_id'],
                        'nombre' => $order['template_name'] ?? 'Plantilla sin nombre'
                    ];

                    for ($i = 1; $i <= 4; $i++) {
                        $plantilla_data["linea$i"] = [
                            'texto' => $order["text_line$i"],
                            'fuente' => $styles['fuente'][$i-1] ?? 'Arial',
                            'tamano' => $styles['tamano'][$i-1] ?? '16',
                            'negrita' => $styles['bold'][$i-1] ?? false,
                            'alineacion' => $styles['alineacion'][$i-1] ?? 'center',
                            'margen' => $styles['margen_top'][$i-1] ?? '0',
                            'mayuscula' => $styles['mayuscula'][$i-1] ?? false
                        ];
                    }
                    ?>
                <div class="preview-section">
                    <div class="plantilla-preview-wrapper" 
                         id="preview-<?= $order['id'] ?>"
                         data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
                        <?php 
                        $plantilla = $plantilla_data; 
                        include 'includes/_plantilla_preview.php'; 
                        ?>
                    </div>
                </div>
            </td>
            
            <td>
                <div style="text-align: center;">
                    <a href="../public/thanks.php?order=<?= htmlspecialchars($order['order_code'] ?? '') ?>&u=<?= htmlspecialchars($order['link_code'] ?? '') ?>" target="_blank" title="Ver Pedido">
                        👁️
                    </a>
                </div>
            </td>
            <td>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="pedido_id" value='<?= $order["id"] ?>'>
                    <select name="nuevo_estado" onchange="this.form.submit()">
                        <option <?= $order['status'] === 'En preparación' ? 'selected' : '' ?>>En preparación</option>
                        <option <?= $order['status'] === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option <?= $order['status'] === 'Pagado' ? 'selected' : '' ?>>Pagado</option>
                    </select>
                </form>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
            <td style="text-align: center;">
                <a href="../public/index.php?u=<?= htmlspecialchars($order['link_code']) ?>&order_id=<?= $order['id'] ?>" target="_blank" title="Modificar Pedido" style="text-decoration:none; color: #2c3e50; margin-right: 10px; font-size: 20px;">
                    ✏️
                </a>
                <a href="eliminar_pedido.php?id=<?= $order['id'] ?>" class="delete-link" title="Eliminar Pedido" style="text-decoration:none; color: #c0392b; font-size: 20px;">
                    🗑️
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
    <form method="GET" style="display: flex; align-items: center; gap: 10px;">
        <?php 
            // Preserve existing filters
            foreach ($_GET as $key => $value) {
                if ($key !== 'per_page' && $key !== 'page') {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
            }
        ?>
        <label for="per_page">Pedidos por página:</label>
        <select name="per_page" id="per_page" onchange="this.form.submit()">
            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
        </select>
    </form>
    <nav>
        <ul class="pagination" style="list-style: none; display: flex; gap: 5px; padding: 0; margin: 0;">
            <?php
            if ($total_pages > 1) {
                $query_params = $_GET;
                unset($query_params['page']);
                $base_url = http_build_query($query_params);

                if ($page > 1): ?>
                    <li><a href="?<?= $base_url ?>&page=<?= $page - 1 ?>" style="padding: 5px 10px; border: 1px solid #ddd; text-decoration: none;">&laquo;</a></li>
                <?php endif;

                for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="<?= ($i == $page) ? 'active' : '' ?>">
                        <a href="?<?= $base_url ?>&page=<?= $i ?>" style="padding: 5px 10px; border: 1px solid #ddd; text-decoration: none; <?= ($i == $page) ? 'background-color: #2c3e50; color: white;' : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor;

                if ($page < $total_pages): ?>
                    <li><a href="?<?= $base_url ?>&page=<?= $page + 1 ?>" style="padding: 5px 10px; border: 1px solid #ddd; text-decoration: none;">&raquo;</a></li>
                <?php endif;
            }
            ?>
        </ul>
    </nav>
</div>


<!-- Scripts comentados de PDF -->
<!-- <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script> -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script> -->

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
// Funciones para la sidebar
function abrirSidebar() {
    document.getElementById('sidebarWidget').classList.add('active');
}
function cerrarSidebar() {
    document.getElementById('sidebarWidget').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Renderizar todas las previsualizaciones de plantillas
    document.querySelectorAll('.plantilla-preview-wrapper').forEach(wrapper => {
        const templateDataAttr = wrapper.getAttribute('data-template-data');
        if (templateDataAttr) {
            try {
                const templateData = JSON.parse(templateDataAttr);
                const previewContainer = wrapper.querySelector('.plantilla-preview-container');
                if (previewContainer) {
                    window.renderizarPlantilla(previewContainer, templateData, 'white');
                }
            } catch (e) {
                console.error('Error al renderizar la vista previa en pedidos.php:', e);
            }
        }
    });

    // 2. Lógica para el botón de exportar a PDF
    const exportButton = document.getElementById('exportarPDF');
    if (exportButton) {
        exportButton.addEventListener('click', () => {
            const seleccionados = document.querySelectorAll('.select-preview:checked');
            if (seleccionados.length === 0) {
                alert('Por favor, selecciona al menos un pedido para exportar.');
                return;
            }

            const ids = Array.from(seleccionados).map(cb => cb.value);
            const url = `preparar_pdf.php?pedidos=${ids.join(',')}`;
            
            window.open(url, '_blank');
        });
    }

    // 3. Add confirmation to delete links
    const deleteLinks = document.querySelectorAll('.delete-link');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const url = this.href;
            if (confirm('¿Estás seguro de que deseas eliminar este pedido?')) {
                window.location.href = url;
            }
        });
    });
});
</script>

</body>
</html>