<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/auth.php';
require_once 'includes/db.php';

// --- CONFIGURACIÓN & FILTROS ---
$filtro = $_GET['estado'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';
$fecha = $_GET['fecha'] ?? '';

$condiciones = "orders.user_id = ?";
$params = [$_SESSION['user']['id']];

// Timezone
$timezone = $_SESSION['user']['timezone'] ?? 'America/Argentina/Buenos_Aires';
date_default_timezone_set($timezone);

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

// --- ESTADOS PERSONALIZADOS ---
$stmt_statuses = $pdo->prepare("SELECT * FROM custom_statuses WHERE user_id = ? ORDER BY display_order ASC, status_name ASC");
$stmt_statuses->execute([$_SESSION['user']['id']]);
$custom_statuses = $stmt_statuses->fetchAll(PDO::FETCH_ASSOC);

$status_colors = [];
foreach ($custom_statuses as $status) {
    $status_colors[$status['status_name']] = $status['color'];
}

// --- PAGINACIÓN ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, [10, 25, 50, 100])) $per_page = 10;

// Total Count
$count_sql = "SELECT COUNT(*) FROM orders JOIN users ON orders.user_id = users.id WHERE $condiciones";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

$offset = ($page - 1) * $per_page;

// Fetch Orders
$sql = "SELECT orders.*, users.whatsapp, users.link_code, models.image AS model_image FROM orders JOIN models ON orders.model_id = models.id
JOIN users ON orders.user_id = users.id WHERE $condiciones ORDER BY id DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fuentes para previsualización
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

// --- RUTAS & EXPORTACIONES ---
$rutas = [];
try {
    $stmt_rutas = $pdo->query("SELECT * FROM rutas ORDER BY fecha_creacion DESC LIMIT 50");
    $rutas = $stmt_rutas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$stmt_batches = $pdo->prepare("SELECT * FROM export_batches WHERE user_id = ? ORDER BY export_date DESC");
$stmt_batches->execute([$_SESSION['user']['id']]);
$export_batches = $stmt_batches->fetchAll(PDO::FETCH_ASSOC);

// --- ACCIONES POST ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND orders.user_id = ?");
    $stmt->execute([$_POST['nuevo_estado'], $_POST['pedido_id'], $_SESSION['user']['id']]);
    header("Location: pedidos.php?" . http_build_query($_GET));
    exit;
}

$current_tab = $_GET['tab'] ?? 'pedidos';

require_once 'includes/header.php';
?>

<!-- Estilos Específicos para Previews (Legacy Support) -->
<?php foreach ($fonts as $font): ?>
<link href="https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $font) ?>:wght@400;700&display=swap" rel="stylesheet">
<?php endforeach; ?>
<link rel="stylesheet" href="../assets/css/plantilla-preview.css">
<style>
    /* Fix para mantener el aspecto de los previews sin que Tailwind interfiera */
    .plantilla-preview-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        width: 210px;
        height: 80px;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        background-color: #000;
        overflow: hidden;
        margin: 0 auto;
    }
    .plantilla-preview-wrapper .plantilla-preview-container {
        width: 410px;
        height: 160px;
        background: #000;
        flex-shrink: 0;
        transform: scale(0.48);
        transform-origin: center;
    }
</style>

<!-- Barra de Herramientas Principal -->
<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    
    <!-- Pestañas (Tabs) -->
    <div class="flex space-x-1 bg-gray-200 p-1 rounded-xl">
        <a href="?tab=pedidos" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $current_tab == 'pedidos' ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            <i class="fas fa-box mr-1.5"></i> Pedidos
        </a>
        <a href="?tab=rutas" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $current_tab == 'rutas' ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            <i class="fas fa-route mr-1.5"></i> Rutas
        </a>
        <a href="?tab=exportaciones" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $current_tab == 'exportaciones' ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            <i class="fas fa-file-export mr-1.5"></i> Exportaciones
        </a>
    </div>

    <!-- Botón de Filtros (Drawer Trigger) -->
    <button onclick="abrirSidebar()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium shadow-sm">
        <i class="fas fa-filter text-brand-500"></i> Filtros y Búsqueda
    </button>
</div>

