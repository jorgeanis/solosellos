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
<?php if ($estado_cuenta === 0): ?>
<style>
    body {
        overflow: hidden;
    }
    .blur-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        backdrop-filter: blur(4px);
        background-color: rgba(0, 0, 0, 0.3);
        z-index: 9998;
    }
    .modal-block {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.25);
        z-index: 9999;
        max-width: 90%;
        width: 400px;
        text-align: center;
        font-family: 'Poppins', sans-serif;
    }
    .modal-block h2 {
        margin-top: 0;
        color: #e74c3c;
    }
</style>
<div class="blur-overlay"></div>
<div class="modal-block">
    <h2>Cuenta Suspendida</h2>
    <p>La cuenta se encuentra suspendida, por favor contactarse con Administración de <strong>SoloSellos.com</strong></p>
</div>
<?php endif; ?>

<?php require_once 'includes/header.php';

// ID del usuario logueado
$user_id = $_SESSION['user']['id'];

// Estadísticas
function get_count($pdo, $sql, $param) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$param]);
    return $stmt->fetchColumn();
}

$total_pedidos = get_count($pdo, "SELECT COUNT(*) FROM orders WHERE user_id = ?", $user_id);
$total_modelos = get_count($pdo, "SELECT COUNT(*) FROM models WHERE user_id = ?", $user_id);
$total_categorias = get_count($pdo, "SELECT COUNT(*) FROM template_categories WHERE user_id = ?", $user_id);

$stmt = $pdo->prepare("
    SELECT COUNT(t.id)
    FROM templates t
    INNER JOIN template_categories c ON t.category_id = c.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$total_plantillas = $stmt->fetchColumn();

// Últimos pedidos
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$ultimos_pedidos = $stmt->fetchAll();
?>

<h2>Panel de Control</h2>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">
    <div class="card" style="flex: 1; min-width: 200px;">
        <h3><i class="fa-solid fa-box"></i> Pedidos</h3>
        <p style="font-size: 28px; margin: 10px 0;"><?= $total_pedidos ?></p>
        <small>Total de pedidos realizados</small>
    </div>
    <div class="card" style="flex: 1; min-width: 200px;">
        <h3><i class="fa-solid fa-layer-group"></i> Modelos</h3>
        <p style="font-size: 28px; margin: 10px 0;"><?= $total_modelos ?></p>
        <small>Modelos de sello cargados</small>
    </div>
    <div class="card" style="flex: 1; min-width: 200px;">
        <h3><i class="fa-solid fa-folder-tree"></i> Categorías</h3>
        <p style="font-size: 28px; margin: 10px 0;"><?= $total_categorias ?></p>
        <small>Categorías de plantillas</small>
    </div>
    <div class="card" style="flex: 1; min-width: 200px;">
        <h3><i class="fa-solid fa-palette"></i> Plantillas</h3>
        <p style="font-size: 28px; margin: 10px 0;"><?= $total_plantillas ?></p>
        <small>Total de plantillas creadas</small>
    </div>
</div>


<div class="card">
    <h3>Estado de la cuenta</h3>
    <p><strong>Tipo de Plan:</strong> <?= $plan_cuenta ?? 'No especificado' ?></p>
    <p><strong>Estado:</strong> <?= ($estado_cuenta == 1) ? 'Activo ✅' : 'Inactivo ❌' ?></p>
    <p><strong>Válido desde:</strong> <?= $desde_cuenta ?? '—' ?></p>
    <p><strong>Hasta:</strong> <?= $hasta_cuenta ?? '—' ?></p>
</div>
<div class="card">
    <h3><i class="fa-solid fa-clock-rotate-left"></i> Últimos 5 pedidos</h3>
    <?php if (count($ultimos_pedidos) === 0): ?>
        <p>No hay pedidos registrados aún.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Modelo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimos_pedidos as $pedido): ?>
                    <tr>
                        <td><?= $pedido['id'] ?></td>
                        <td><?= htmlspecialchars($pedido['name']) ?> <?= htmlspecialchars($pedido['lastname']) ?></td>
                        <td><?= $pedido['model_id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></td>
                        <td>
                            <span style="padding:4px 8px; border-radius:5px;
                            background-color:
                                <?= $pedido['status'] === 'Entregado' ? '#2ecc71' :
                                   ($pedido['status'] === 'Pagado' ? '#3498db' : '#f39c12') ?>;
                            color: white;">
                                <?= $pedido['status'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
