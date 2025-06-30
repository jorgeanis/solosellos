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

$sql = "SELECT orders.*, users.whatsapp, models.image AS model_image FROM orders JOIN models ON orders.model_id = models.id
JOIN users ON orders.user_id = users.id WHERE $condiciones ORDER BY id DESC";
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

<style>
.preview {
    transform: scale(0.5);
    transform-origin: top left;
    width: 420px;
    height: 160px;
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
}

.preview-wrapper {
    width: 190px;
    height: 70px;
    overflow: hidden;
    justify-content: center;
    align-items: center;
    overflow: hidden;
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
            <th>⬇️</th>
            <th>Estado</th>
            <th>Fecha</th>
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
            <td><a href="https://wa.me/+549<?= preg_replace('/[^0-9]/', '', $order['whatsapp']) ?>" target="_blank" style="font-size:20px; text-decoration:none;">📱</a></td>
            <td><?= $order['email'] ?></td>
            <td><img src="../assets/images/<?= $order['model_image'] ?>" style="max-height:60px; display:block; margin:auto;"></td>
            <td>
                <div class="preview-wrapper" style="position:relative;">
                <div style="width: 380px; aspect-ratio: 1.875;">
                <div style="justify-content: center; align-items: center; transform: scale(0.5); transform-origin: top left; width: 380px; height: 140px; background: black; position: relative; padding-top: 5px;">
                    <?php
                    $styles = json_decode($order['styles'], true);
                    for ($i = 1; $i <= 4; $i++) {
                        $line = $order['text_line' . $i];
                        $font = $styles['fuente'][$i - 1] ?? 'Arial';
                        $size = $styles['tamano'][$i - 1] ?? 14;
                        $bold = !empty($styles['bold'][$i - 1]) ? 'bold' : 'normal';
                        $top = $styles['margen_top'][$i - 1] ?? 0;
                        echo '<div style="';
                        echo 'font-family:' . $font . ';';
                        echo 'font-size:' . $size . 'px;';
                        echo 'font-weight:' . $bold . ';';
                        echo 'color:white;';
                        echo 'margin-top:' . $top . 'px;';
                        echo 'text-align:center;';
                        echo 'width:100%;';
                        echo '">' . $line . '</div>';
                    }
                    ?>
                </div>
                </div>

                <div class="preview" id="preview-<?= $order['id'] ?>" style="width: 380px; height: 140px; background: black; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php
                            $text = htmlspecialchars($order["text_line$i"]);
                            if (empty($text)) continue;
                            $font = $styles["fuente"][$i-1] ?? 'Arial';
                            $size = $styles["tamano"][$i-1] ?? 20;
                            $bold = !empty($styles["bold"][$i-1]) ? "bold" : "normal";
                            $align = $styles["alineacion"][$i-1] ?? "center";
                            $extra = isset($styles["margen_top"][$i-1]) ? (int)$styles["margen_top"][$i-1] : 0;
                            $base_margin = 0;
                            $margin_top = max(-10, $base_margin + $extra);
                        ?>
                        <div class="linea-prev" style="
                            margin-top: <?= $margin_top ?>px;
                            font-family: '<?= $font ?>', sans-serif;
                            font-size: <?= $size ?>px;
                            font-weight: <?= $bold ?>;
                            text-align: <?= $align ?>;
                            line-height: <?= $size * 1.1 ?>px;
                            width: 380px;
                            color: white;
                        "><?= $text ?></div>
                    <?php endfor; ?>
                        <td>
                    <div style="margin-top: 4px; text-align: center;">
    <button onclick="downloadPreview(<?= $order['id'] ?>)" title="Descargar PNG" style="background: none; border: none; cursor: pointer; font-size: 18px;">⬇️</button>
    </div>
                    </td>
                </div>
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
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const exportButton = document.getElementById('exportarPDF');
    if (exportButton) {
        exportButton.addEventListener('click', exportSelectedToPDF);
    }
});


async function exportSelectedToPDF() {
    document.getElementById('loadingExport').style.display = 'flex';
    const checkboxes = document.querySelectorAll('.select-preview:checked');
    if (checkboxes.length === 0) {
        alert("Seleccioná al menos un preview.");
        return;
    }

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'pt', 'a4');
    const marginX = 20;
    const marginY = 20;
    const spacingX = 0;
    const spacingY = 0;
    const renderScale = 4;

    const imgWidth = 107.72;  // 34mm
    const imgHeight = 39.69;  // 18mm

    const pageWidth = pdf.internal.pageSize.getWidth() - 2 * marginX;
    const pageHeight = pdf.internal.pageSize.getHeight() - 2 * marginY;

    const maxCols = Math.floor(pageWidth / (imgWidth + spacingX));
    const maxRows = Math.floor(pageHeight / (imgHeight + spacingY));

    const total = checkboxes.length;

    // Cálculo ideal de columnas y filas para formar una grilla cuadrada
    let idealCols = Math.ceil(Math.sqrt(total));
    let idealRows = Math.ceil(total / idealCols);

    // Asegurarse que no se exceda del espacio disponible
    const cols = Math.min(idealCols, maxCols);
    const rows = Math.min(Math.ceil(total / cols), maxRows);

    let currentCol = 0;
    let currentRow = 0;

    for (let i = 0; i < total; i++) {
        const id = checkboxes[i].value;
        const preview = document.getElementById('preview-' + id);
        if (!preview) continue;

        const canvas = await html2canvas(preview, {
            backgroundColor: '#000',
            scale: renderScale
        });
        const imgData = canvas.toDataURL('image/png');

        const x = marginX + currentCol * (imgWidth + spacingX);
        const y = marginY + currentRow * (imgHeight + spacingY);

        pdf.addImage(imgData, 'PNG', x, y, imgWidth, imgHeight);

        currentCol++;
        if (currentCol >= cols) {
            currentCol = 0;
            currentRow++;
            if (currentRow >= rows && i < total - 1) {
                pdf.addPage();
                currentRow = 0;
            }
        }
    }

    pdf.save("previews.pdf");
    document.getElementById('loadingExport').style.display = 'none';
}

</script>

<!-- Loader de exportación -->
<div id="loadingExport" style="
    display:none;
    position:fixed;
    top:0; left:0; width:100vw; height:100vh;
    background:rgba(0,0,0,0.5);
    z-index:9999;
    justify-content:center;
    align-items:center;
    color:white;
    font-size:20px;
    font-family: Roboto, sans-serif;">
  <div>
    🖨️ Generando PDF, por favor esperá...
  </div>
</div>