<!-- CONTENIDO: PEDIDOS -->
<?php if ($current_tab == 'pedidos'): ?>
<div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
    
    <!-- Floating Action Buttons -->
    <div class="fixed bottom-6 right-6 flex flex-col gap-3 z-40">
        <button id="crearRuta" title="Crear Hoja de Ruta" class="w-12 h-12 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 flex items-center justify-center transition-transform hover:scale-105">
            <i class="fas fa-map-marked-alt text-lg"></i>
        </button>
        <button id="exportarPDF" title="Exportar a PDF" class="w-12 h-12 bg-brand-600 text-white rounded-full shadow-lg hover:bg-brand-700 flex items-center justify-center transition-transform hover:scale-105">
            <i class="fas fa-file-pdf text-lg"></i>
        </button>
        <button id="eliminarSeleccionados" title="Eliminar Seleccionados" class="w-12 h-12 bg-red-500 text-white rounded-full shadow-lg hover:bg-red-600 flex items-center justify-center transition-transform hover:scale-105">
            <i class="fas fa-trash-alt text-lg"></i>
        </button>
    </div>

    <!-- VISTA MÓVIL: TARJETAS (Visible solo en móvil) -->
    <div class="block md:hidden p-4 space-y-4 bg-gray-50">
        <?php foreach ($orders as $order): 
            $statusColor = $status_colors[$order['status']] ?? '#cbd5e1';
            $cleaned_phone = preg_replace('/[^0-9]/', '', $order['phone']);
            $wa_link = strlen($cleaned_phone) >= 10 ? "https://wa.me/549" . substr($cleaned_phone, -10) : "#";
            
            // Reutilizamos lógica de preview
            $styles = json_decode($order["styles"], true);
            $plantilla_data = [
                'id' => $order['template_id'],
                'nombre' => $order['template_name'] ?? 'Sin nombre'
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
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden relative">
            <!-- Barra lateral de color estado -->
            <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background-color: <?= $statusColor ?>;"></div>
            
            <div class="p-4 pl-5">
                <!-- Header Tarjeta -->
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" class="select-preview w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500" value="<?= $order['id'] ?>">
                        <span class="font-bold text-gray-800">#<?= $order['id'] ?></span>
                    </div>
                    <div class="text-xs text-gray-400"><?= date('d/m H:i', strtotime($order['created_at'])) ?></div>
                </div>

                <!-- Info Cliente -->
                <div class="mb-3">
                    <h3 class="font-semibold text-gray-900 text-lg leading-tight mb-1">
                        <?= htmlspecialchars($order['name'] . ' ' . $order['lastname']) ?>
                    </h3>
                    <div class="flex flex-col gap-1 text-sm text-gray-600">
                        <?php if(!empty($order['address'])): ?>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-map-marker-alt mt-1 text-gray-400 w-4 text-center"></i>
                                <span><?= htmlspecialchars($order['address']) ?></span>
                                <?php if($order['lat'] && $order['lng']): ?>
                                    <i class="fas fa-crosshairs text-blue-500 text-[10px] mt-1.5" title="Ubicación GPS precisa"></i>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="flex items-center gap-2">
                            <a href="<?= $wa_link ?>" target="_blank" class="text-green-600 bg-green-50 px-2 py-0.5 rounded-full text-xs font-bold flex items-center gap-1">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <span class="text-gray-500 text-xs"><?= htmlspecialchars($order['phone']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Preview y Estado -->
                <div class="bg-gray-50 rounded-lg p-3 mb-3 border border-gray-100 flex flex-col items-center">
                    <div class="plantilla-preview-wrapper shadow-sm mb-3" id="preview-mobile-<?= $order['id'] ?>" data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
                        <?php 
                        $plantilla = $plantilla_data; 
                        include 'includes/_plantilla_preview.php'; 
                        ?>
                    </div>
                    
                    <form method="POST" class="w-full">
                        <input type="hidden" name="pedido_id" value='<?= $order["id"] ?>'>
                        <div class="relative">
                            <select name="nuevo_estado" onchange="this.form.submit()" 
                                class="appearance-none block w-full pl-3 pr-8 py-2 text-sm font-semibold rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer text-gray-700 bg-white shadow-sm">
                                <?php foreach ($custom_statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status['status_name']) ?>" <?= $order['status'] === $status['status_name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status['status_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($custom_statuses)): ?>
                                    <option><?= htmlspecialchars($order['status']) ?></option>
                                <?php endif; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Acciones Footer -->
                <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                    <div class="flex gap-2">
                        <?php if($order['model_image']): ?>
                            <img src="../assets/images/<?= $order['model_image'] ?>" class="h-8 w-8 object-contain rounded border border-gray-200 p-0.5" alt="Modelo">
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-3">
                        <a href="../public/thanks.php?order=<?= htmlspecialchars($order['order_code'] ?? '') ?>&u=<?= htmlspecialchars($order['link_code'] ?? '') ?>" target="_blank" class="text-gray-400 hover:text-brand-600 bg-gray-50 p-2 rounded-full" title="Ver Público">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="editar_pedido.php?id=<?= $order['id'] ?>" class="text-gray-400 hover:text-blue-600 bg-gray-50 p-2 rounded-full" title="Editar">
                            <i class="fas fa-pen"></i>
                        </a>
                        <a href="eliminar_pedido.php?id=<?= $order['id'] ?>" class="delete-link text-gray-400 hover:text-red-600 bg-gray-50 p-2 rounded-full" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- VISTA ESCRITORIO: TABLA (Visible solo en md+) -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 w-10 text-center">
                        <i class="fas fa-check-square text-gray-400"></i>
                    </th>
                    <th class="px-4 py-3">#ID</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Contacto</th>
                    <th class="px-4 py-3">Detalle</th>
                    <th class="px-4 py-3 text-center">Vista Previa</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($orders as $order): ?>
                <?php 
                    $statusColor = $status_colors[$order['status']] ?? '#cbd5e1';
                ?>
                <tr class="bg-white hover:bg-gray-50 transition-colors group">
                    <td class="px-4 py-4 text-center">
                        <input type="checkbox" class="select-preview w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500" value="<?= $order['id'] ?>">
                    </td>
                    <td class="px-4 py-4 font-medium text-gray-900">
                        #<?= $order['id'] ?>
                        <div class="text-xs text-gray-400 mt-0.5"><?= date('d/m H:i', strtotime($order['created_at'])) ?></div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="font-semibold text-gray-800"><?= htmlspecialchars($order['name'] . ' ' . $order['lastname']) ?></div>
                        <?php if(!empty($order['address'])): ?>
                            <div class="text-xs text-gray-500 mt-1 flex items-start gap-1">
                                <i class="fas fa-map-marker-alt mt-0.5 text-gray-400"></i>
                                <span class="truncate max-w-[150px]"><?= htmlspecialchars($order['address']) ?></span>
                                <?php if($order['lat'] && $order['lng']): ?>
                                    <i class="fas fa-crosshairs text-blue-500 text-[10px] mt-0.5" title="GPS"></i>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4">
                        <?php
                            $cleaned_phone = preg_replace('/[^0-9]/', '', $order['phone']);
                            $wa_link = strlen($cleaned_phone) >= 10 ? "https://wa.me/549" . substr($cleaned_phone, -10) : "#";
                        ?>
                        <div class="flex items-center gap-2">
                            <a href="<?= $wa_link ?>" target="_blank" class="text-green-500 hover:text-green-600 bg-green-50 hover:bg-green-100 p-1.5 rounded-full transition-colors">
                                <i class="fab fa-whatsapp text-lg"></i>
                            </a>
                            <span class="text-xs text-gray-600"><?= htmlspecialchars($order['phone']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <?php if($order['model_image']): ?>
                                <img src="../assets/images/<?= $order['model_image'] ?>" class="h-8 w-8 object-contain rounded border border-gray-200 bg-white p-0.5" alt="Modelo">
                            <?php endif; ?>
                            <?php if(!empty($order['comments'])): ?>
                                <span class="text-xs bg-yellow-50 text-yellow-700 px-2 py-1 rounded border border-yellow-100" title="<?= htmlspecialchars($order['comments']) ?>">
                                    <i class="fas fa-comment-alt mr-1"></i> Nota
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <?php
                            $styles = json_decode($order["styles"], true);
                            $plantilla_data = [
                                'id' => $order['template_id'],
                                'nombre' => $order['template_name'] ?? 'Sin nombre'
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
                        <div class="plantilla-preview-wrapper shadow-sm" id="preview-<?= $order['id'] ?>" data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
                            <?php 
                            $plantilla = $plantilla_data; 
                            include 'includes/_plantilla_preview.php'; 
                            ?>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <form method="POST" class="m-0">
                            <input type="hidden" name="pedido_id" value='<?= $order["id"] ?>'>
                            <div class="relative">
                                <select name="nuevo_estado" onchange="this.form.submit()" 
                                    class="appearance-none block w-full pl-3 pr-8 py-1.5 text-xs font-semibold rounded-full border-none focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-brand-500 cursor-pointer text-white shadow-sm transition-opacity hover:opacity-90"
                                    style="background-color: <?= $statusColor ?>; background-image: none;">
                                    <?php foreach ($custom_statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status['status_name']) ?>" class="bg-white text-gray-800" <?= $order['status'] === $status['status_name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($status['status_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (empty($custom_statuses)): ?>
                                        <option class="bg-white text-gray-800"><?= htmlspecialchars($order['status']) ?></option>
                                    <?php endif; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                                    <i class="fas fa-chevron-down text-[10px]"></i>
                                </div>
                            </div>
                        </form>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="../public/thanks.php?order=<?= htmlspecialchars($order['order_code'] ?? '') ?>&u=<?= htmlspecialchars($order['link_code'] ?? '') ?>" target="_blank" class="p-2 text-gray-400 hover:text-brand-600 transition-colors" title="Ver Público">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="editar_pedido.php?id=<?= $order['id'] ?>" class="p-2 text-gray-400 hover:text-blue-600 transition-colors" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="eliminar_pedido.php?id=<?= $order['id'] ?>" class="delete-link p-2 text-gray-400 hover:text-red-600 transition-colors" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación Tailwind -->
    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-t border-gray-100 gap-4">
        
        <!-- Selector Per Page -->
        <form method="GET" class="flex items-center gap-2 text-sm text-gray-600">
            <?php foreach ($_GET as $key => $value): if ($key !== 'per_page' && $key !== 'page') echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">'; endforeach; ?>
            <span>Mostrar</span>
            <select name="per_page" onchange="this.form.submit()" class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-1.5">
                <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
            </select>
            <span>por pág.</span>
        </form>

        <!-- Paginador -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="inline-flex -space-x-px text-sm">
                <?php
                $query_params = $_GET;
                unset($query_params['page']);
                $base_url = http_build_query($query_params);
                ?>
                
                <?php if ($page > 1): ?>
                <li>
                    <a href="?<?= $base_url ?>&page=<?= $page - 1 ?>" class="flex items-center justify-center px-3 h-8 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li>
                    <a href="?<?= $base_url ?>&page=<?= $i ?>" class="flex items-center justify-center px-3 h-8 leading-tight border border-gray-300 <?= $i == $page ? 'text-brand-600 border-brand-300 bg-brand-50 hover:bg-brand-100 hover:text-brand-700' : 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700' ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="?<?= $base_url ?>&page=<?= $page + 1 ?>" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- CONTENIDO: RUTAS -->
<?php if ($current_tab == 'rutas'): ?>
<div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-800">Historial de Hojas de Ruta</h2>
    </div>

    <?php if (empty($rutas)): ?>
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <i class="fas fa-route text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-gray-500 font-medium">No hay hojas de ruta generadas</h3>
            <p class="text-gray-400 text-sm mt-1">Selecciona pedidos y usa el botón 🗺️ para crear una.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nombre</th>
                        <th class="px-6 py-3">Fecha</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php foreach ($rutas as $ruta): ?>
                    <tr class="bg-white hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">#<?= $ruta['id'] ?></td>
                        <td class="px-6 py-4 text-gray-900 font-semibold"><?= htmlspecialchars($ruta['nombre']) ?></td>
                        <td class="px-6 py-4"><?= date('d/m/Y H:i', strtotime($ruta['fecha_creacion'])) ?></td>
                        <td class="px-6 py-4">
                            <?php 
                                $estado = $ruta['estado'] ?? 'pendiente';
                                $colorClass = ($estado == 'completada') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            ?>
                            <span class="<?= $colorClass ?> text-xs font-medium px-2.5 py-0.5 rounded uppercase">
                                <?= ucfirst($estado) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="ruta/hoja_de_ruta.php?ruta_id=<?= $ruta['id'] ?>" target="_blank" class="text-brand-600 hover:text-brand-800 font-medium mr-3">
                                <i class="fas fa-external-link-alt mr-1"></i> Ver
                            </a>
                            <a href="eliminar_ruta.php?id=<?= $ruta['id'] ?>" onclick="return confirm('¿Eliminar esta ruta?')" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>


<!-- CONTENIDO: EXPORTACIONES -->
<?php if ($current_tab == 'exportaciones'): ?>
<div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-800">Archivos Exportados</h2>
    </div>
    
    <?php if (empty($export_batches)): ?>
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <i class="fas fa-file-archive text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-500">No hay exportaciones guardadas.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">Fecha Exportación</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php foreach ($export_batches as $batch): ?>
                    <tr class="bg-white hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">
                            <?= date('d/m/Y H:i:s', strtotime($batch['export_date'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-3">
                            <a href="preparar_pdf.php?batch_id=<?= $batch['id'] ?>" target="_blank" class="text-gray-600 hover:text-brand-600" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <button class="preview-btn text-brand-500 hover:text-brand-700" title="Vista Previa" data-layout='<?= htmlspecialchars($batch['layout_state'] ?? 'null') ?>'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="eliminar_exportacion.php?id=<?= $batch['id'] ?>" onclick="return confirm('¿Eliminar permanentemente?')" class="text-red-500 hover:text-red-700" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- SIDEBAR FILTROS (DRAWER) -->
<div id="sidebarWidget" class="fixed inset-y-0 right-0 w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50 flex flex-col">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-800">Filtros y Búsqueda</h3>
        <button onclick="cerrarSidebar()" class="text-gray-500 hover:text-gray-700 bg-white border border-gray-200 rounded-full w-8 h-8 flex items-center justify-center">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="p-6 flex-1 overflow-y-auto">
        <form method="GET" class="space-y-5">
            <!-- Mantener tabs -->
            <?php if(isset($_GET['tab'])): ?>
                <input type="hidden" name="tab" value="<?= htmlspecialchars($_GET['tab']) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado del Pedido</label>
                <select name="estado" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <?php foreach ($custom_statuses as $status): ?>
                        <option value="<?= htmlspecialchars($status['status_name']) ?>" <?= $filtro === $status['status_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['status_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar (Nombre, Texto)</label>
                <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm" placeholder="Ej: Juan, Calle Falsa...">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Creación</label>
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            </div>

            <div class="pt-4 flex gap-3">
                <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white py-2 px-4 rounded-lg text-sm font-medium shadow-sm transition-colors">
                    Aplicar Filtros
                </button>
                <a href="pedidos.php" class="flex-1 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 py-2 px-4 rounded-lg text-sm font-medium text-center transition-colors">
                    Limpiar
                </a>
            </div>
        </form>

        <hr class="my-6 border-gray-100">
        
        <div class="text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-3">Acciones Rápidas</p>
            <a href="pedidos.php?<?= http_build_query(array_merge($_GET, ['exportar' => 'csv'])) ?>" class="block w-full bg-green-50 text-green-700 border border-green-200 py-2 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors">
                <i class="fas fa-file-csv mr-2"></i> Descargar CSV
            </a>
        </div>
    </div>
</div>
<!-- Overlay para cerrar sidebar -->
<div id="sidebarOverlay" onclick="cerrarSidebar()" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 hidden transition-opacity opacity-0"></div>


<!-- Modal Vista Previa (Exportaciones) -->
<div id="previewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl overflow-hidden max-w-sm w-full relative">
        <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-sm font-bold text-gray-700">Vista Previa Layout</h3>
            <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 bg-gray-200 flex justify-center">
            <div id="previewSheet" class="bg-white shadow-lg relative border border-gray-300" style="width:210px; height:297px;">
                <!-- JS inyectará items aquí -->
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/plantilla-renderer.js"></script>
<script>
    // --- Lógica Sidebar Filtros ---
    const sidebarWidget = document.getElementById('sidebarWidget');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function abrirSidebar() {
        sidebarWidget.classList.remove('translate-x-full');
        sidebarOverlay.classList.remove('hidden');
        setTimeout(() => sidebarOverlay.classList.remove('opacity-0'), 10);
    }

    function cerrarSidebar() {
        sidebarWidget.classList.add('translate-x-full');
        sidebarOverlay.classList.add('opacity-0');
        setTimeout(() => sidebarOverlay.classList.add('hidden'), 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Renderizar Plantillas (Script Legacy mantenido)
        document.querySelectorAll('.plantilla-preview-wrapper').forEach(wrapper => {
            const templateDataAttr = wrapper.getAttribute('data-template-data');
            if (templateDataAttr) {
                try {
                    const templateData = JSON.parse(templateDataAttr);
                    const previewContainer = wrapper.querySelector('.plantilla-preview-container');
                    if (previewContainer) {
                        // Creamos el contenedor interno si no existe (el script original lo espera)
                        if (!previewContainer.querySelector('.preview')) {
                            const innerDiv = document.createElement('div');
                            innerDiv.className = 'plantilla-preview-container';
                            // wrapper.appendChild(innerDiv); 
                            // Nota: En el HTML ya incluí la estructura esperada por CSS. 
                            // Solo necesitamos llamar al renderer.
                            window.renderizarPlantilla(wrapper, templateData, 'white');
                        } else {
                            window.renderizarPlantilla(previewContainer, templateData, 'white');
                        }
                    } else {
                         // Fallback si la estructura HTML cambió
                         const innerDiv = document.createElement('div');
                         innerDiv.className = 'plantilla-preview-container';
                         wrapper.appendChild(innerDiv);
                         window.renderizarPlantilla(innerDiv, templateData, 'white');
                    }
                } catch (e) { console.error('Error render:', e); }
            }
        });

        // 2. Acciones en Lote
        const getSelectedIds = () => {
            return Array.from(document.querySelectorAll('.select-preview:checked')).map(cb => cb.value);
        };

        const handleBatchAction = (btnId, urlPrefix, confirmMsg) => {
            const btn = document.getElementById(btnId);
            if(!btn) return;
            
            btn.addEventListener('click', () => {
                const ids = getSelectedIds();
                if (ids.length === 0) {
                    alert('Selecciona al menos un pedido.');
                    return;
                }
                if (confirmMsg && !confirm(confirmMsg)) return;

                const url = `${urlPrefix}${ids.join(',')}`;
                if (btnId === 'eliminarSeleccionados') {
                    window.location.href = url;
                } else {
                    window.open(url, '_blank');
                }
            });
        };

        handleBatchAction('crearRuta', 'preparar_ruta.php?ids=');
        handleBatchAction('exportarPDF', 'preparar_pdf.php?pedidos=');
        handleBatchAction('eliminarSeleccionados', 'eliminar_pedidos_seleccionados.php?ids=', '¿Eliminar pedidos seleccionados permanentemente?');

        // 3. Delete Links confirmation
        document.querySelectorAll('.delete-link').forEach(link => {
            link.addEventListener('click', (e) => {
                if(!confirm('¿Eliminar este pedido?')) e.preventDefault();
            });
        });

        // 4. Modal Preview Exportaciones
        const modal = document.getElementById('previewModal');
        const previewSheet = document.getElementById('previewSheet');
        const MM_TO_PX = 210 / 210; // 1:1 scale for simplicity in this small view

        document.querySelectorAll('.preview-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const layout = JSON.parse(btn.dataset.layout || '[]');
                previewSheet.innerHTML = '';
                
                layout.forEach(item => {
                    const el = document.createElement('div');
                    el.style.position = 'absolute';
                    el.style.left = item.x + 'px';
                    el.style.top = item.y + 'px';
                    
                    if(item.is_rect) {
                        el.style.width = item.width + 'px';
                        el.style.height = item.height + 'px';
                        el.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    } else {
                        // Stamp approx size
                        const w = 38 * (item.scale || 1);
                        const h = 14 * (item.scale || 1);
                        el.style.width = w + 'px';
                        el.style.height = h + 'px';
                        el.style.backgroundColor = 'rgba(59, 130, 246, 0.5)';
                        el.style.transform = `rotate(${item.angle || 0}deg)`;
                    }
                    el.style.border = '1px solid rgba(255,255,255,0.8)';
                    previewSheet.appendChild(el);
                });
                modal.classList.remove('hidden');
            });
        });

        document.getElementById('closeModal').addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.add('hidden');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
