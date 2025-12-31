<?php
require_once 'includes/auth.php';

if (!isset($pdo)) {
    require_once 'includes/db.php';
}

$stmt = $pdo->prepare("SELECT plan, desde, hasta, active FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$datos_cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

$estado_cuenta = isset($datos_cuenta['active']) ? (int)$datos_cuenta['active'] : 0;
$plan_cuenta = $datos_cuenta['plan'] ?? 'No especificado';
$desde_cuenta = $datos_cuenta['desde'] ?? '—';
$hasta_cuenta = $datos_cuenta['hasta'] ?? '—';
?>

<!-- Bloque de Cuenta Suspendida (Estilo Modal Tailwind) -->
<?php if ($estado_cuenta === 0): ?>
<div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full text-center border-t-4 border-red-500 animate-bounce-in">
        <div class="mb-4 text-red-100 bg-red-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto">
            <i class="fa-solid fa-lock text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Cuenta Suspendida</h2>
        <p class="text-gray-600 mb-6 text-sm">Tu cuenta se encuentra inactiva. Por favor, contacta con la administración de <strong>SoloSellos.com</strong> para regularizar tu situación.</p>
        <a href="mailto:soporte@solosellos.com" class="inline-block bg-red-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">Contactar Soporte</a>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/header.php';

// ID del usuario logueado
$user_id = $_SESSION['user']['id'];

// Set Timezone
$timezone = $_SESSION['user']['timezone'] ?? 'America/Argentina/Buenos_Aires';
date_default_timezone_set($timezone);

// --- GET DATE FILTERS ---
$today_start = date('Y-m-d 00:00:00');
$week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
$month_start = date('Y-m-01 00:00:00');

// --- HELPER FUNCTION ---
function get_sales_stats($pdo, $user_id, $start_date, $end_date = null) {
    $sql = "SELECT SUM(price) as total_sales, COUNT(id) as total_orders 
            FROM orders 
            WHERE user_id = ? AND status != 'cancelled'";
    
    $params = [$user_id];
    
    if ($start_date) {
        $sql .= " AND created_at >= ?";
        $params[] = $start_date;
    }
    if ($end_date) {
        $sql .= " AND created_at <= ?";
        $params[] = $end_date;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'total' => $res['total_sales'] ?? 0,
        'count' => $res['total_orders'] ?? 0
    ];
}

// --- FETCH METRICS ---
$stats_today = get_sales_stats($pdo, $user_id, $today_start);
$stats_week = get_sales_stats($pdo, $user_id, $week_start);
$stats_month = get_sales_stats($pdo, $user_id, $month_start);

// Top Selling Models
$stmt_top = $pdo->prepare("
    SELECT m.title, COUNT(o.id) as qty, SUM(o.price) as revenue
    FROM orders o
    JOIN models m ON o.model_id = m.id
    WHERE o.user_id = ? AND o.status != 'cancelled'
    GROUP BY o.model_id
    ORDER BY qty DESC
    LIMIT 5
");
$stmt_top->execute([$user_id]);
$top_models = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

// Pending Orders Count
$stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pendiente'");
$stmt_pending->execute([$user_id]);
$pending_count = $stmt_pending->fetchColumn();

// Public Link Logic
$stmt = $pdo->prepare("SELECT link_code FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_link_code = $stmt->fetchColumn();
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain_name = $_SERVER['HTTP_HOST'];
$public_link = $protocol . $domain_name . '/public/index.php?u=' . $user_link_code;

// Últimos pedidos (Extended info)
$stmt = $pdo->prepare("
    SELECT o.*, m.title as model_title 
    FROM orders o
    LEFT JOIN models m ON o.model_id = m.id
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$ultimos_pedidos = $stmt->fetchAll();
?>

<!-- Sección Principal -->
<div class="mb-8">
    
    <!-- Alertas de Estado y Link -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Tarjeta: Enlace Público -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-1 h-full bg-brand-500"></div>
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Tu Enlace de Ventas</h3>
                    <p class="text-sm text-gray-500">Comparte este link para recibir pedidos</p>
                </div>
                <div class="bg-brand-50 p-2 rounded-lg text-brand-600">
                    <i class="fa-solid fa-link text-xl"></i>
                </div>
            </div>
            
            <div class="flex gap-2">
                <input type="text" id="publicLink" value="<?= htmlspecialchars($public_link) ?>" readonly 
                    class="flex-1 bg-gray-50 border border-gray-200 text-gray-600 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block w-full p-2.5">
                <button id="copyButton" 
                    class="text-white bg-gray-800 hover:bg-gray-900 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-copy"></i> <span>Copiar</span>
                </button>
            </div>
        </div>

        <!-- Tarjeta: Estado Cuenta -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-gray-800"></div>
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Estado de la Cuenta</h3>
                    <p class="text-sm text-gray-500">Información de tu suscripción</p>
                </div>
                <div class="bg-gray-100 p-2 rounded-lg text-gray-600">
                    <i class="fa-solid fa-user-shield text-xl"></i>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="bg-gray-50 p-3 rounded-lg text-center">
                    <span class="block text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Plan</span>
                    <span class="font-bold text-gray-800"><?= htmlspecialchars($plan_cuenta) ?></span>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg text-center">
                    <span class="block text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Vence</span>
                    <span class="font-bold text-gray-800"><?= htmlspecialchars($hasta_cuenta) ?></span>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg text-center">
                    <span class="block text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Estado</span>
                    <?php if ($estado_cuenta == 1): ?>
                        <span class="inline-flex items-center gap-1 text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded-full text-xs">
                            Activo <i class="fa-solid fa-check-circle"></i>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-red-600 font-bold bg-red-100 px-2 py-0.5 rounded-full text-xs">
                            Inactivo <i class="fa-solid fa-times-circle"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Card 1 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
            <div class="absolute bottom-0 left-0 w-full h-1 bg-blue-500"></div>
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ventas Hoy</h4>
                <div class="text-blue-500 bg-blue-50 p-2 rounded-lg">
                    <i class="fa-solid fa-cash-register text-lg"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-2xl font-bold text-gray-800">$<?= number_format($stats_today['total'], 0, ',', '.') ?></span>
                <span class="text-xs text-gray-500 mt-1"><?= $stats_today['count'] ?> pedidos realizados</span>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
            <div class="absolute bottom-0 left-0 w-full h-1 bg-purple-500"></div>
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Esta Semana</h4>
                <div class="text-purple-500 bg-purple-50 p-2 rounded-lg">
                    <i class="fa-solid fa-calendar-week text-lg"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-2xl font-bold text-gray-800">$<?= number_format($stats_week['total'], 0, ',', '.') ?></span>
                <span class="text-xs text-gray-500 mt-1"><?= $stats_week['count'] ?> pedidos acumulados</span>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
            <div class="absolute bottom-0 left-0 w-full h-1 bg-green-500"></div>
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Este Mes</h4>
                <div class="text-green-500 bg-green-50 p-2 rounded-lg">
                    <i class="fa-solid fa-chart-line text-lg"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-2xl font-bold text-gray-800">$<?= number_format($stats_month['total'], 0, ',', '.') ?></span>
                <span class="text-xs text-gray-500 mt-1"><?= $stats_month['count'] ?> pedidos totales</span>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
            <div class="absolute bottom-0 left-0 w-full h-1 bg-orange-500"></div>
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pendientes</h4>
                <div class="text-orange-500 bg-orange-50 p-2 rounded-lg">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <span class="text-2xl font-bold text-gray-800"><?= $pending_count ?></span>
                <span class="text-xs text-gray-500 mt-1">Pedidos por procesar</span>
            </div>
        </div>

    </div>

    <!-- Tablas Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        <!-- Modelos Top -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-trophy text-yellow-500"></i> Modelos Más Vendidos
                </h3>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($top_models)): ?>
                    <div class="p-8 text-center text-gray-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                        No hay datos suficientes
                    </div>
                <?php else: ?>
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Modelo</th>
                                <th class="px-6 py-3 text-center">Cant.</th>
                                <th class="px-6 py-3 text-right">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_models as $tm): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($tm['title']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        <?= $tm['qty'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-green-600 font-bold">$<?= number_format($tm['revenue'], 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimos Pedidos -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-box text-brand-500"></i> Actividad Reciente
                </h3>
                <a href="pedidos.php" class="text-xs font-semibold text-brand-600 hover:text-brand-800">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($ultimos_pedidos)): ?>
                    <div class="p-8 text-center text-gray-400">
                        <i class="fa-regular fa-clipboard text-3xl mb-2 block"></i>
                        No hay pedidos recientes
                    </div>
                <?php else: ?>
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3">Monto</th>
                                <th class="px-6 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_pedidos as $pedido): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <a href="#" class="font-medium text-brand-600 hover:underline">#<?= $pedido['id'] ?></a>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <?= htmlspecialchars($pedido['name']) ?>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-700">$<?= number_format($pedido['price'], 0) ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $statusColors = [
                                            'pendiente' => 'bg-orange-100 text-orange-800',
                                            'aprobado' => 'bg-green-100 text-green-800',
                                            'completado' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $sClass = $statusColors[$pedido['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="<?= $sClass ?> text-xs font-medium px-2.5 py-0.5 rounded">
                                        <?= htmlspecialchars(ucfirst($pedido['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<script>
document.getElementById('copyButton').addEventListener('click', function() {
    const linkInput = document.getElementById('publicLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); 

    navigator.clipboard.writeText(linkInput.value).then(() => {
        const btn = this;
        const originalHtml = btn.innerHTML;
        const originalBg = btn.className;
        
        btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Copiado!</span>';
        btn.classList.remove('bg-gray-800', 'hover:bg-gray-900');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.className = originalBg;
        }, 2000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
